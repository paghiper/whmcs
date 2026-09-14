<?php
/**
 * PagHiper Integration Hooks
 * 
 * 1. Attaches PagHiper PDF to Invoice Emails.
 * 2. Auto-heals the invoicepdf.tpl integration to ensure it stays active.
 * 
 * @package    PagHiper para WHMCS
 * @version    3.1.0
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Henrique Cruz
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2026, PagHiper
 * @link       https://www.paghiper.com/
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Hook: EmailPreSend
 * Attaches the PagHiper PDF (Boleto or PIX) to invoice-related emails.
 */
add_hook('EmailPreSend', 1, function($vars) {
    // 1. Auto-Heal Check (Runs before any email is sent to ensure PDF is ready)
    $paghiper_config = getGatewayVariables('paghiper');
    $auto_heal_billet = isset($paghiper_config['auto_pdf_integration']) ? $paghiper_config['auto_pdf_integration'] : '';
        
    $paghiper_pix_config = getGatewayVariables('paghiper_pix');
    $auto_heal_pix = isset($paghiper_pix_config['auto_pdf_integration']) ? $paghiper_pix_config['auto_pdf_integration'] : '';

    if ($auto_heal_billet == 'on' || $auto_heal_billet == '1' || $auto_heal_pix == 'on' || $auto_heal_pix == '1') {
        $integrator_path = ROOTDIR . '/modules/gateways/paghiper/inc/helpers/integrate_pdf_template.php';
        if (file_exists($integrator_path)) {
            require_once($integrator_path);
            if (class_exists('PaghiperPdfInvoiceIntegrator')) {
                $integrator = new PaghiperPdfInvoiceIntegrator();
                if (!$integrator->isTplIntegrated()) {
                    $integrator->autoHeal();
                }
            }
        }
    }

    $email_template = $vars['messagename'];
    $invoice_id = $vars['relid'];
    $attachments = [];

    // Define which email templates should receive the attachment
    $paghiper_config = getGatewayVariables('paghiper');
    $db_templates = isset($paghiper_config['email_templates']) ? $paghiper_config['email_templates'] : '';
        
    $target_templates = $db_templates ? array_map('trim', explode(',', $db_templates)) : [];


    if (in_array($email_template, $target_templates) && $invoice_id) {
        
        // 1. Get Invoice Details
        $invoice = Capsule::table('tblinvoices')
            ->join('tblclients', 'tblclients.id', '=', 'tblinvoices.userid')
            ->where('tblinvoices.id', $invoice_id)
            ->select('tblinvoices.paymentmethod', 'tblinvoices.total', 'tblclients.id as client_id', 'tblclients.email')
            ->first();

        if (!$invoice) {
            return [];
        }

        $isPaghiper = (strpos($invoice->paymentmethod, 'paghiper') !== false);
        
        $paghiper_pix_config = getGatewayVariables('paghiper_pix');
    $issue_all_pix = isset($paghiper_pix_config['issue_all']) ? $paghiper_pix_config['issue_all'] : '';
        $paghiper_config = getGatewayVariables('paghiper');
    $issue_all_billet = isset($paghiper_config['issue_all']) ? $paghiper_config['issue_all'] : '';
        
        $pixActive = ($issue_all_pix == '1' || $issue_all_pix == 'on');
        $boletoActive = ($issue_all_billet == '1' || $issue_all_billet == 'on');

        if (!$isPaghiper && !$pixActive && !$boletoActive) {
            return []; // Nothing to generate
        }

        $is_pix = false;
        if ($invoice->paymentmethod == 'paghiper_pix') {
            $is_pix = true;
        } elseif (!$isPaghiper) {
            if ($pixActive) $is_pix = true; // PIX has priority if issue_all is active
        }
        
        // 2. Fetch the Asset URL from PagHiper Module Logic
        // We simulate the module's URL generation to get the JSON response
        $whmcs_url = rtrim(\App::getSystemUrl(), "/");
        $json_url = "{$whmcs_url}/modules/gateways/";
        $json_url .= ($is_pix) ? 'paghiper_pix.php' : 'paghiper.php';
        $json_url .= "?invoiceid={$invoice_id}&uuid={$invoice->client_id}&mail={$invoice->email}&json=1";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $json_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $json = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($json);
        $transaction_id = $result->transaction_id ?? '';
        
        $asset_url = '';
        if (!$is_pix) {
            $asset_url = (isset($result->bank_slip) && isset($result->bank_slip->url_slip_pdf)) 
                ? $result->bank_slip->url_slip_pdf 
                : ($result->url_slip_pdf ?? '');
        } else {
             $asset_url = (isset($result->pix_code) && isset($result->pix_code->qrcode_image_url))
                ? $result->pix_code->qrcode_image_url
                : ($result->qrcode_image_url ?? '');
        }

        // 3. Download and Attach
        if (!empty($transaction_id) && !empty($asset_url)) {
            $module_dir = ROOTDIR . '/modules/gateways/paghiper';
            $tmp_dir = ($is_pix) ? $module_dir . '/tmp/pix' : $module_dir . '/tmp/billets';
            
            if (!is_dir($tmp_dir)) mkdir($tmp_dir, 0755, true);

            $extension = ($is_pix) ? '.png' : '.pdf';
            $local_file = $tmp_dir . '/' . $transaction_id . $extension;

            if (!file_exists($local_file)) {
                $file_content = file_get_contents($asset_url);
                if ($file_content) file_put_contents($local_file, $file_content);
            }

            if (file_exists($local_file)) {
                $attachments['pay_slip'] = $local_file;
            }
        }
    }

    return ['attachments' => $attachments];
});

<?php
/**
 * Adiciona boleto bancário e link direto para boleto no WHMCS
 * 
 * @package    PagHiper para WHMCS
 * @version    3.1.1
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Henrique Cruz
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2026, PagHiper
 * @link       https://www.paghiper.com/
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");

use WHMCS\Database\Capsule;

function paghiper_display_digitable_line($vars) {
	
	$basedir = dirname(dirname(__DIR__));

    $merge_fields = [];
    $email_template = $vars['messagename'];
    $invoice_id = $vars['relid'];

    $paghiper_config = getGatewayVariables('paghiper');
    $db_templates = isset($paghiper_config['email_templates']) ? $paghiper_config['email_templates'] : '';
        
    $target_templates = $db_templates ? array_map('trim', explode(',', $db_templates)) : [];

    if(in_array($email_template, $target_templates)) {

        $invoice = Capsule::table('tblinvoices')->where('id', $invoice_id)->first();
        if (!$invoice) return [];
        
        $paghiper_config = getGatewayVariables('paghiper');
    $issue_all_billet = isset($paghiper_config['issue_all']) ? $paghiper_config['issue_all'] : '';
        
        if ($invoice->paymentmethod == 'paghiper' || $issue_all_billet == '1' || $issue_all_billet == 'on') {
            $whmcs_url = rtrim(\App::getSystemUrl(),"/");

            require_once($basedir . '/modules/gateways/paghiper/classes/PaghiperTransaction.php');
            $paghiperTransaction    = new PaghiperTransaction(['invoiceID' => $invoice_id, 'format' => 'array', 'forceGateway' => 'paghiper']);
            $invoiceTransaction     = $paghiperTransaction->process();
            if ($invoiceTransaction) {

            $digitable_line             = $invoiceTransaction['digitable_line'];
            $bar_code_number_to_image   = $invoiceTransaction['bar_code_number_to_image'];
            
            if($digitable_line) {
                $merge_fields['linha_digitavel'] = '<div style="text-align: center;" class="billet-barcode-container"><span>Linha digitável: <br><span style="font-size: 16px; color: #000000"><strong>';
                $merge_fields['linha_digitavel'] .= "<img class='billet-barcode' style='max-width: 100%;' height='50' src='{$whmcs_url}/modules/gateways/paghiper/assets/php/barcode.php?codigo={$bar_code_number_to_image}'><br>";
                $merge_fields['linha_digitavel'] .= $digitable_line;
                $merge_fields['linha_digitavel'] .= '</strong></span></span></div>';
            }
        }
        } // End of Boleto check
    }

    return $merge_fields;
}

add_hook('EmailPreSend', 1, "paghiper_display_digitable_line");

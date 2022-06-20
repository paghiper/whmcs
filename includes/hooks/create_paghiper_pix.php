<?php
/**
 * Adiciona QR code do PIX nos templates do WHMCS
 * 
 * @package    PagHiper para WHMCS
 * @version    2.3
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Henrique Cruz
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2021, PagHiper
 * @link       https://www.paghiper.com/
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");

function paghiper_display_pix_qr_code($vars) {
    $merge_fields = [];
    $email_template = $vars['messagename'];
    $invoice_id = $vars['relid'];

    $target_templates = array('Invoice Created', 'Invoice Payment Reminder', 'First Invoice Overdue Notice', 'Second Invoice Overdue Notice', 'Third Invoice Overdue Notice');

    if(in_array($email_template, $target_templates)) {
        // Todo: 
        $invoice = mysql_fetch_array(mysql_query("SELECT tblinvoices.*,tblclients.id as client_id, tblclients.email FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE tblinvoices.id='$invoice_id'"));

        $whmcs_url = rtrim(\App::getSystemUrl(),"/");
        $json_url = $whmcs_url."/modules/gateways/paghiper_pix.php?invoiceid=".$invoice_id."&uuid=".$invoice['client_id']."&mail=".$invoice['email']."&json=1&pix=true";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $json_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $json = curl_exec($ch);
        $result = json_decode($json);
		
        $digitable_line 	= (isset($result->pix_code)) ? $result->pix_code->emv : $result->emv;
        $qrcode_image_url 	= (isset($result->pix_code)) ? $result->pix_code->qrcode_image_url : $result->qrcode_image_url;

        if($digitable_line) {
			$merge_fields['codigo_pix'] = "<div style='text-align: center;' class='qr-code-container'><img class='qr-code' width='320' height='320' src='{$qrcode_image_url}'><br>";
            $merge_fields['codigo_pix'] .= '<h2 style="font-size: 16px; color: #000000">Use a opção QR Code no seu app de internet banking<br><span style="font-size: 14px; font-weight: normal;">Ou, se preferir, copie o texto abaixo para fazer o pagamento</span></h2>';
            $merge_fields['codigo_pix'] .= '<span>Seu código PIX: <br><span style="font-size: 16px; color: #000000"><strong>';
            $merge_fields['codigo_pix'] .= $digitable_line;
            $merge_fields['codigo_pix'] .= '</strong></span></span></div>';
        }


    }
    return $merge_fields;
}

add_hook('EmailPreSend', 1, "paghiper_display_pix_qr_code");

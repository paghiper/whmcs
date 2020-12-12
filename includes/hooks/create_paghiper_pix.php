<?php
/**
 * Adiciona QR code do PIX nos templates do WHMCS
 * @author     Henrique Cruz | henriquecruz.com.br
 * @copyright  Copyright (c) 2019 https://henriquecruz.com.br
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");
function display_pix_qr_code($vars) {
    $merge_fields = [];
    $email_template = $vars['messagename'];
    $invoice_id = $vars['relid'];

    $target_templates = array('Invoice Created', 'Invoice Payment Reminder', 'First Invoice Overdue Notice', 'Second Invoice Overdue Notice', 'Third Invoice Overdue Notice');

    if(in_array($email_template, $target_templates)) {
        // Todo: 
        $invoice = mysql_fetch_array(mysql_query("SELECT tblinvoices.*,tblclients.id as client_id, tblclients.email FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE tblinvoices.id='$invoice_id'"));

        $whmcs_url = rtrim(\App::getSystemUrl(),"/");
        $json = file_get_contents($whmcs_url."/modules/gateways/paghiper.php?invoiceid=".$invoice_id."&uuid=".$invoice['client_id']."&mail=".$invoice['email']."&json=1&pix=true");
		$result = json_decode($json);
		
        $digitable_line 	= (isset($result->pix_code)) ? $result->pix_code->emv : $result->emv;
        $qrcode_image_url 	= (isset($result->pix_code)) ? $result->pix_code->qrcode_image_url : $result->qrcode_image_url;

        if($digitable_line) {
			$merge_fields['codigo_pix'] = "<img width='320' height='320' src='{$qrcode_image_url}'>";
            $merge_fields['codigo_pix'] .= '<span>Seu código PIX: <br><span style="font-size: 16px; color: #000000"><strong>';
            $merge_fields['codigo_pix'] .= $digitable_line;
            $merge_fields['codigo_pix'] .= '</strong></span></span>';
        }


    }
    return $merge_fields;
}
add_hook('EmailPreSend', 1, "display_pix_qr_code");
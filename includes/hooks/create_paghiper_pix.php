<?php
/**
 * Adiciona QR code do PIX nos templates do WHMCS
 * 
 * @package    PagHiper para WHMCS
 * @version    2.5.3-rc1
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Henrique Cruz
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2025, PagHiper
 * @link       https://www.paghiper.com/
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");

function paghiper_display_pix_qr_code($vars) {
	
	// PHP 5.x compatibility
	if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
		$basedir = (function_exists('dirname')) ? dirname(__DIR__, 2) : realpath(__DIR__ . '/../..');
	} else {
		$basedir = (function_exists('dirname') && function_exists('dirname_with_levels')) ? dirname_with_levels(__DIR__, 2) : realpath(__DIR__ . '/../..');
	}

    $merge_fields = [];
    $email_template = $vars['messagename'];
    $invoice_id = $vars['relid'];

    $target_templates = array('Invoice Created', 'Invoice Payment Reminder', 'First Invoice Overdue Notice', 'Second Invoice Overdue Notice', 'Third Invoice Overdue Notice');

    if(in_array($email_template, $target_templates)) {

        require_once($basedir . '/modules/gateways/paghiper/classes/PaghiperTransaction.php');
        $paghiperTransaction    = new PaghiperTransaction(['invoiceID' => $invoice_id, 'format' => 'array']);
        $invoiceTransaction     = $paghiperTransaction->process();

        if($invoiceTransaction) {
		
            $digitable_line 	= $invoiceTransaction['emv'];
            $qrcode_image_url 	= $invoiceTransaction['qrcode_image_url'];
    
            if($digitable_line) {
                $merge_fields['codigo_pix'] = "<div style='text-align: center;' class='qr-code-container'><img class='qr-code' width='320' height='320' src='{$qrcode_image_url}'><br>";
                $merge_fields['codigo_pix'] .= '<h2 style="font-size: 16px; color: #000000">Use a opção QR Code no seu app de internet banking<br><span style="font-size: 14px; font-weight: normal;">Ou, se preferir, copie o texto abaixo para fazer o pagamento</span></h2>';
                $merge_fields['codigo_pix'] .= '<span>Seu código PIX: <br><span style="font-size: 16px; color: #000000"><strong>';
                $merge_fields['codigo_pix'] .= $digitable_line;
                $merge_fields['codigo_pix'] .= '</strong></span></span></div>';
            }
        }


    }
    return $merge_fields;
}

add_hook('EmailPreSend', 1, "paghiper_display_pix_qr_code");

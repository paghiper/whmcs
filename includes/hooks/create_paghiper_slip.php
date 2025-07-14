<?php
/**
 * Adiciona boleto bancário e link direto para boleto no WHMCS
 * 
 * @package    PagHiper para WHMCS
 * @version    2.5.3
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Henrique Cruz
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2025, PagHiper
 * @link       https://www.paghiper.com/
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");

function paghiper_display_digitable_line($vars) {
	
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

        $whmcs_url = rtrim(\App::getSystemUrl(),"/");

        require_once($basedir . '/modules/gateways/paghiper/classes/PaghiperTransaction.php');
        $paghiperTransaction    = new PaghiperTransaction(['invoiceID' => $invoice_id, 'format' => 'array']);
        $invoiceTransaction     = $paghiperTransaction->process();

        if($invoiceTransaction) {

            $digitable_line             = $invoiceTransaction['digitable_line'];
            $bar_code_number_to_image   = $invoiceTransaction['bar_code_number_to_image'];
            
            if($digitable_line) {
                $merge_fields['linha_digitavel'] = '<div style="text-align: center;" class="billet-barcode-container"><span>Linha digitável: <br><span style="font-size: 16px; color: #000000"><strong>';
                $merge_fields['linha_digitavel'] .= "<img class='billet-barcode' style='max-width: 100%;' height='50' src='{$whmcs_url}/modules/gateways/paghiper/assets/php/barcode.php?codigo={$bar_code_number_to_image}'><br>";
                $merge_fields['linha_digitavel'] .= $digitable_line;
                $merge_fields['linha_digitavel'] .= '</strong></span></span></div>';
            }
        }

    }
    return $merge_fields;
}

add_hook('EmailPreSend', 1, "paghiper_display_digitable_line");

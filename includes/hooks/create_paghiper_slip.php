<?php
/**
 * Adiciona boleto bancário e link direto para boleto no WHMCS
 * 
 * @package    PagHiper para WHMCS
 * @version    2.5.1
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Henrique Cruz
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2023, PagHiper
 * @link       https://www.paghiper.com/
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");

function paghiper_display_digitable_line($vars) {
    $merge_fields = [];
    $email_template = $vars['messagename'];
    $invoice_id = $vars['relid'];

    $target_templates = array('Invoice Created', 'Invoice Payment Reminder', 'First Invoice Overdue Notice', 'Second Invoice Overdue Notice', 'Third Invoice Overdue Notice');

    if(in_array($email_template, $target_templates)) {

        $whmcs_url = rtrim(\App::getSystemUrl(),"/");

        require_once(dirname(__FILE__) . '/../../modules/gateways/paghiper/classes/PaghiperTransaction.php');
        $paghiperTransaction    = new PaghiperTransaction(['invoiceID' => $invoice_id, 'format' => 'array']);
        $invoiceTransaction     = $paghiperTransaction->process();

        $digitable_line             = $invoiceTransaction['digitable_line'];
        $bar_code_number_to_image   = $invoiceTransaction['bar_code_number_to_image'];
        
        if($digitable_line) {
            $merge_fields['linha_digitavel'] = '<div style="text-align: center;" class="billet-barcode-container"><span>Linha digitável: <br><span style="font-size: 16px; color: #000000"><strong>';
            $merge_fields['linha_digitavel'] .= "<img class='billet-barcode' style='max-width: 100%;' height='50' src='{$whmcs_url}/modules/gateways/paghiper/assets/php/barcode.php?codigo={$bar_code_number_to_image}'><br>";
            $merge_fields['linha_digitavel'] .= $digitable_line;
            $merge_fields['linha_digitavel'] .= '</strong></span></span></div>';
        }

    }
    return $merge_fields;
}

add_hook('EmailPreSend', 1, "paghiper_display_digitable_line");

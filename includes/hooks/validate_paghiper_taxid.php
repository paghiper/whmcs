<?php
/**
 * Valida informações de faturamento do cliente no check-out
 * 
 * @package    PagHiper e Boleto para WHMCS
 * @version    2.5.3
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Henrique Cruz
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2025, PagHiper
 * @link       https://www.paghiper.com/
 */

// PHP 5.x compatibility
if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
    $basedir = (function_exists('dirname')) ? dirname(__DIR__, 2) : realpath(__DIR__ . '/../..');
} else {
    $basedir = (function_exists('dirname') && function_exists('dirname_with_levels')) ? dirname_with_levels(__DIR__, 2) : realpath(__DIR__ . '/../..');
}

require_once($basedir . '/modules/gateways/paghiper/inc/helpers/gateway_functions.php');

function paghiper_getClientDetails($vars, $gateway_config) {

    $gateway_admin = $gateway_config['admin'];
    $backup_admin = array_shift(mysql_fetch_array(mysql_query("SELECT username FROM tbladmins LIMIT 1")));

    $whmcs_admin = paghiper_autoSelectAdminUser($gateway_config);

    $query_params = array(
        'clientid' 	=> $vars['userid'],
        'stats'		=> false
    );

    return localAPI('getClientsDetails', $query_params, $whmcs_admin);
}

function paghiper_getClientCustomFields($vars, $gateway_config) {

    $clientCustomFields = [];

    if(array_key_exists('custtype', $vars) && $vars['custtype'] == 'existing') {

        $client_details = paghiper_getClientDetails($vars, $gateway_config);

        foreach($client_details["customfields"] as $key => $value){
            $clientCustomFields[$value['id']] = $value['value'];
        }

    } else {

        foreach($vars["customfield"] as $key => $value){
            $clientCustomFields[$key] = $value;
        }

    }
    
    if(count($taxIdFields) > 1) {
        $clientTaxIds[] = $clientCustomFields[$taxIdFields[0]];
        $clientTaxIds[] = $clientCustomFields[$taxIdFields[1]];
    } else {
        $clientTaxIds[] = $clientCustomFields[$taxIdFields[0]];
    }

    $isValidTaxId = false;
    foreach($clientTaxIds as $clientTaxId) {
        if(paghiper_is_tax_id_valid($clientTaxId)) {
            $isValidTaxId = true;
            break 1;
        }
    }

    if(!$isValidTaxId) {

        if(array_key_exists('custtype', $vars) && $vars['custtype'] == 'existing') {
            return array('CPF/CNPJ inválido! Cheque seu cadastro.');
        } else {
            return array('CPF/CNPJ inválido!');
        }
    }
}

//add_hook("ClientDetailsValidation", 1, "paghiper_clientValidateTaxId");
add_hook("ShoppingCartValidateCheckout", 1, "paghiper_clientValidateTaxId");
<?php
/**
 * Valida informações de faturamento do cliente no check-out
 * 
 * @package    PagHiper e Boleto para WHMCS
 * @version    3.0.0
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Henrique Cruz
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2026, PagHiper
 * @link       https://www.paghiper.com/
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");

// PHP 5.x compatibility
if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
    $basedir = (function_exists('dirname')) ? dirname(__DIR__, 2) : realpath(__DIR__ . '/../..');
} else {
    $basedir = (function_exists('dirname') && function_exists('dirname_with_levels')) ? dirname_with_levels(__DIR__, 2) : realpath(__DIR__ . '/../..');
}

require_once($basedir . '/modules/gateways/paghiper/inc/helpers/gateway_functions.php');

function paghiper_clientValidateTaxId($vars) {
    if (array_key_exists('paymentmethod', $vars) && strpos($vars['paymentmethod'], "paghiper") !== false) {
        $gatewayConfig = getGatewayVariables($vars['paymentmethod']);
    } else {
        return;
    }

    if (empty($gatewayConfig['tax_id_validation']) || ($gatewayConfig['tax_id_validation'] != 'on' && $gatewayConfig['tax_id_validation'] != '1')) {
        return;
    }

    if (empty($gatewayConfig['cpf_cnpj'])) {
        return;
    }

    // Checamos o CPF/CNPJ novamente, para evitar problemas no checkout
    $taxIdFields = explode("|", $gatewayConfig['cpf_cnpj']);
    $clientCustomFields = [];
    $clientTaxIds = [];

    if (array_key_exists('custtype', $vars) && $vars['custtype'] == 'existing') {
        $whmcsAdmin = paghiper_autoSelectAdminUser($gatewayConfig);

        $query_params = array(
            'clientid' 	=> $vars['userid'],
            'stats'		=> false
        );

        $client_details = localAPI('getClientsDetails', $query_params, $whmcsAdmin);

        foreach ($client_details["customfields"] as $key => $value) {
            $clientCustomFields[$value['id']] = $value['value'];
        }
    } else {
        if (isset($vars["customfield"]) && is_array($vars["customfield"])) {
            foreach ($vars["customfield"] as $key => $value) {
                $clientCustomFields[$key] = $value;
            }
        }
    }

    if (count($taxIdFields) > 1) {
        $clientTaxIds[] = isset($clientCustomFields[$taxIdFields[0]]) ? $clientCustomFields[$taxIdFields[0]] : '';
        $clientTaxIds[] = isset($clientCustomFields[$taxIdFields[1]]) ? $clientCustomFields[$taxIdFields[1]] : '';
    } else {
        $clientTaxIds[] = isset($clientCustomFields[$taxIdFields[0]]) ? $clientCustomFields[$taxIdFields[0]] : '';
    }

    $isValidTaxId = false;
    foreach ($clientTaxIds as $clientTaxId) {
        if (!empty($clientTaxId) && paghiper_is_tax_id_valid($clientTaxId)) {
            $isValidTaxId = true;
            break;
        }
    }

    if (!$isValidTaxId) {
        if (array_key_exists('custtype', $vars) && $vars['custtype'] == 'existing') {
            return array('CPF/CNPJ inválido! Cheque seu cadastro.');
        } else {
            return array('CPF/CNPJ inválido!');
        }
    }
}

//add_hook("ClientDetailsValidation", 1, "paghiper_clientValidateTaxId");
add_hook("ShoppingCartValidateCheckout", 1, "paghiper_clientValidateTaxId");
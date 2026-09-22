<?php
/**
 * Valida informações de faturamento do cliente no check-out
 * 
 * @package    PagHiper e Boleto para WHMCS
 * @version    3.1.1
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
        $gateway_config = getGatewayVariables($vars['paymentmethod']);
    } else {
        return;
    }

    if (empty($gateway_config['tax_id_validation']) || ($gateway_config['tax_id_validation'] != 'on' && $gateway_config['tax_id_validation'] != '1')) {
        return;
    }

    if (empty($gateway_config['cpf_cnpj'])) {
        return;
    }

    // Checamos o CPF/CNPJ novamente, para evitar problemas no checkout
    $tax_id_fields = explode("|", $gateway_config['cpf_cnpj']);
    $client_custom_fields = [];
    $client_tax_ids = [];

    if (array_key_exists('custtype', $vars) && $vars['custtype'] == 'existing') {
        $client = \WHMCS\User\Client::find($vars['userid']);
        if ($client) {
            foreach ($client->customFieldValues as $cf) {
                $client_custom_fields[$cf->fieldid] = $cf->value;
            }
        }
    } else {
        if (isset($vars["customfield"]) && is_array($vars["customfield"])) {
            foreach ($vars["customfield"] as $key => $value) {
                $client_custom_fields[$key] = $value;
            }
        }
    }

    if (count($tax_id_fields) > 1) {
        $client_tax_ids[] = isset($client_custom_fields[$tax_id_fields[0]]) ? $client_custom_fields[$tax_id_fields[0]] : '';
        $client_tax_ids[] = isset($client_custom_fields[$tax_id_fields[1]]) ? $client_custom_fields[$tax_id_fields[1]] : '';
    } else {
        $client_tax_ids[] = isset($client_custom_fields[$tax_id_fields[0]]) ? $client_custom_fields[$tax_id_fields[0]] : '';
    }

    $is_valid_tax_id = false;
    foreach ($client_tax_ids as $client_tax_id) {
        if (!empty($client_tax_id) && paghiper_is_tax_id_valid($client_tax_id)) {
            $is_valid_tax_id = true;
            break;
        }
    }

    if (!$is_valid_tax_id) {
        if (array_key_exists('custtype', $vars) && $vars['custtype'] == 'existing') {
            return array('CPF/CNPJ inválido! Cheque seu cadastro.');
        } else {
            return array('CPF/CNPJ inválido!');
        }
    }
}

//add_hook("ClientDetailsValidation", 1, "paghiper_clientValidateTaxId");
add_hook("ShoppingCartValidateCheckout", 1, "paghiper_clientValidateTaxId");
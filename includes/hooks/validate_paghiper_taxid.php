<?php
/**
 * Valida informações de faturamento do cliente no check-out
 * @author     Henrique Cruz
 * @copyright  Copyright (c) 2019-2021 https://www.paghiper.com/
 */

require_once(dirname(__FILE__) . '/../../modules/gateways/paghiper/inc/helpers/gateway_functions.php');

function paghiper_clientValidateTaxId($vars){

    if(array_key_exists('paymentmethod', $vars) && strpos($vars['paymentmethod'], "paghiper") !== false) {
        $gatewayConfig = getGatewayVariables($vars['paymentmethod']);
    } else {
        return;
    }

    // Checamos o CPF/CNPJ novamente, para evitar problemas no checkout
    $taxIdFields = explode("|", $gatewayConfig['cpf_cnpj']);
    $clientCustomFields = [];
    $clientTaxIds = [];

    if(array_key_exists('custtype', $vars) && $vars['custtype'] == 'existing') {

        $gateway_admin = $gatewayConfig['admin'];
        $backup_admin = array_shift(mysql_fetch_array(mysql_query("SELECT username FROM tbladmins LIMIT 1")));
    
        // Se o usuário admin estiver vazio nas configurações, usamos o padrão
        $whmcsAdmin = (
            (empty(trim($gateway_admin))) ? 
    
            // Caso não tenha um valor para usarmos, pegamos o primeiro admin disponível na tabela
            $backup_admin : 
    
                // Caso tenha, usamos o preenchido
                (
                    empty(array_shift(mysql_fetch_array(mysql_query("SELECT username FROM tbladmins WHERE username = '$gateway_admin' LIMIT 1"))))) ?
                    $backup_admin :
                    trim($GATEWAY['admin']
                )
    
        );

        $query_params = array(
            'clientid' 	=> $vars['userid'],
            'stats'		=> false
        );

        $client_details = localAPI('getClientsDetails', $query_params, $whmcsAdmin);

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
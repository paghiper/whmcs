<?php
/**
 * Valida informações de faturamento do cliente no check-out
 * 
 * @package    PagHiper e Boleto para WHMCS
 * @version    2.3
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Henrique Cruz
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2021, PagHiper
 * @link       https://www.paghiper.com/
 */

require_once(dirname(__FILE__) . '/../../modules/gateways/paghiper/inc/helpers/gateway_functions.php');

function paghiper_getClientDetails($vars, $gatewayConfig) {

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
                trim($gatewayConfig['admin']
            )

    );

    $query_params = array(
        'clientid' 	=> $vars['userid'],
        'stats'		=> false
    );

    return localAPI('getClientsDetails', $query_params, $whmcsAdmin);
}

function paghiper_getClientCustomFields($vars, $gatewayConfig) {

    $clientCustomFields = [];

    if(array_key_exists('custtype', $vars) && $vars['custtype'] == 'existing') {

        $client_details = paghiper_getClientDetails($vars, $gatewayConfig);

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
<?php
/**
 * Cancela boletos bancários não-pagos, atrelados a uma fatura cancelada ou paga.
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

function paghiper_cancel_paghiper_slips($vars) {
	
	// PHP 5.x compatibility
	if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
		$basedir = (function_exists('dirname')) ? dirname(__DIR__, 2) : realpath(__DIR__ . '/../..');
	} else {
		$basedir = (function_exists('dirname') && function_exists('dirname_with_levels')) ? dirname_with_levels(__DIR__, 2) : realpath(__DIR__ . '/../..');
	}

	//require_once ("../init.php");
	require_once($basedir . '/modules/gateways/paghiper/inc/helpers/gateway_functions.php');
	$invoice_id = $vars['invoiceid'];

	// Initialise gateway configuration
	$gatewayConfig = getGatewayVariables("paghiper");

	if(!empty($gatewayConfig)) {

		// Get the variables we'll be using for the transactions
		$account_token = trim($gatewayConfig['token']);
		$account_api_key = trim($gatewayConfig['api_key']);

		// Query database for active transactions
		$transactions = mysql_query("SELECT transaction_id, status FROM mod_paghiper WHERE order_id = '{$invoice_id}' AND status = 'pending'");

		// Loop and cancel each and every one of them
		while($transaction = mysql_fetch_array($transactions)) {

			// Define data for our API transaction
			$paghiper_data = array(
				'apiKey'			=> $account_api_key,
				'token'				=> $account_token,
				'status'			=> 'canceled',
				'transaction_id'	=> $transaction['transaction_id'] 
			);

			// Agora vamos buscar o status da transação diretamente na PagHiper, usando a API.
			$url = "https://api.paghiper.com/transaction/cancel/";
			$data_post = json_encode( $paghiper_data );
			$mediaType = "application/json"; // formato da requisição
			$charset = "UTF-8";
			$headers = array();
			$headers[] = "Accept: ".$mediaType;
			$headers[] = "Accept-Charset: ".$charset;
			$headers[] = "Accept-Encoding: ".$mediaType;
			$headers[] = "Content-Type: ".$mediaType.";charset=".$charset;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
			$result = curl_exec($ch);
	
			// captura o http code
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			// Agora processamos a notificação, que recebemos em formato JSON
			$json = json_decode($result, true);

			if($httpCode == 201) {
				logTransaction($GATEWAY["name"],array('post' => $paghiper_data, 'json' => $json), "Boleto adicional cancelado com sucesso. Transação #{$transaction['transaction_id']}"); 
				paghiper_log_status_to_db('canceled', $transaction['transaction_id']);
			} else {
				// Logamos um erro pra controle
				logTransaction($GATEWAY["name"],array('post' => $paghiper_data, 'json' => $json), "Não foi possível cancelar o boleto"); 
				paghiper_log_status_to_db('force_canceled', $transaction['transaction_id']);
			}

		}
	}

	return true;
}

//add_hook('AddInvoicePayment', 1, 'paghiper_cancel_paghiper_slips');
add_hook('InvoiceCancelled', 1, 'paghiper_cancel_paghiper_slips');
add_hook('InvoicePaid', 1, 'paghiper_cancel_paghiper_slips');
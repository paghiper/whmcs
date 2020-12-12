<?php
/**
 * PagHiper - Módulo oficial para integração com WHMCS
 * 
 * @package    PagHiper para WHMCS
 * @version    2.1
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Desenvolvido e mantido Henrique Cruz - https://henriquecruz.com.br/
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2020, PagHiper
 * @link       https://www.paghiper.com/
 */

function get_customfield_id() {
    $fields = mysql_query("SELECT id, fieldname FROM tblcustomfields WHERE type = 'client';");
    if (!$fields) {
        return '<br><br>Erro geral no banco de dados';
    } elseif (mysql_num_rows($fields) >= 1) {
        $tutorial = '<br><br>Para sua comodidade, listamos abaixo os campos que podem ser usados e seus IDs. Basta pegar o ID e preencher acima. <ul>';
        while ($field = mysql_fetch_assoc($fields)) {
            $tutorial .= '<li><strong>ID do Campo: ';
            $tutorial .= $field['id'];
            $tutorial .= '</strong> | Nome: ';
            $tutorial .= htmlentities($field['fieldname']);
            $tutorial .= '</li>';
        }
        $tutorial .= '</ul>';
        $tutorial .= '<br>Caso use campos separados para CPF e CNPJ, coloque o campo CPF seguido pelo de CNPJ separado por uma barra vertical.<br>(ex.: 15|42)';
        $tutorial .= '<br>Caso o campo de CPF não esteja disponível, <strong><a href="https://github.com/paghiper/whmcs/wiki/Criando-o-campo-de-CPF-CNPJ" target="_blank">acesse o tutorial clicando aqui</a></strong> e veja como pode criar o campo.';
       return $tutorial;
    } else {
        return '<br><br>Nenhum campo possível foi encontrado! Por favor <strong><a href="https://github.com/paghiper/whmcs/wiki/Criando-o-campo-de-CPF-CNPJ" target="_blank">acesse o tutorial clicando aqui</a></strong> e veja como pode criar o campo.';
    }
    
}

function add_to_invoice($invoice_id, $desc, $value, $whmcsAdmin) {

    $postData = array(
        'invoiceid'             => (int) $invoice_id,
        'newitemdescription'    => array('PAGHIPER: '. $desc),
        'newitemamount'         => array($value)
    );

    // Atualizamos a invoice com os valores novos
    $results = localAPI('UpdateInvoice',$postData,$whmcsAdmin);

}

function to_monetary($int) {
    return number_format ( $int, 2, '.', '' );
}

function log_status_to_db($status, $transaction_id) {

    $update = mysql_query("UPDATE mod_paghiper SET status = '$status' WHERE transaction_id = '$transaction_id';");
    if(!$update) {
        return false;
    }
    return true;
}

function fetch_remote_url($url) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output = curl_exec($ch);

    return $output;
}

function convert_to_numeric($str) {
    return preg_replace('/\D/', '', $str);
}

function query_scape_string($string) {
	if(function_exists('mysql_real_escape_string')) {
		return mysql_real_escape_string($string);
	}

	return mysql_escape_string($string);

}

function apply_custom_taxes($amount, $GATEWAY, $params = NULL){
    if(array_key_exists('amount', $params)) {
        $amount     = $params['amount'];
        $porcento   = $params['porcento'];
        $taxa       = $params['taxa'];
    } else {
        $porcento   = $GATEWAY['porcento'];
        $taxa       = $GATEWAY['taxa'];
    }
    return number_format(($amount+((($amount / 100) * $porcento) + $taxa)), 2, '.', ''); # Formato: ##.##
}

function check_if_subaccount($user_id, $email, $invoice_userid) {
    $query = "SELECT userid, id, email, permissions, invoiceemails FROM tblcontacts WHERE userid = '$user_id' AND email = '$email' LIMIT 1"; 
    $result = mysql_query($query);
    $user = mysql_fetch_array($result);

    $allow_invoices = ((strpos($user['permissions'], 'invoices') || $user['invoiceemails'] == 1) && $invoice_userid == $user['userid'] ? TRUE : FALSE);
    if($allow_invoices) {
        return $user['userid'];
    }
    return false;
}

function print_screen($ico, $title, $message) {
    global $systemurl;
    $code = '
        <!DOCTYPE html>
        <html>

        <head>
          <meta charset="utf-8">
          <title>'.$title.'</title>
          <meta name="author" content="">
          <meta name="description" content="">
          <meta name="viewport" content="width=device-width, initial-scale=1">
        </head>

        <body>
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,600" rel="stylesheet"> 

        <div class="container">
          <div>
            <img style="max-width: 200px;" src="'.$systemurl.'/modules/gateways/paghiper/assets/img/'.$ico.'">
            <h3>'.$title.'</h3>
            <p>'.$message.'</p>
          </div>
        </div>

        <style type="text/css">
            html, body {
              width: 100%;
              height: 100%;
              overflow: hidden;
            }
            * {
              font-family: Open Sans;
            }
            .container {
              display: table;
              width: 100%;
              height: 100%;
            }
            .container div {
              display: table-cell;
              vertical-align: middle;
              text-align: center;
            }
            .container div * {
              max-width: 90%;
              margin: 0px auto;
            }
        </style>
        </body>

        </html>';
return $code;

}

function generate_paghiper_billet($invoice, $params) {

    global $return_json;
	
	// Prepare variables that we'll be using during the process
	$postData    = array();
	
	// Data received from the invoice
	$total 				= $invoice['balance'];
	$due_date 			= $params['due_date'];

	// Data from the client
	$firstname			= $params['client_data']['firstname'];
	$lastname			= $params['client_data']['lastname'];
	$companyname		= $params['client_data']['companyname'];
	$email				= $params['client_data']['email'];
	$phone				= $params['client_data']['phonenumber'];
	$address1			= $params['client_data']['address1'];
	$address2			= $params['client_data']['address2'];
	$city   			= $params['client_data']['city'];
	$state   			= $params['client_data']['state'];
	$postcode			= $params['client_data']['postcode'];

	// Data
	$gateway_settings 	= $params['gateway_settings'];
	$notification_url 	= $params['notification_url'];
	$cpfcnpj 			= $gateway_settings['cpf_cnpj'];

	// Data received through function params
	$invoice_id			= $invoice['invoiceid'];
	$client_id 			= $invoice['userid'];

    // Checamos se o campo é composto ou simples
    if(strpos($cpfcnpj, '|')) {
        // Se composto, pegamos ambos os campos
        $fields = explode('|', $cpfcnpj);

        $i = 0;

        foreach($fields as $field) {
            $result  = mysql_fetch_array(mysql_query("SELECT * FROM tblcustomfieldsvalues WHERE relid = '$client_id' and fieldid = '".trim($field)."'"));
            ($i == 0) ? $cpf = trim($result["value"]) : $cnpj = trim($result["value"]);
            if($i == 1) { break; }
            $i++;
        }

    } else {
        // Se simples, pegamos somente o que temos
		$cpf_cnpj     = trim(array_shift(mysql_fetch_array(mysql_query("SELECT value FROM tblcustomfieldsvalues WHERE relid = '$client_id' and fieldid = '$cpfcnpj'"))));
		if(strlen($cpf_cnpj) > 11) {
			$cnpj = $cpf_cnpj;
		} else {
			$cpf = $cpf_cnpj;
		}
    }


    // Aplicamos as taxas do gateway sobre o total
    $total = apply_custom_taxes($total, $gateway_settings, $params);
    
    // Preparate data to send
    $paghiper_data = array(
       "apiKey"                         => $gateway_settings['api_key'],
       "partners_id"                    => "12WIT2XD",
       "order_id"                       => $invoice_id,

       // Informações para a criação e liquidação da fatura
       "notification_url"               => $notification_url,
       "days_due_date"                  => $due_date,
       'type_bank_slip'                 => 'boletoA4',

       // Dados da fatura
       'items'                          =>  array(
                                                array(
                                                    'item_id'       => $invoice_id,
                                                    'description'   => 'Fatura #'.$invoice_id,
                                                    'price_cents'   => convert_to_numeric($total),
                                                    'quantity'      => 1
                                                ),
                                            ),

       // Dados do cliente
       "payer_email"                    => $email,
       "payer_name"                     => $firstname . ' ' . $lastname,
       "payer_phone"                    => convert_to_numeric($phone),
       "payer_street"                   => $address1,
       "payer_complement"               => $address2,
       "payer_city"                     => $city,
       "payer_state"                    => $state,
       "payer_zip_code"                 => $postcode,
	);

    // Checa se incluimos dados CPF ou CNPJ no post
    if((isset($cpf) && !empty($cpf) && $cpf != "on file") || (isset($cnpj) && !empty($cnpj) && $cnpj != "on file")) {
        if(isset($cnpj) && !empty($cnpj)) {
            if(isset($companyname) && !empty($companyname)) {
                $paghiper_data["payer_name"] = $companyname;
            }
            $paghiper_data["payer_cpf_cnpj"] = substr(trim(str_replace(array('+','-'), '', filter_var($cnpj, FILTER_SANITIZE_NUMBER_INT))), -14);
        } else {
            $paghiper_data["payer_cpf_cnpj"] = substr(trim(str_replace(array('+','-'), '', filter_var($cpf, FILTER_SANITIZE_NUMBER_INT))), -15);
        }
    } elseif(!isset($cpfcnpj) || $cpfcnpj == '') {
        logTransaction($gateway_settings["name"],$_POST,"Boleto não exibido. Você não definiu os campos de CPF/CNPJ");
    } elseif(!isset($cpf_cnpj) || $cpf_cnpj == '' || (empty($cpf) && empty($cnpj))) {
        logTransaction($gateway_settings["name"],$_POST,"Boleto não exibido. CPF/CNPJ do cliente não foi informado");
    } else {
        logTransaction($gateway_settings["name"],$_POST,"Boleto não exibido. Erro indefinido");
    }

    // Checamos os valores booleanos, 1 por 1
    // Dados do boleto
    $additional_config_boolean = array(
        'fixed_description'             => $gateway_settings['fixed_description'],
        'per_day_interest'              => $gateway_settings['per_day_interest'],
    );

    foreach($additional_config_boolean as $k => $v) {
        if($v === TRUE || $v === FALSE) {
            $paghiper_data[$k] = $v;
        } elseif($v == 'on') {
            $paghiper_data[$k] = TRUE;
        } else {
            $paghiper_data[$k] = FALSE;
        }
    }

    $discount_config = (!empty($gateway_settings['early_payment_discounts_cents'])) ? ltrim(preg_replace('/\D/', '', $gateway_settings['early_payment_discounts_cents']), 0) : '';
    $discount_value = (!empty($discount_config)) ? convert_to_numeric( number_format($total * (($discount_config > 99) ? 99 / 100 : $discount_config / 100), 2, '.', '' ), 2, '.', '' ) : '';

    $additional_config_text = array(
        'early_payment_discounts_days'  => $gateway_settings['early_payment_discounts_days'],
        'early_payment_discounts_cents' => $discount_value,
        'open_after_day_due'            => $gateway_settings['open_after_day_due'],
        'late_payment_fine'             => $gateway_settings['late_payment_fine'],
        'open_after_day_due'            => $gateway_settings['open_after_day_due'],
    );

    foreach($additional_config_text as $k => $v) {
        if(!empty($v)) {
            $paghiper_data[$k] = convert_to_numeric($v);
        }
    }

	$data_post = json_encode( $paghiper_data );

    $url = (isset($_GET) && array_key_exists('pix', $_GET)) ? "https://pix.paghiper.com/invoice/create/" : "https://api.paghiper.com/transaction/create/";
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
	$json = json_decode($result, true);

    // captura o http code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

    // CÓDIGO 201 SIGNIFICA QUE O BOLETO FOI GERADO COM SUCESSO
    if($httpCode == 201) {

		// Exemplo de como capturar a resposta json
		$transaction_type 	= ((isset($_GET) && array_key_exists('pix', $_GET))) ? 'pix' : 'billet';
        $transaction_id 	= (array_key_exists('pix_create_request', $json)) ? $json['pix_create_request']['transaction_id'] : $json['create_request']['transaction_id'];
        $order_id 			= (array_key_exists('pix_create_request', $json)) ? $json['pix_create_request']['order_id'] : $json['create_request']['order_id'];
        $due_date 			= (array_key_exists('pix_create_request', $json)) ? $json['pix_create_request']['due_date'] : $json['create_request']['due_date'];
        $status 			= (array_key_exists('pix_create_request', $json)) ? $json['pix_create_request']['status'] : $json['create_request']['status'];
        $url_slip 			= (array_key_exists('pix_create_request', $json)) ? null : $json['create_request']['bank_slip']['url_slip'];
        $url_slip_pdf 		= (array_key_exists('pix_create_request', $json)) ? null : $json['create_request']['bank_slip']['url_slip_pdf'];
        $digitable_line 	= (array_key_exists('pix_create_request', $json)) ? null : $json['create_request']['bank_slip']['digitable_line'];
		$open_after_day_due = $gateway_settings['open_after_day_due'];
		
		$qrcode_base64 		= (array_key_exists('pix_create_request', $json)) ? $json['pix_create_request']['pix_code']['qrcode_base64'] : null;
		$qrcode_image_url 	= (array_key_exists('pix_create_request', $json)) ? $json['pix_create_request']['pix_code']['qrcode_image_url'] : null;
		$emv 				= (array_key_exists('pix_create_request', $json)) ? $json['pix_create_request']['pix_code']['emv'] : null;
		$bacen_url 			= (array_key_exists('pix_create_request', $json)) ? $json['pix_create_request']['pix_code']['bacen_url'] : null;
		$pix_url 			= (array_key_exists('pix_create_request', $json)) ? $json['pix_create_request']['pix_code']['pix_url'] : null;

		$slip_value = $total;

        try {

            $sql = "INSERT INTO mod_paghiper (transaction_type,transaction_id,order_id,due_date,status,url_slip,url_slip_pdf,digitable_line,open_after_day_due,slip_value,qrcode_base64,qrcode_image_url,emv,bacen_url,pix_url) VALUES ('$transaction_type', '$transaction_id','$order_id','$due_date','$status','$url_slip','$url_slip_pdf','$digitable_line','$open_after_day_due','$slip_value','$qrcode_base64','$qrcode_image_url','$emv','$bacen_url','$pix_url');";

            $query = full_query($sql);
        } catch (Exception $e) {
            logTransaction($GATEWAY["name"],array('json' => $json, 'query' => $sql, 'query_result' => $query, 'exception' => $e),"Não foi possível inserir o boleto no banco de dados. Por favor entre em contato com o suporte.");
        }


        if($return_json) {
            header('Content-Type: application/json');
            return (array_key_exists('pix_create_request', $json)) ? json_encode($json['pix_create_request']) : json_encode($json['create_request']);
		}


		if(!empty($qrcode_image_url)) {
			$output = "<img src='{$qrcode_image_url}'>";
		} else {
			$output = fetch_remote_url($billet_url);
		}

        return $output;

    } else {

        // Não foi possível solicitar o boleto.

        if(!$return_json) { 

            $ico = 'boleto-cancelled.png';
            $title = 'Ops! Não foi possível emitir o boleto bancário.';
            if(isset($json['create_request']['response_message']) && $json['create_request']['response_message'] == 'payer_cpf_cnpj invalido') {
                $message = 'CPF/CNPJ inválido no cadastro. Por favor verifique os dados e tente novamente.';
            } else {
                $message = 'Por favor entre em contato com o suporte.';
            }
            
            echo print_screen($ico, $title, $message);
        } else {
            die('Não foi possível emitir o boleto.');
        }

        logTransaction($GATEWAY["name"],array('json' => $json, 'post' => $_POST),"Não foi possível solicitar o boleto.");
        return false;
    }
 
}

function check_table() {
    $checktable = full_query("SHOW TABLES LIKE 'mod_paghiper'");
    $table_exists = mysql_num_rows($checktable) > 0;

    if($table_exists) {
        $table_columns = full_query("SHOW COLUMNS FROM `mod_paghiper` LIKE 'transaction_id';");
        if(mysql_num_rows($table_columns) == 0) {
            $delete_table = full_query("DROP TABLE mod_paghiper;");
            if($delete_table) {
                create_paghiper_table();
            }
        }

        $slip_value = full_query("SHOW COLUMNS FROM `mod_paghiper` WHERE `field` = 'slip_value' AND `type` = 'decimal(11,2)'");
        if(mysql_num_rows($slip_value) == 0) {
            $alter_table = full_query("ALTER TABLE `mod_paghiper` CHANGE `slip_value` `slip_value` DECIMAL(11,2) NULL DEFAULT NULL;");
            if(!$alter_table) {
                logTransaction($GATEWAY["name"],$_POST,"Não foi possível alterar o formato de dados da coluna slip_value. Por favor altere manualmente para decimal(11,2).");
            }
        }

        $transaction_type = full_query("SHOW COLUMNS FROM `mod_paghiper` WHERE `field` = 'transaction_type'");
        if(mysql_num_rows($transaction_type) == 0) {
            $alter_table = full_query("ALTER TABLE `mod_paghiper` ADD COLUMN transaction_type varchar(45) DEFAULT NULL AFTER id, ADD COLUMN qrcode_base64 varchar(255) DEFAULT NULL, ADD COLUMN qrcode_image_url varchar(255) DEFAULT NULL, ADD COLUMN emv varchar(255) DEFAULT NULL, ADD COLUMN pix_url varchar(255) DEFAULT NULL, ADD COLUMN bacen_url varchar(255) DEFAULT NULL;");
            if(!$alter_table) {
                logTransaction($GATEWAY["name"],$_POST,"Não foi possível adicionar os campos para suporte ao PIX. Por favor cheque se o usuário MySQL tem permissões para alterar a tabela mod_paghiper");
            }
        }
    } else {
        create_paghiper_table();
    }


    if ($result = full_query("SHOW TABLES LIKE 'mod_paghiper'")) {
        if($result->num_rows == 1) {
            echo "Table exists";
        }
    }
    else {
        echo "Table does not exist";
    }
}

function create_paghiper_table() {
    $table_create = full_query("CREATE TABLE IF NOT EXISTS `mod_paghiper` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
		  `transaction_type` varchar(45) DEFAULT NULL,
          `transaction_id` varchar(16) NOT NULL,
          `order_id` int(11) NOT NULL,
          `due_date` date,
          `status` varchar(45) NOT NULL,
          `url_slip` varchar(255) DEFAULT NULL,
          `url_slip_pdf` varchar(255) DEFAULT NULL,
          `digitable_line` varchar(54) DEFAULT NULL,
          `open_after_day_due` int(2) DEFAULT NULL,
          `slip_value` decimal(11,2) DEFAULT NULL,
		  `qrcode_base64` varchar(255) DEFAULT NULL,
		  `emv` varchar(255) DEFAULT NULL,
		  `pix_url` varchar(255) DEFAULT NULL,
		  `bacen_url` varchar(255) DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `transaction_id` (`transaction_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

    if(!$table_create) {
        logTransaction($GATEWAY["name"],$_POST,"Não foi possível criar o banco de dados para armazenamento das faturas.");
        return false;
    } else {
        logTransaction($GATEWAY["name"],$_POST,"Banco de dados criado com sucesso");
        return true;
    }
}
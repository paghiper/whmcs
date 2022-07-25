<?php
/**
 * PagHiper - Módulo oficial para integração com WHMCS
 * 
 * @package    PagHiper para WHMCS
 * @version    2.3
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Desenvolvido e mantido Henrique Cruz - https://henriquecruz.com.br/
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2021, PagHiper
 * @link       https://www.paghiper.com/
 */

// Nenhuma das funções foi executada, então o script foi acessado diretamente.
if (!defined("WHMCS")) {
    
    header("access-control-allow-origin: *");

    // Inicializar WHMCS, carregar o gateway e a fatura.
    require_once ("../../init.php");
    $whmcs->load_function("gateway");
    $whmcs->load_function("invoice");

    // Initialize module settings
	$gateway_code = ($is_pix) ? "paghiper_pix" : "paghiper"; 
    $GATEWAY = getGatewayVariables($gateway_code);

    // Define variáveis para configurações do gateway
    $gateway_name = $GATEWAY["name"];
    $account_email = trim($GATEWAY["email"]);
    $account_token = trim($GATEWAY['token']);
    $account_api_key = trim($GATEWAY['api_key']);

    // Vamos precisar pegar a URL do sistema usando métodos alternativos. A variável $params não está disponível nesse momento.
    $systemurl = rtrim(\App::getSystemUrl(),"/");

    $gateway_admin = $GATEWAY['admin'];
	$backup_admin = array_shift(mysql_fetch_array(mysql_query("SELECT username FROM tbladmins LIMIT 1")));

    // Se o usuário admin estiver vazio nas configurações, usamos o padrão
    $whmcsAdmin = (empty(trim($gateway_admin))) ? 

        // Caso não tenha um valor para usarmos, pegamos o primeiro admin disponível na tabela
        $backup_admin : 

        // Caso tenha, usamos o preenchido
        ( empty(array_shift(mysql_fetch_array(mysql_query("SELECT username FROM tbladmins WHERE username = '$gateway_admin' LIMIT 1"))))) ?
            $backup_admin :
            trim($GATEWAY['admin'] );

    // Checamos se a tabela da PagHiper está pronta pra uso
    $custom_table = paghiper_check_table();

    // Se as condições baterem, estamos lidando com um post do checkout transparente.
    if(isset($_GET["invoiceid"])) {
        
        $user_id = intval($_GET["uuid"]);
		$user_email = paghiper_query_scape_string($_GET["mail"]);

        $return_json = (isset($_GET['json']) && $_GET['json'] == 1) ? TRUE : FALSE;

        // Pegamos a fatura no banco de dados
        $getinvoice = 'getinvoice';
        $getinvoiceid['invoiceid'] = intval($_GET["invoiceid"]);
        $invoice = localAPI($getinvoice,$getinvoiceid,$whmcsAdmin);

        $issue_all_config = (int) $GATEWAY['issue_all'];

		$issue_all = ( $issue_all_config === 1 || $issue_all_config === 0 ) ? $issue_all_config : 1;

        if($invoice['paymentmethod'] !== $gateway_code && $issue_all == 0) {

                // Mostrar tela de boleto indisponível
                $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                $title = (($is_pix) ? 'PIX' : 'Boleto bancário') . ' não disponível para essa fatura!';
                $message = 'O método de pagamento escolhido para esta fatura não é ' . (($is_pix) ? 'PIX' : 'boleto bancário') . '. Caso ache que isso é um erro, contate o suporte.';
                echo paghiper_print_screen($ico, $title, $message);
                exit();

        }

        // Checamos se a fatura está sendo exibida por um usuário de sub-conta
        if( paghiper_check_if_subaccount($user_id, $user_email, $invoice['userid'] ) == FALSE ) {
            if(intval($invoice['userid']) !== $user_id) {
                // ID não bate
                // Mostrar tela de boleto indisponível
                $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                $title = 'Desculpe!';
                $message = 'Você não está autorizado a ver este recurso. Caso ache que isso é um erro, contate o suporte.';
                echo paghiper_print_screen($ico, $title, $message);

                exit();
            } else {
                $query = "SELECT email FROM tblclients WHERE id = '$user_id' LIMIT 1"; 
                $result = mysql_query($query);
                $data = mysql_fetch_array($result);
                $email = $data[0]; 
                if($email !== $user_email) {

                    // Mostrar tela de boleto indisponível
                    $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                    $title = 'Desculpe!';
                    $message = 'Você não está autorizado a ver este recurso. Caso ache que isso é um erro, contate o suporte.';
                    echo paghiper_print_screen($ico, $title, $message);

                    exit;
                }
            }
        }

        // Process status screens accordingly to invoice status
        switch($invoice['status']) {
            case "Paid":

                // Mostrar tela de boleto pago
                $ico = ($is_pix) ? 'pix-ok.png' : 'billet-ok.png';
                $title = 'Fatura paga!';
                $message = 'Este '.(($is_pix) ? 'PIX' : 'boleto').' ja foi compensado no sistema e consta como pago.';
                echo paghiper_print_screen($ico, $title, $message);
                exit();

                break;
            case "Draft":

                // Mostrar tela de boleto indisponível
                $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                $title = 'Esta fatura ainda não está disponível!';
                $message = 'Este '.(($is_pix) ? 'PIX' : 'boleto').' ainda não está disponível. Caso acredite que seja um erro, por favor acione o suporte.';
                echo paghiper_print_screen($ico, $title, $message);
                exit();

                break;
            case "Unpaid":
                //

                break;
            case "Overdue":
                //

                break;
            case "Cancelled":

                // Mostrar tela de boleto indisponível
                $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                $title = 'Esta fatura foi cancelada!';
                $message = 'Este boleto foi cancelado. Caso acredite que seja um erro, por favor acione o suporte.';
                echo paghiper_print_screen($ico, $title, $message);
                exit();

                break;
            case "Refunded":

                // Mostrar tela de boleto indisponível
                $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                $title = 'Este boleto venceu!';
                $message = 'Este boleto foi estornado. Caso acredite que seja um erro, por favor acione o suporte.';
                echo paghiper_print_screen($ico, $title, $message);
                exit();

                break;
            case "Collections":
                break;
        }
  
        // Pegamos a data de vencimento e a data de hoje
        $invoiceDuedate = $invoice['duedate']; // Data de vencimento da fatura
        $dataHoje = date('Y-m-d'); // Data de Hoje

        // Lógica: Checar se um boleto ja foi emitido pra essa fatura
        $order_id = $invoice['invoiceid'];
        $invoice_total = paghiper_apply_custom_taxes((float) $invoice['balance'], $GATEWAY);
        $invoice_balance = $invoice_total;

        foreach($invoice['items']['item'] as $invoice_key => $invoice_item) {

            if($invoice_item['type'] == 'LateFee') {
                $invoice_balance -= (float) $invoice_item['amount'];
            }

        }

        $transaction_type = ($is_pix) ? 'pix' : 'billet';
        $sql = (!$is_pix) ? 
            "SELECT * FROM mod_paghiper WHERE (transaction_type = '{$transaction_type}' OR transaction_type IS NULL) AND order_id = '{$order_id}' AND status = 'pending' AND (slip_value = '{$invoice_total}' OR slip_value = '{$invoice_balance}') AND ('{$dataHoje}' <= due_date OR '{$dataHoje}' <= DATE_ADD('{$invoiceDuedate}', INTERVAL (open_after_day_due) DAY)) ORDER BY ABS( DATEDIFF( due_date, '{$dataHoje}' ) ) ASC LIMIT 1" : 
            "SELECT * FROM mod_paghiper WHERE (transaction_type = '{$transaction_type}' OR transaction_type IS NULL) AND order_id = '{$order_id}' AND status = 'pending' AND (slip_value = '{$invoice_total}' OR slip_value = '{$invoice_balance}') AND '{$dataHoje}' <= due_date ORDER BY ABS( DATEDIFF( due_date, '{$dataHoje}' ) ) ASC LIMIT 1";
        $billet = mysql_fetch_array(mysql_query($sql), MYSQL_ASSOC);

        if(!empty($billet)) {
            $due_date           = $billet['due_date'];
            $grace_days         = $billet['open_after_day_due'];
            $billet_url         = $billet['url_slip'];
            $qrcode_image_url   = $billet['qrcode_image_url'];
            $billet_value       = $billet['slip_value'];
            $emv                = $billet['emv'];
        }

        // Só re-emitimos a fatura se os valores forem diferentes, se limite para pagamento ja tiver expirado (somando os dias de tolerência) e se o status for não-pago.
        if( 
            (
                // Caso nenhum boleto tenha sido emitido
                empty($billet) || 
                // Caso não haja URL de boleto disponível no banco
                (empty($billet_url) && empty($qrcode_image_url)) ||
                // Caso o vencimento esteja no futuro mas for diferente do definido na fatura
                (strtotime($invoiceDuedate) > strtotime(date('Y-m-d')) && $due_date !== $invoiceDuedate)
            ) 
            && $invoice['status'] == 'Unpaid'
        ) {

            $sql = "SELECT * FROM mod_paghiper WHERE order_id = '{$order_id}' AND status = 'reserved' AND (slip_value = '{$invoice_total}' OR slip_value = '{$invoice_balance}') ORDER BY due_date DESC LIMIT 1;";
            $reserved_billet = mysql_fetch_array(mysql_query($sql), MYSQL_ASSOC);
            if(!empty($reserved_billet)) {

                $ico = ($is_pix) ? 'pix-reserved.png' : 'billet-reserved.png';
                $title = 'Pagamento pré-confirmado.';
                $message = 'Este '.(($is_pix) ? 'PIX' : 'boleto').' teve o pagamento pré-confirmado e está aguardando compensação bancária. Por favor, aguarde.';
                echo paghiper_print_screen($ico, $title, $message);
                exit();

            }

            if(empty($billet) && empty($reserved_billet)) { 
                $reissue = TRUE;
            }
        }

        // Lógica: Checar se a data de vencimento + dias de tolerancia > data de hoje.
        if(isset($reissue) && $reissue) {

            // Pegamos a data de hoje, adicionamos um dia e usamos como nova data de vencimento
            $reissue_unpaid_cont = (int) $GATEWAY['reissue_unpaid'];
            $reissue_unpaid = (isset($reissue_unpaid_cont) && ($reissue_unpaid_cont === 0 || !empty($reissue_unpaid_cont))) ? $reissue_unpaid_cont : 1 ;
            if($reissue_unpaid == -1 && $dataHoje > $invoiceDuedate) {

                // Mostrar tela de boleto cancelado
                $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                $title = 'Este '.(($is_pix) ? 'PIX' : 'boleto').' venceu!';
                $message = 'Caso ja tenha efetuado o pagamento, aguarde o prazo de baixa bancária. Caso contrário, por favor acione o suporte.';
                echo paghiper_print_screen($ico, $title, $message);
                exit();

            }

            // Abortamos a exibição, caso valor seja menor que R$ 3
            if((int) $invoice['total'] < 3) {

                // Mostrar tela de boleto cancelado
                $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                $title = 'Não foi possível gerar o '.(($is_pix) ? 'PIX' : 'boleto').'!';
                $message = 'Este '.(($is_pix) ? 'PIX' : 'boleto').' tem o valor total inferior a R$3,00! Por favor, escolha outro método de pagamento.';
                echo paghiper_print_screen($ico, $title, $message);
                exit();

            }
        
            // Pegamos as datas que definimos anteriormente e transformamos em objeto Date do PHP
            $data1 = new DateTime($invoiceDuedate);
            $data2 = new DateTime($dataHoje);

            // Comparamos as datas para enviar o resultado a PagHiper. Isso é necessário pois o gateway pede o vencimento em número de dias no futuro, não como data.
            $intervalo = $data2->diff($data1); 
            $vencimentoBoleto = $intervalo->format('%R%a');

            if($vencimentoBoleto < 0) {
                $vencimentoBoleto = $reissue_unpaid;
            } else {
                $vencimentoBoleto = $intervalo->days;
            }

            // Calculamos a diferença de dias entre o dia de vencimento e os dias para aplicação de desconto.
            $discount_period = (int) $GATEWAY['early_payment_discounts_days'];
            if(!empty($discount_period) && $discount_period > 0) {
                
                if($vencimentoBoleto <= $discount_period || $vencimentoBoleto == 0) {
                    unset($GATEWAY['early_payment_discounts_days']);
                    unset($GATEWAY['early_payment_discounts_cents']);
                }
            }

            // Checamos uma ultima vez se o ID da fatura não veio vazio
            if($_GET["invoiceid"] == '') {
                exit("Fatura inexistente");
            } else {
                $invoiceid = intval($_GET["invoiceid"]);
				$urlRetorno = $systemurl.'/modules/gateways/';
				$urlRetorno .= ($is_pix) ? 'paghiper_pix.php' : 'paghiper.php';
                // Executamos o checkout transparente e printamos o resultado

                try {

                    $client_data = json_decode(html_entity_decode($_POST['client_data']), TRUE);

                    // Checamos se os dados do cliente vem de um checkout ou do perfil do cliente.
                    if( !empty($_POST) && is_array($client_data) && !empty($client_data) ) {
                        $client_details = $client_data;
                    } else {

                        $query_params = array(
                            'clientid' 	=> $invoice['userid'],
                            'stats'		=> false
                        );
                        $client_query = localAPI('getClientsDetails', $query_params, $whmcsAdmin);
                        $client_details = $client_query['client'];
                    }
                    
                    if(array_key_exists('currency_code', $client_details['client']) && ($client_details['client']['currency_code'] !== 'BRL' && $client_details['client']['currency_code'] !== 'R$')) {
                        $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                        $title = 'Método de pagamento indisponível para a moeda selecionada';
                        $message = 'Este método de pagamento só pode ser utilizado para pagamentos em R$ (BRL)<br>Caso creia que isso seja um erro, entre em contato com o suporte.';
                        echo paghiper_print_screen($ico, $title, $message);
                        exit();
                    }

                    $params = array(
						'client_data'		=> $client_details,
						'gateway_settings'	=> $GATEWAY,
                        'notification_url'	=> $urlRetorno,
                    	'due_date'			=> $vencimentoBoleto,
                        'format'			=> (($return_json) ? 'json' : 'html')
                    );
                    
                    //echo generate_paghiper_billet($params,$GATEWAY,$invoiceid,$urlRetorno,$vencimentoBoleto,$return_json);
                    echo generate_paghiper_billet($invoice, $params);

                } catch (Exception $e) {
                    echo 'Erro ao solicitar boleto: ',  $e->getMessage(), "\n";
                }
                
                exit;
            }

        } else {

            //url_slip;
            if($return_json) {
                header('Content-Type: application/json');
                echo json_encode($billet);
            } else {
                if(!empty($qrcode_image_url)) {
                    echo paghiper_print_screen($qrcode_image_url, null, null, array('is_pix' => true, 'invoice_id' => $order_id, 'payment_value' => $billet_value, 'pix_emv' => $emv));
                } else {
                    echo paghiper_fetch_remote_url($billet_url);
                }
            }
            

        }
        
    // Caso contrário, é um post do PagHiper.
    } else {

        // Checamos que tipo de post estamos tratando. Compara

        // Pegamos os campos enviados por POST. Vamos checar esses dados.
        $transaction_id     = $_POST['transaction_id'];
        $notification_id    = $_POST['notification_id'];

        $paghiper_data = array(
            'token'             => $account_token,
            'apiKey'            => $account_api_key,
            'transaction_id'    => $transaction_id,
            'notification_id'   => $notification_id
        );

        $billet = mysql_fetch_array(mysql_query("SELECT * FROM mod_paghiper WHERE transaction_id = '$transaction_id' ORDER BY due_date DESC LIMIT 1;"), MYSQL_ASSOC);
        $order_id = (empty($billet)) ? $_POST['idPlataforma'] : $billet['order_id'];

        if (!empty($_POST)) {

            // Resolvemos disputas entre notifications enviadas simultaneamente
            $request_bytes  = openssl_random_pseudo_bytes(16, $is_strong);
            $request_id     = bin2hex($request_bytes);
    
            $lock_id = paghiper_write_lock_id($request_id, $transaction_id);
            if(!$lock_id || !$is_strong) {
                $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                $title = 'Ops! Não foi possível processar a baixa do '.((!$is_pix) ? 'boleto bancário' : 'PIX').'.';
                $message = 'Não foi possível associar o Request ID a transação sendo processada.';
                
                echo paghiper_print_screen($ico, $title, $message);
                logTransaction($gateway_settings["name"],array('invoice_id' => $invoice_id, 'exception' => 'Failed to write Paghiper LockID'), sprintf("Não foi possível associar o ID de requisição ao %s.", ($is_pix) ? 'PIX' : 'boleto'));
                exit();
            }
    
            sleep(3);
    
            $current_lock_id = paghiper_get_lock_id($transaction_id);
    
            if(!$current_lock_id || ($current_lock_id !== $request_id)) {
    
                // Função que vamos usar na localAPI
                /*$addtransaction = "addtransaction";
                $transaction_suffix = '-Baixa-Duplicada-Evitada';
    
                // Log transaction
                $addtransvalues['userid'] = $results['userid'];
                $addtransvalues['invoiceid'] = $order_id;
                $addtransvalues['description'] = 'A nova versão do módulo resolveu com sucesso uma disputa de notificações de baixa simultâneas';
                $addtransvalues['amountin'] = '0.00';
                $addtransvalues['fees'] = '0.00';
                $addtransvalues['paymentmethod'] = $gateway_code;
                $addtransvalues['transid'] = $transaction_id . $transaction_suffix;
                $addtransvalues['date'] = date('d/m/Y');
                $addtransresults = localAPI($addtransaction,$addtransvalues,$whmcsAdmin);*/
    
                $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                $title = 'Ops! Ação não permitida.';
                $message = 'O thread ID desta notificação não está autorizado a ser processado.';
                
                echo paghiper_print_screen($ico, $title, $message);
                logTransaction($gateway_settings["name"],array('invoice_id' => $invoice_id, 'exception' => 'Thread ID error (0x004682)'), sprintf("O ID de requesição associado %s não é desta sessão. Erro 0x004682 \n\nThread ID: %s\nLock: %s", (($is_pix) ? 'PIX' : 'boleto'), $request_id, $current_lock_id));
                exit();
            } else {
                paghiper_write_lock_id(NULL, $transaction_id);
            }
        }

        // Agora vamos buscar o status da transação diretamente na PagHiper, usando a API.
        $url = ($is_pix) ? "https://pix.paghiper.com/invoice/notification/" : "https://api.paghiper.com/transaction/notification/";
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

        // Só executamos o bloco condicional abaixo se o post da PagHiper for confirmado.
        $request = $json['status_request'];


        $json = json_decode($result, true);

        if($request['result'] == 'reject') {

            // Logamos um erro pra controle
            logTransaction($GATEWAY["name"],array('post' => $_POST, 'json' => $json), "Notificação Inválida."); 

        } elseif($request['result'] == 'success') {

            $invoice_id = $order_id;

            $status             = $request['status'];
            $transaction_id     = $request['transaction_id'];
            $ammount_paid       = paghiper_to_monetary($request['value_cents_paid'] / 100);
            $transaction_fee    = paghiper_to_monetary($request['value_fee_cents'] / 100);

            // Pegamos a fatura como array e armazenamos na variável para uso posterior
            $command = "getinvoice";
            $values["invoiceid"] = $order_id;
            $results = localAPI($command,$values,$whmcsAdmin);
            
                // Função que vamos usar na localAPI
				$addtransaction = "addtransaction";
				
				$transaction_suffix = ($gateway_code == 'paghiper_pix') ? '-Transacao-Pix-Criada' : '-Boleto-Gerado';
                
                // Cliente fez emissão do boleto, logamos apenas como memorando
                if ($status == "pending" || $status == "Aguardando") {
                    $addtransvalues['userid'] = $results['userid'];
                    $addtransvalues['invoiceid'] = $order_id;
                    $addtransvalues['description'] = (($is_pix) ? 'PIX' : 'Boleto')." gerado aguardando pagamento.";
                    $addtransvalues['amountin'] = '0.00';
                    $addtransvalues['fees'] = '0.00';
                    $addtransvalues['paymentmethod'] = $gateway_code;
                    $addtransvalues['transid'] = $transaction_id . $transaction_suffix;
                    $addtransvalues['date'] = date('d/m/Y');
                    $addtransresults = localAPI($addtransaction,$addtransvalues,$whmcsAdmin);

                    // Salvamos as informações no log de transações do WHMCS
                    logTransaction($GATEWAY["name"],$_POST,"Aguardando o Pagamento");

                    // Logamos status no banco
                    paghiper_log_status_to_db($status, $transaction_id);

                // Transação foi reservada
                } elseif($status == "reserved") {

                    $addtransvalues['userid'] = $results['userid'];
                    $addtransvalues['invoiceid'] = $order_id;
                    $addtransvalues['description'] = "Pagto. pré-confirmado. Aguarde compensação.";
                    $addtransvalues['amountin'] = '0.00';
                    $addtransvalues['fees'] = '0.00';
                    $addtransvalues['paymentmethod'] = $gateway_code;
                    $addtransvalues['transid'] = $transaction_id.'-Pagto-Reservado';
                    $addtransvalues['date'] = date('d/m/Y');
                    $addtransresults = localAPI($addtransaction,$addtransvalues,$whmcsAdmin);

                    // Salvamos as informações no log de transações do WHMCS
                    logTransaction($GATEWAY["name"],$_POST,"Pagamento pré-confirmado");

                    //TODO: Implementar re-estabelecimento dos serviços ao pré-confirmar pagamento

                    // Logamos status no banco
                    paghiper_log_status_to_db($status, $transaction_id);
                    
                // Transação foi aprovada
                } elseif ($status == "paid" || $status == "Aprovado") {

                    // Essa função checa se a transação ja foi registrada no banco de dados. 
                    $checkTransId = checkCbTransID($transaction_id);

                    /**
                     * Infelizmente a função checkCbTransID não é totalmente confiável na versão 7 do WHMCS.
                     * Por conta disso, precisamos checar se a transação ja sofreu baixa no banco
                     */ 
                    $unpaid_transactions = mysql_query("SELECT transaction_id, status FROM mod_paghiper WHERE transaction_id = '{$transaction_id}' AND status = 'paid'");
                    if(mysql_num_rows($unpaid_transactions) >= 1) {
                        die('Notificação ja foi processada');
                    }

                    // Calcula a taxa cobrada pela PagHiper de maneira dinâmica e registra para uso no painel.
                    $fee = $transaction_fee;

                    // Logamos a transação no log de Gateways do WHMCS.
                    logTransaction($GATEWAY["name"],$request,"Transação Concluída");

                    // Logamos status no banco
                    paghiper_log_status_to_db($status, $transaction_id);

                    // Se estiver tudo certo, checamos se o valor pago é diferente do configurado na fatura
                    if($results['balance'] !== $ammount_paid) {

                        // Subtraimos valor de balanço do valor pago. Funciona tanto para desconto como acréscimo.
                        // Ex. 1: Valor pago | R$ 18 - R$ 20 (Balanço) = -R$ 2 [Desconto]
                        // Ex. 2: Valor pago | R$ 21 - R$ 20 (Balanço) = +R$ 1 [Multa]
                        $value = $ammount_paid - $results['balance'];

                        if($results['balance'] > $ammount_paid) {

                            // Conciliação: Desconto por antecipação (Valor de balanço da Invoice - Valor total pago)
                            $desc = ($is_pix) ? 'Desconto por pagamento por PIX' : 'Desconto por pagamento antecipado';
                            paghiper_add_to_invoice($invoice_id, $desc, $value, $whmcsAdmin);

                        } else {

                            // Conciliação: Juros e Multas = (Valor total pago - Valor contido na Invoice)
                            $desc = 'Juros e multa por atraso';
                            paghiper_add_to_invoice($invoice_id, $desc, $value, $whmcsAdmin);

                            // TODO: Implementar mensagem alternativa, caso valor adicional venha de taxas

                        }
                    }

                    // Registramos o pagamento e damos baixa na fatura
                    addInvoicePayment($invoice_id,$transaction_id,$ammount_paid,$fee,$gateway_code);

                // Transação Cancelada. 
                } else if ($status == "canceled" || $status == "Cancelado") {
                    // Boleto não foi pago, logamos apenas como memorando
                    logTransaction($GATEWAY["name"],$request,"Transação Cancelada");

                    // Logamos status no banco
                    paghiper_log_status_to_db($status, $transaction_id);
                }
                //TODO
                // Prever todos os tipos de retorno.

        } else {

            // Logamos um erro pra controle
            logTransaction($GATEWAY["name"],$json,"Falha ao buscar ID da transação no banco."); 

        }

        exit();

    }
}

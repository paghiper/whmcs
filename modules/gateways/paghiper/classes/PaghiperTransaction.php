<?php
/**
 * Classe responsável pela criação e resgate de transações
 * 
 * @package    PagHiper para WHMCS
 * @version    2.5.3
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Desenvolvido e mantido Henrique Cruz - https://henriquecruz.com.br/
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2025, PagHiper
 * @link       https://www.paghiper.com/
 */

use WHMCS\Database\Capsule;
use WHMCS\User\Client;
use WHMCS\Billing\Invoice;

class PaghiperTransaction {

    private $invoiceID,
            $invoiceData,
            $isPIX,
            $outputFormat,
            $reissueUnpaid,
            $whmcsVersion,
            $whmcsAdminUser,
            $systemURL,
            $transactionData,
            $transactionTotal,
            $isGatewayAvailable;

    function __construct( $transactionParams ) {

        // Pegamos as dependências necessárias para as operações dessa classe (core, gateway e invoice)
        require_once __DIR__ . '/../../../../init.php';
        require_once __DIR__ . '/../../../../includes/gatewayfunctions.php';
        require_once __DIR__ . '/../../../../includes/invoicefunctions.php';

        $this->invoiceID    = $transactionParams['invoiceID'];
        $this->outputFormat = array_key_exists('format', $transactionParams) ? $transactionParams['format'] : 'html';

        $this->isGatewayAvailable = true;
        try {

            // Pegamos a fatura no banco de dados
            $invoice = Invoice::find($this->invoiceID);
            $this->invoiceData = $invoice->getRawOriginal();
            $this->invoiceData['balance'] = $invoice->getBalanceAttribute();
            $this->invoiceData['items'] = $invoice->items()->get();

            // Variáveis básicas para nossa operação. Caso algo falhe aqui, não será possível inicializar o gateway.
            $this->gatewayName = $this->invoiceData['paymentmethod'];
            $this->isPIX       = ($this->gatewayName == 'paghiper_pix');

            // Saímos do fluxo, caso o método de pagamento não seja Paghiper.
            if(!str_contains($this->gatewayName, 'paghiper')) {
                $this->isGatewayAvailable = false;
            }

            // Pegamos as configurações do gateway e de sistema necessárias
            $this->gatewayConf      = getGatewayVariables($this->gatewayName);
            $this->systemURL        = rtrim(\App::getSystemUrl(),"/");
            $this->whmcsAdminUser   = paghiper_autoSelectAdminUser($this->gatewayConf);
            $this->reissueUnpaid    = $this->gatewayConf["reissue_unpaid"];
            $this->whmcsVersion     = App::getVersion()->getCasual();

        } catch(Exception $e) {

            logTransaction($this->gatewayConf["name"],array('error' => $e->getMessage(), 'transactionData' => $transactionParams, 'exception' => 'Failed to initialise payment gateway'),"Não foi possível inicializar o gateway.");
            $this->isGatewayAvailable = false;

        }
    
        // Checamos se a tabela da PagHiper está pronta pra uso
        if(!paghiper_check_table()) {
            switch ($this->outputFormat) {
                case 'json':
                    return json_encode([
                        'status'    => 400,
                        'error'     => 'mod_table_unavailable',
                        'message'   => 'Erro de banco de dados. Cheque os logs para mais detalhes.'
                    ]);
                    break;
                case 'html':

                    // Mostrar tela de boleto indisponível
                    $ico = ($this->isPIX) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                    $title = 'Não foi possível gerar seu '. (($this->isPIX) ? 'PIX' : 'Boleto bancário') . '.';
                    $message = 'Erro no armazenamento da transação. Por favor entre em contato com o suporte.';
                    echo paghiper_print_screen($ico, $title, $message);
                    exit();

                    break;
            }
                    
            $this->isGatewayAvailable = false;
        }
    }

    private function hasPayableStatus() {


        // Process status screens accordingly to invoice status
        switch($this->invoiceData['status']) {
            case "Paid":

                if($this->outputFormat == 'html') {

                    // Mostrar tela de boleto pago
                    $ico = ($this->isPIX) ? 'pix-ok.png' : 'billet-ok.png';
                    $title = 'Fatura paga!';
                    $message = 'Este '.(($this->isPIX) ? 'PIX' : 'boleto').' ja foi compensado no sistema e consta como pago.';
                    echo paghiper_print_screen($ico, $title, $message);
                    exit();
                    
                }

                return false;
                break;
            case "Draft":

                logTransaction($this->gatewayConf["name"],array('json' => $paghiper_data, 'transactionData' => $transactionParams, 'exception' => 'Transaction not published'),"Transação ainda em rascunho, não é possível criar a transação.");

                if($this->outputFormat == 'html') {
                    // Mostrar tela de boleto indisponível
                    $ico = ($this->isPIX) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                    $title = 'Esta fatura ainda não está disponível!';
                    $message = 'Este '.(($this->isPIX) ? 'PIX' : 'boleto').' ainda não está disponível. Caso acredite que seja um erro, por favor acione o suporte.';
                    echo paghiper_print_screen($ico, $title, $message);
                    exit();

                }

                return false;
                break;
            case "Unpaid":
                return true;
                break;
            case "Overdue":
                return true;
                break;
            case "Cancelled":

                logTransaction($this->gatewayConf["name"],array('json' => $paghiper_data, 'transactionData' => $transactionParams, 'exception' => 'Transaction cancelled'),"Transação cancelada, não é possível criar novas transações. Caso acredite que seja um erro, por favor acione o suporte.");

                if($this->outputFormat == 'html') {

                    // Mostrar tela de boleto indisponível
                    $ico = ($this->isPIX) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                    $title = 'Esta fatura foi cancelada!';
                    $message = 'Por consequencia, esse '.(($this->isPIX) ? 'PIX' : 'boleto').' também foi cancelado. Caso acredite que seja um erro, por favor acione o suporte.';
                    echo paghiper_print_screen($ico, $title, $message);
                    exit();

                }

                return false;
                break;
            case "Refunded":

                logTransaction($this->gatewayConf["name"],array('json' => $paghiper_data, 'transactionData' => $transactionParams, 'exception' => 'Transaction refunded'),"Transação estornada, não é possível emitir segunda via. Caso acredite que seja um erro, por favor acione o suporte.");

                if($this->outputFormat == 'html') {

                    // Mostrar tela de boleto indisponível
                    $ico = ($this->isPIX) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                    $title = 'Este fatura foi estornada.';
                    $message = 'Seu '.(($this->isPIX) ? 'PIX' : 'boleto').' foi estornado. Caso acredite que seja um erro, por favor acione o suporte.';
                    echo paghiper_print_screen($ico, $title, $message);
                    exit();
                
                }

                return false;
                break;
            case "Collections":
                return false;
                break;
        }
    }

    private function hasPayableTransaction() {

        // Pegamos a data de vencimento e a data de hoje
        $invoiceDuedate = $this->invoiceData['duedate']; // Data de vencimento da fatura
        $dataHoje = date('Y-m-d'); // Data de Hoje

        // Lógica: Checar se um boleto ja foi emitido pra essa fatura
        $invoice_total = paghiper_apply_custom_taxes((float) $this->invoiceData['balance'], $this->gatewayConf);
        $invoice_balance = $invoice_total;

        foreach($this->invoiceData['items'] as $invoice_key => $item) {

            $invoice_item = $item->getRawOriginal();

            if($invoice_item['type'] == 'LateFee') {
                $invoice_balance -= (float) $invoice_item['amount'];
            }

        }

        $transaction_type = ($this->isPIX) ? 'pix' : 'billet';
        $sql = (!$this->isPIX) ? 
            "SELECT * FROM mod_paghiper WHERE (transaction_type = '{$transaction_type}' OR transaction_type IS NULL) AND order_id = '{$this->invoiceID}' AND status = 'pending' AND (slip_value = '{$invoice_total}' OR slip_value = '{$invoice_balance}') AND ('{$dataHoje}' <= due_date OR '{$dataHoje}' <= DATE_ADD('{$invoiceDuedate}', INTERVAL (open_after_day_due) DAY)) ORDER BY ABS( DATEDIFF( due_date, '{$dataHoje}' ) ) ASC LIMIT 1" : 
            "SELECT * FROM mod_paghiper WHERE (transaction_type = '{$transaction_type}' OR transaction_type IS NULL) AND order_id = '{$this->invoiceID}' AND status = 'pending' AND (slip_value = '{$invoice_total}' OR slip_value = '{$invoice_balance}') AND '{$dataHoje}' <= due_date ORDER BY ABS( DATEDIFF( due_date, '{$dataHoje}' ) ) ASC LIMIT 1";

        $query = Capsule::connection()
                    ->getPdo()
                    ->prepare($sql);
        $query->execute();
        $transaction = $query->fetch(\PDO::FETCH_ASSOC);

        if(!empty($transaction)) {
            $due_date           = $transaction['due_date'];
            $grace_days         = $transaction['open_after_day_due'];
            $transaction_url    = $transaction['url_slip'];
            $qrcode_image_url   = $transaction['qrcode_image_url'];
            $transaction_value  = $transaction['slip_value'];
            $emv                = $transaction['emv'];
        }

        // Só re-emitimos a fatura se os valores forem diferentes, se limite para pagamento ja tiver expirado (somando os dias de tolerência) e se o status for não-pago.
        if( 
            (
                // Caso nenhum boleto tenha sido emitido
                empty($transaction) || 
                // Caso não haja URL de boleto/PIX disponível no banco
                (empty($transaction_url) && empty($qrcode_image_url)) ||
                // Caso o vencimento esteja no futuro mas for diferente do definido na fatura
                (strtotime($invoiceDuedate) > strtotime(date('Y-m-d')) && $due_date !== $invoiceDuedate)
            ) 
            && $this->invoiceData['status'] == 'Unpaid'
        ) {

            $sql = "SELECT * FROM mod_paghiper WHERE order_id = '{$this->invoiceID}' AND status = 'reserved' AND (slip_value = '{$invoice_total}' OR slip_value = '{$invoice_balance}') ORDER BY due_date DESC LIMIT 1;";
            $query = Capsule::connection()
                    ->getPdo()
                    ->prepare($sql);
            $query->execute();
            $reserved_billet = $query->fetch(\PDO::FETCH_ASSOC);
            
            if(!empty($reserved_billet)) {

                if($this->outputFormat == 'html') {

                    $ico = ($this->isPIX) ? 'pix-reserved.png' : 'billet-reserved.png';
                    $title = 'Pagamento pré-confirmado.';
                    $message = 'Este '.(($this->isPIX) ? 'PIX' : 'boleto').' teve o pagamento pré-confirmado e está aguardando compensação bancária. Por favor, aguarde.';
                    echo paghiper_print_screen($ico, $title, $message);
                    exit();

                }

                return false;

            }

            if(empty($transaction) && empty($reserved_billet)) {
                return false;
            }
        }

        $this->transactionData = $transaction;
        return true;

    }

    private function prepareTransactionData() {

        // Pegamos as datas que definimos anteriormente e transformamos em objeto Date do PHP
        $data1 = new DateTime($this->invoiceData['duedate']);
        $data2 = new DateTime(date('Y-m-d'));

        // Comparamos as datas para enviar o resultado a PagHiper. Isso é necessário pois o gateway pede o vencimento em número de dias no futuro, não como data.
        $intervalo = $data2->diff($data1); 
        $vencimentoBoleto = $intervalo->format('%R%a');

        if($vencimentoBoleto < 0) {
            $vencimentoBoleto = $this->reissueUnpaid;
        } else {
            $vencimentoBoleto = $intervalo->days;
        }

        // Calculamos a diferença de dias entre o dia de vencimento e os dias para aplicação de desconto.
        $discount_period = (int) $this->gatewayConf['early_payment_discounts_days'];
        if(!empty($discount_period) && $discount_period > 0) {
            
            if($vencimentoBoleto <= $discount_period || $vencimentoBoleto == 0) {
                unset($this->gatewayConf['early_payment_discounts_days']);
                unset($this->gatewayConf['early_payment_discounts_cents']);
            }
        }

		$urlRetorno = $this->systemURL.'/modules/gateways/';
		$urlRetorno .= ($this->isPIX) ? 'paghiper_pix.php' : 'paghiper.php';

        $client_data_breakpoint = 0;

        // Checamos se os dados do cliente vem de um checkout ou do perfil do cliente.
        $client_data = json_decode(html_entity_decode($_POST['client_data']), TRUE);
        if( !empty($_POST) && is_array($client_data) && !empty($client_data) ) {
            $client_details = $client_data;
            $client_data_breakpoint = 1;
        } else {

            // Se não tivermos um UserID, abortar operação
            if(!array_key_exists('userid', $this->invoiceData) || empty($this->invoiceData['userid'])) {
                logTransaction($this->gatewayConf["name"],array('json' => $paghiper_data, 'transactionData' => $transactionParams, 'exception' => 'No user ID found'),"Não foi possível encontrar o ID do cliente associado a fatura. Por favor, verifique os dados da fatura.");
                return false;
            }

            $client_query = localAPI('getClientsDetails', ['clientid' => $this->invoiceData['userid'], 'stats' => false], $this->whmcsAdminUser);

            if (version_compare($this->whmcsVersion, '8.0.0') >= 0 && array_key_exists('client', $client_query)) {
                $client_details = $client_query['client'];
                $client_data_breakpoint = 2;
            } elseif(array_key_exists('email', $client_query)) {
                $client_details = $client_query;
                $client_data_breakpoint = 3;
            } else {
                // Se a função localAPI falhar aqui, provavelmente estamos tratando de uma questão relacionada a licença e executando uma CRON.
                // Se não conseguimos pegar os dados do cliente via API, tentamos pegar via Model
                $client = Client::find($this->invoiceData['userid'], ['email', 'firstname', 'lastname', 'companyname', 'phonenumber', 'address1', 'address2', 'city', 'state', 'postcode', 'country']);
                if($client) {
                    $client_details = $client->toArray();
                    $client_data_breakpoint = 4;
                } else {
                    $client_details = [];
                    $client_data_breakpoint = 5;
                }
            }
        }

        // Checamos se o cliente é um usuário válido
        if(!is_array($client_details) || !array_key_exists('email', $client_details) || empty($client_details['email']) || is_null($client_details['email'])) {

            logTransaction($this->gatewayConf["name"], ['client_id' => $this->invoiceData['userid'], 'client_details' => var_export($client_query, TRUE), 'client_data_origin' => $client_data_breakpoint, 'exception' => 'Invalid client details'], "Não foi possível obter os dados do cliente. Por favor, verifique se o cliente existe.");

            if($this->outputFormat == 'html') {

                $ico = ($this->isPIX) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                $title = 'Ops! Não foi possível emitir o '.(($this->isPIX) ? 'PIX' : 'boleto bancário').'.';
                $message = 'Não foi possível obter os dados do cliente. Por favor, entre em contato com o suporte.';
                echo paghiper_print_screen($ico, $title, $message);
                exit();

            }

            return false;
        }

        // Get used currency
        $default_currency_code = getCurrency()['code'];
        if(is_array($client_details) && array_key_exists('currency_code', $client_details)) {
            $currency = $client_details['currency_code'];
        } else {
            $currency = $default_currency_code;
        }
        
        if($currency !== 'BRL' && $currency !== 'R$') {

            logTransaction($this->gatewayConf["name"],array('json' => $client_details, 'transactionData' => $transactionParams, 'exception' => 'Invalid currency', 'currency' => $currency),"Este método de pagamento só pode ser utilizado para pagamentos em R$ (BRL)<br>Caso creia que isso seja um erro, entre em contato com o suporte.");

            if($this->outputFormat == 'html') {

                $ico = ($this->isPIX) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                $title = 'Método de pagamento indisponível para a moeda selecionada';
                $message = 'Este método de pagamento só pode ser utilizado para pagamentos em R$ (BRL)<br>Caso creia que isso seja um erro, entre em contato com o suporte.';
                echo paghiper_print_screen($ico, $title, $message);
                exit();

            }

            return false;
        }

        return [
            'client_data'		=> $client_details,
            'notification_url'	=> $urlRetorno,
            'due_date'			=> $vencimentoBoleto,
        ];
    }

    // Comes from generate_paghiper_billet()
    private function createTransaction() {

        $params = $this->prepareTransactionData();
        
        // Prepare variables that we'll be using during the process
        $postData    = array();
        
        // Data received from the invoice
        $total 				= $this->invoiceData['balance'];
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
        $cpf_cnpj			= $params['client_data']['cpf_cnpj'];
        $razaosocial_val    = $params['client_data']['razao_social'];
    
        // Data
        $notification_url 	= $params['notification_url'];
        $cpfcnpj 			= $this->gatewayConf['cpf_cnpj'];
        $razaosocial        = $this->gatewayConf['razao_social'];
    
        // Data received through function params
        $invoice_id			= $this->invoiceData['id'];
        $client_id 			= $this->invoiceData['userid'];
    
        if(empty($cpf_cnpj)) {
    
            // Checamos se o campo é composto ou simples
            if(strpos($cpfcnpj, '|')) {
    
                // Se composto, pegamos ambos os campos
                $fields = explode('|', $cpfcnpj);
        
                $i = 0;
        
                foreach($fields as $field) {
                    
                    $sql = "SELECT * FROM tblcustomfieldsvalues WHERE relid = '$client_id' and fieldid = '".trim($field)."'";
                    $query = Capsule::connection()
                        ->getPdo()
                        ->prepare($sql);
                    $query->execute();
                    $result = $query->fetch(\PDO::FETCH_BOTH);
    
                    ($i == 0) ? $cpf = paghiper_convert_to_numeric(trim($result["value"])) : $cnpj = paghiper_convert_to_numeric(trim($result["value"]));
                    if($i == 1) { break; }
                    $i++;
                }
        
            } else {
    
                // Se simples, pegamos somente o que temos
                $sql = "SELECT value FROM tblcustomfieldsvalues WHERE relid = '$client_id' and fieldid = '$cpfcnpj'";
                $query = Capsule::connection()
                    ->getPdo()
                    ->prepare($sql);
                $query->execute();
                $result = $query->fetch(\PDO::FETCH_BOTH);

                if(is_array($result) && !empty($result)) {
                    $cpf_cnpj     = paghiper_convert_to_numeric(trim(array_shift($result)));
                }
            
            }
    
        }
    
        if(empty($cpf) && empty($cnpj)) {
            if(strlen($cpf_cnpj) > 11) {
                $cnpj = $cpf_cnpj;
            } else {
                $cpf = $cpf_cnpj;
            }
        }
    
        // Validate CPF/CNPJ
        if(!paghiper_is_tax_id_valid($cpf) && !paghiper_is_tax_id_valid($cnpj)) {

            logTransaction($this->gatewayConf["name"],array('tax_id' => (!empty($cnpj)) ? $cnpj : $cpf, 'invoice_id' => $invoice_id, 'exception' => 'Failed Paghiper TaxID validation'), sprintf("Número de CPF/CNPJ inválido! Não foi possível gerar o %s.", ($this->isPIX) ? 'PIX' : 'boleto'));

            if($this->outputFormat == 'html') {
                $ico = ($this->isPIX) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                $title = 'Ops! Não foi possível emitir o '.((!$this->isPIX) ? 'boleto bancário' : 'PIX').'.';
                $message = 'Número de CPF/CNPJ inválido! Por favor atualize seus dados ou entre em contato com o suporte';
                
                echo paghiper_print_screen($ico, $title, $message);
                exit();

            }

            return false;
        }
    
    
        // Aplicamos as taxas do gateway sobre o total
        $total = paghiper_apply_custom_taxes($total, $this->gatewayConf, $params);
        
        // Preparate data to send
        $paghiper_data = array(
           "apiKey"                         => $this->gatewayConf['api_key'],
           "partners_id"                    => (($this->isPIX) ? "98IS0XYC" : "12WIT2XD"),
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
                                                        'price_cents'   => paghiper_convert_to_numeric($total),
                                                        'quantity'      => 1
                                                    ),
                                                ),
    
           // Dados do cliente
           "payer_email"                    => $email,
           "payer_name"                     => $firstname . ' ' . $lastname,
           "payer_phone"                    => paghiper_convert_to_numeric($phone),
           "payer_street"                   => $address1,
           "payer_complement"               => $address2,
           "payer_city"                     => $city,
           "payer_state"                    => $state,
           "payer_zip_code"                 => $postcode,
        );
    
        // Checa se incluimos dados CPF ou CNPJ no post
        if((isset($cpf) && !empty($cpf) && $cpf != "on file") || (isset($cnpj) && !empty($cnpj) && $cnpj != "on file")) {
            if(isset($cnpj) && !empty($cnpj) && paghiper_is_tax_id_valid($cnpj)) {
                if(isset($companyname) && !empty($companyname)) {
                    $paghiper_data["payer_name"] = $companyname;
                }
    
                if(empty($razaosocial_val)) {
    
                    if (isset($razaosocial) && !empty($razaosocial) && isset($cnpj) && !empty($cnpj)) {
                        
                        $sql = "SELECT value FROM tblcustomfieldsvalues WHERE relid = '$client_id' and fieldid = '$razaosocial'";
                        $query = Capsule::connection()
                            ->getPdo()
                            ->prepare($sql);
                        $query->execute();
                        $result = $query->fetch(\PDO::FETCH_BOTH);

                        if(is_array($result) && !empty($result)) {
                            $razaosocial_val = trim(array_shift($result));
                        }
                        
                    }
    
                }
                
                if(isset($razaosocial_val) && !empty($razaosocial_val) && strlen($razaosocial_val) > 5 ){
                    $paghiper_data["payer_name"] =  $razaosocial_val;
                }
    
                $paghiper_data["payer_cpf_cnpj"] = substr(trim(str_replace(array('+','-'), '', filter_var($cnpj, FILTER_SANITIZE_NUMBER_INT))), -14);
            } else {
                $paghiper_data["payer_cpf_cnpj"] = substr(trim(str_replace(array('+','-'), '', filter_var($cpf, FILTER_SANITIZE_NUMBER_INT))), -15);
            }
        } elseif(!isset($cpfcnpj) || $cpfcnpj == '') {
            logTransaction($this->gatewayConf["name"],array('post' => $_POST, 'json' => $paghiper_data),"Boleto não exibido. Você não definiu os campos de CPF/CNPJ");
        } elseif(!isset($cpf_cnpj) || $cpf_cnpj == '' || (empty($cpf) && empty($cnpj))) {
            logTransaction($this->gatewayConf["name"],array('post' => $_POST, 'json' => $paghiper_data),"Boleto não exibido. CPF/CNPJ do cliente não foi informado");
        } else {
            logTransaction($this->gatewayConf["name"],array('post' => $_POST, 'json' => $paghiper_data),"Boleto não exibido. Erro indefinido");
        }
    
        // Checamos os valores booleanos, 1 por 1
        // Dados do boleto
        $additional_config_boolean = array(
            'fixed_description'             => $this->gatewayConf['fixed_description'],
            'per_day_interest'              => $this->gatewayConf['per_day_interest'],
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

        if(!empty($this->gatewayConf['early_payment_discounts_cents'])) {
            $discountDbConf     = preg_replace('#[^0-9\.,]#', '', $this->gatewayConf['early_payment_discounts_cents']);
            $discountConf       = (float) str_replace(',', '.',  $discountDbConf);
        } else {
            $discountConf = NULL;
        }
        
        $discount_value = (!empty($discountConf)) ? $total * (($discountConf > 99) ? 99 / 100 : $discountConf / 100) : '';
        $discount_cents = (!empty($discount_value)) ? paghiper_convert_to_numeric(number_format($discount_value, 2, '.', '' )) : 0;
    
        if((floatval($total) - floatval($discount_value)) < 3) {

            logTransaction($this->gatewayConf["name"],array('json' => $paghiper_data, 'transactionData' => $transactionParams, 'exception' => $e),"O valor com desconto por pagto. antecipado é inferior a R$3,00! Por favor, revise a configuração.");

            $err_message = [
                'status'    => 400,
                'error'     => 'below_minimum_ticket',
                'message'   => 'Valor total com inferior a R$ 3.'
            ];

            switch($this->outputFormat) {

                case 'html':

                    // Mostrar tela de boleto cancelado
                    $ico = ($this->isPIX) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                    $title = 'Não foi possível gerar o '.(($this->isPIX) ? 'PIX' : 'boleto').'!';
                    $message = 'Este '.(($this->isPIX) ? 'PIX' : 'boleto').' tem o valor total inferior a R$3,00! Por favor, escolha outro método de pagamento.';
                    echo paghiper_print_screen($ico, $title, $message);
                    exit();
                    break;

                case 'json':
                    return json_encode($err_message);
                    break;
                case 'array':
                    return $err_message;
                    break;

            }

            return false;
    
        }
    
        $additional_config_text = array(
            'early_payment_discounts_days'  => $this->gatewayConf['early_payment_discounts_days'],
            'early_payment_discounts_cents' => $discount_cents,
            'open_after_day_due'            => $this->gatewayConf['open_after_day_due'],
            'late_payment_fine'             => $this->gatewayConf['late_payment_fine'],
            'open_after_day_due'            => ($this->isPIX) ? 0 : $this->gatewayConf['open_after_day_due'],
        );
    
        foreach($additional_config_text as $k => $v) {
            if(!empty($v)) {
                $paghiper_data[$k] = paghiper_convert_to_numeric($v);
            }
        }
    
        $data_post = json_encode( $paghiper_data );
    
        $url = ($this->isPIX) ? "https://pix.paghiper.com/invoice/create/" : "https://api.paghiper.com/transaction/create/";
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
            $transaction_type 	        = ($this->isPIX) ? 'pix' : 'billet';
            $transaction_id 	        = ($this->isPIX) ? $json['pix_create_request']['transaction_id'] : $json['create_request']['transaction_id'];
            $invoice_id 			    = ($this->isPIX) ? $json['pix_create_request']['order_id'] : $json['create_request']['order_id'];
            $due_date 			        = ($this->isPIX) ? $json['pix_create_request']['due_date'] : $json['create_request']['due_date'];
            $status 			        = ($this->isPIX) ? $json['pix_create_request']['status'] : $json['create_request']['status'];
            $url_slip 			        = ($this->isPIX) ? null : $json['create_request']['bank_slip']['url_slip'];
            $url_slip_pdf 		        = ($this->isPIX) ? null : $json['create_request']['bank_slip']['url_slip_pdf'];
            $digitable_line 	        = ($this->isPIX) ? null : $json['create_request']['bank_slip']['digitable_line'];
            $bar_code_number_to_image   = ($this->isPIX) ? null : $json['create_request']['bank_slip']['bar_code_number_to_image'];
            $open_after_day_due         = ($this->isPIX) ? 0 : $this->gatewayConf['open_after_day_due'];
            
            $qrcode_base64 		        = ($this->isPIX) ? $json['pix_create_request']['pix_code']['qrcode_base64'] : null;
            $qrcode_image_url 	        = ($this->isPIX) ? $json['pix_create_request']['pix_code']['qrcode_image_url'] : null;
            $emv 				        = ($this->isPIX) ? $json['pix_create_request']['pix_code']['emv'] : null;
            $bacen_url 			        = ($this->isPIX) ? $json['pix_create_request']['pix_code']['bacen_url'] : null;
            $pix_url 			        = ($this->isPIX) ? $json['pix_create_request']['pix_code']['pix_url'] : null;
    
            $this->transactionTotal = $total;
    
            $sql = "INSERT INTO mod_paghiper (transaction_type,transaction_id,order_id,due_date,status,url_slip,url_slip_pdf,digitable_line,bar_code_number_to_image,open_after_day_due,slip_value,qrcode_base64,qrcode_image_url,emv,bacen_url,pix_url) VALUES ('$transaction_type', '$transaction_id','$invoice_id','$due_date','$status','$url_slip','$url_slip_pdf','$digitable_line','$bar_code_number_to_image', '$open_after_day_due','$this->transactionTotal','$qrcode_base64','$qrcode_image_url','$emv','$bacen_url','$pix_url');";
            $query = Capsule::connection()
                        ->getPdo()
                        ->prepare($sql);
            $query_insert = $query->execute();
    
            if(!$query_insert) {
                
                logTransaction($this->gatewayConf["name"],array('json' => $json, 'query' => $sql, 'query_result' => $query, 'exception' => $e),"Não foi possível inserir a transação no banco de dados. Por favor entre em contato com o suporte.");

                if($this->outputFormat == 'html') {

                    $ico = ($this->isPIX) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                    $title = 'Ops! Não foi possível emitir o '.(($this->isPIX) ? 'PIX' : 'boleto bancário').'.';
                    $message = 'Por favor entre em contato com o suporte. Erro 0x004681';

                    echo paghiper_print_screen($ico, $title, $message);
                    exit();

                }

                return false;
            }

            $transactionData    = ($this->isPIX) ? $json['pix_create_request'] : $json['create_request'];
            $transactionDetails = ($this->isPIX) ? $json['pix_create_request']['pix_code'] : $json['create_request']['bank_slip'];

            $this->transactionData = array_merge($transactionData, $transactionDetails);
    
        } else {

            logTransaction($this->gatewayConf["name"],array('json' => $json, 'post' => $_POST, 'request' => $data_post),"Não foi possível criar a transação.");

            if($this->outputFormat == 'html') {
    
                // Não foi possível solicitar o boleto.
                $ico = ($this->isPIX) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                $title = 'Ops! Não foi possível emitir o '.(($this->isPIX) ? 'PIX' : 'boleto bancário').'.';
                $message = 'Por favor entre em contato com o suporte. Erro 0x004682';
                
                echo paghiper_print_screen($ico, $title, $message);
                exit();
            }
    
            return false;
        }

    }

    private function getTransaction() {
    
        if($this->outputFormat == 'json') {
            return json_encode($this->transactionData);
        } elseif($this->outputFormat == 'array') {
            return $this->transactionData;
        }
        
        if($this->isPIX) {
            return paghiper_print_screen($this->transactionData['pix_code']['qrcode_image_url'], null, null, array('is_pix' => true, 'invoice_id' => $this->invoiceID, 'payment_value' => $this->transactionTotal, 'pix_emv' => $this->transactionData['pix_code']['emv']));
        } else {
            return paghiper_fetch_remote_url($url_slip);
        }

        return false;
        
    }

    public function isPIX() {
        return $this->isPIX;
    }

    public function process() {

        if(!$this->isGatewayAvailable) {
            return false;
        }

        if($this->hasPayableStatus()) {
            if($this->hasPayableTransaction()) {
                return $this->getTransaction();
            } else {

                // Error when not possible to reissue transaction
                // Pegamos a data de hoje, adicionamos um dia e usamos como nova data de vencimento
                $dataHoje = date('Y-m-d'); // Data de Hoje

                $reissue_unpaid_cont = (int) $this->gatewayConf['reissue_unpaid'];
                $reissue_unpaid = (isset($reissue_unpaid_cont) && ($reissue_unpaid_cont === 0 || !empty($reissue_unpaid_cont))) ? $reissue_unpaid_cont : 1 ;
                
                if($reissue_unpaid == -1 && $dataHoje > $this->invoiceData['duedate']) {

                    logTransaction($this->gatewayConf["name"],array('transactionData' => $transactionParams, 'exception' => 'Reissue not allowed'),"Transação vencida. Configuração não permite reemissão. Caso ache que isso é um erro, revise nas opções ou entre em contato com o suporte");

                    switch ($this->outputFormat) {
                        case 'json':
                            return json_encode([
                                'status'    => 400,
                                'error'     => 'reissue_not_allowed',
                                'message'   => 'Reemissão não disponível.'
                            ]);
                            break;
                        case 'html':
        
                            // Mostrar tela de boleto indisponível
                            $ico = ($this->isPIX) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                            $title = 'Este '.(($this->isPIX) ? 'PIX' : 'boleto').' venceu!';
                            $message = 'Caso ja tenha efetuado o pagamento, aguarde o prazo de baixa bancária. Caso contrário, por favor acione o suporte.';
                            echo paghiper_print_screen($ico, $title, $message);
                            exit();
        
                            break;
                    }   
                    
                    return false;
                }

                // Abortamos a exibição, caso valor seja menor que R$ 3
                if((int) $this->invoiceData['total'] < 3) {

                    logTransaction($this->gatewayConf["name"],array('transactionData' => $transactionParams, 'exception' => 'Below minimun ticket'),'Este '.(($this->isPIX) ? 'PIX' : 'boleto').' tem o valor total inferior a R$3,00! Por favor, escolha outro método de pagamento.');

                    $err_message = [
                        'status'    => 400,
                        'error'     => 'below_minimum_ticket',
                        'message'   => 'Valor total com inferior a R$ 3.'
                    ];

                    switch($this->outputFormat) {

                        case 'html':

                            // Mostrar tela de boleto cancelado
                            $ico = ($this->isPIX) ? 'pix-cancelled.png' : 'billet-cancelled.png';
                            $title = 'Não foi possível gerar o '.(($this->isPIX) ? 'PIX' : 'boleto').'!';
                            $message = 'Este '.(($this->isPIX) ? 'PIX' : 'boleto').' tem o valor total inferior a R$3,00! Por favor, escolha outro método de pagamento.';
                            echo paghiper_print_screen($ico, $title, $message);
                            exit();
                            break;

                        case 'json':
                            return json_encode($err_message);
                            break;
                        case 'array':
                            return $err_message;
                            break;

                    }

                    return false;

                }

                $this->createTransaction();
                return $this->getTransaction();
            }
        }
    }
}
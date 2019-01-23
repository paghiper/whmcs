<?php
/**
 * PagHiper - Módulo oficial para integração com WHMCS
 * 
 * @package    PagHiper para WHMCS
 * @version    1.2.1
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Desenvolvido e mantido Henrique Cruz - https://henriquecruz.com.br/
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2019, PagHiper
 * @link       https://www.paghiper.com/
 */

// Opções padrão do Gateway
function paghiper_config($params = NULL) {
    $config = array(
        'FriendlyName' => array(
            "Type" => "System",
            "Value" => "PagHiper Boleto"
        ),
        "nota" => array(
            "FriendlyName" => "Nota",
            "Description" => "
            <table>
                <tbody>
                    <tr>
                        <td width='60%'><img src='https://s3.amazonaws.com/logopaghiper/whmcs/badge.oficial.png' style='max-width: 100%;'></td>
                        <td>Versão <h2 style='font-weight: bold; margin-top: 0px; font-size: 300%;'>1.2.1</h2></td>
                    </tr>
                </tbody>
            </table>

           <h2>Para que o modulo funcione, siga as etapas abaixo:</h2>
           <ul>
               <li>Caso não possua uma conta PagHiper, <a href='https://www.paghiper.com/abra-sua-conta/' target='_blank'><strong> crie a sua aqui</strong></a> <br>
                   Precisa de ajuda para criar sua conta? <a href='https://www.paghiper.com/duvidas/como-se-cadastrar-no-paghiper/' target='_blank'><strong> clique aqui e veja como criar de maneira rápida e facil.</strong></a><br></li>
               <li>Certifique-se que a conta esteja verificada e valida na página de <a href='https://www.paghiper.com/painel/detalhes-da-conta/' target='_blank'><strong>Detalhes da sua conta</strong></a> PagHiper</li>
               <li>Gere o seu token PagHiper na página <a href='https://www.paghiper.com/painel/token/' target='_blank'><strong> Ferramentas > Token</strong></a> e pegue sua ApiKey na página <a href='https://www.paghiper.com/painel/token/' target='_blank'><strong>Minha Conta > Dados da Conta</strong></a></li>
               <li>Ative a integração entre o PagHiper e o <a href='https://www.paghiper.com/painel/whmcs' target='_blank'><strong>WHMCS</strong></a>, <a href='https://www.paghiper.com/painel/whmcs' target='_blank'><strong>Acesse aqui</strong></a> e ative.</li>
               <li><h5>Suporte</h5><p>Se tiver qualquer duvida, visite a nossa <a href='https://www.paghiper.com/atendimento/' target='_blank'><strong>central de atendimento</strong></a></p></li>
           </ul>"
        ),
        
        'email' => array(
            "FriendlyName" => "Email",
            "Type" => "text",
            "Size" => "100",
            "Description" => "Email da conta PagHiper que irá receber"
        ),
        'api_key' => array(
            "FriendlyName" => "API Key",
            "Type" => "text",
            "Size" => "66",
            "Description" => "Campo composto de números, letras, traços e hífen.
Sempre começa por apk_. Caso não tenha essa informação, pegue sua chave API <a href='https://www.paghiper.com/painel/credenciais/' target='_blank'><strong>aqui</strong></a>."
        ),
        'token' => array(
            "FriendlyName" => "Token",
            "Type" => "text",
            "Size" => "66",
            "Description" => "Extremamente importante, você pode gerar seu token em nossa pagina: Painel > Ferramentas > Token ( <a href='https://www.paghiper.com/painel/token/' target='_blank'><strong>Confira Aqui</strong></a> )."
        ),
        "cpf_cnpj" => array(
            "FriendlyName" => "ID do custom field contendo CPF/CNPJ",
            "Type" => "text",
            "Size" => "3",
            "Description" => "Defina aqui o ID do campo usado para coletar CPF/CNPJ do seu cliente. Isso é necessário para usar o checkout transparente." . get_customfield_id()
        ),
        "porcento" => array(
            "FriendlyName" => "Taxa Percentual (%)",
            "Type" => "text",
            "Size" => "3",
            "Description" => "Porcentagem da fatura a se pagar a mais por usar o PagHiper. Ex.: (2.5). Obs.: não precisa colocar o % no final. Obs²: Use o ponto (.) como delimitador de casas decimais. <br> Recomendamos não cobrar nenhuma taxa."
        ),
        "taxa" => array(
            "FriendlyName" => "Taxa fixa",
            "Type" => "text",
            "Size" => "7",
            "Description" => "Taxa cobrada a mais do cliente por utilizar esse meio de pagamento, exemplo: 1.0 (dois reias). Obs: Use o ponto (.) como delimitador de casas decimais.<br> Recomendamos não cobrar nenhuma taxa."
        ),
        "abrirauto" => array(
            "FriendlyName" => "Abrir boleto ao abrir fatura?",
            "Type" => "yesno"
        ),
        "fixed_description" => array(
            "FriendlyName" => "Exibe ou não a frase fixa do boleto (configurada no painel da PagHiper)",
            "Type" => "yesno"
        ),
        "open_after_day_due" => array(
            "FriendlyName" => "Tolerância para pagto",
            "Type" => "text",
            "Size" => "2",
            "Description" => "Número máximo de dias em que o boleto poderá ser pago após o vencimento. (Prática comum para quem opta por cobrar juros e multas)."
        ),
        "late_payment_fine" => array(
            "FriendlyName" => "Percentual da multa por atraso (%)",
            "Type" => "text",
            "Size" => "1",
            "Description" => "O percentual máximo autorizado é de 2%, de acordo artigo 52, parágrafo primeiro do Código de Defesa do Consumidor, Lei 8.078/90"
        ),
        "per_day_interest" => array(
            "FriendlyName" => "Aplicar multa fatorada?",
            "Type" => "yesno",
            "Description" => "Se selecionado, a multa será dividida pela quantidade de dias em atraso."
        ),
        "early_payment_discounts_days" => array(
            "FriendlyName" => "Qtde. de dias para aplicação de desconto",
            "Type" => "text",
            "Size" => "2",
            "Description" => "Número de dias em que o pagamento pode ser realizado com antecedência recebendo o desconto extra."
        ),
        "early_payment_discounts_cents" => array(
            "FriendlyName" => "Desconto por pagto. antecipado",
            "Type" => "text",
            "Size" => "6",
            "Description" => "Valor do desconto que será aplicado caso o pagamento ocorra de forma antecipada. Em percentual (Ex.: 10%)"
        ),
        
        "admin" => array(
            "FriendlyName" => "Administrador atribuído",
            "Type" => "text",
            "Size" => "10",
            "Default" => "admin",
            "Description" => "Insira o nome de usuário ou ID do administrador do WHMCS que será atribuído as transações. Necessário para usar a API interna do WHMCS."
        ),
        
        'suporte' => array(
            "FriendlyName" => "<span class='label label-primary'><i class='fa fa-question-circle'></i> Suporte</span>",
            "Description" => '<h2>Para informações ou duvidas: </h2><br><br>
<ul>
<li>Duvidas sobre a conta <strong> PAGHIPER:</strong> <br><br>
Devem ser resolvidas diretamente na central de atendimento: <br>
<strong><a href="https://www.paghiper.com/atendimento" target="_blank">https://www.paghiper.com/atendimento</a></strong></li>
<br><br><br>
<li>Duvidas sobre o <strong> Modulo WHMCS </strong> <br><br>
Tem uma dúvida ou quer contribuir para o projeto? Acesse nosso repositório no GitHub!
<br>
<strong><a href="https://github.com/paghiper/whmcs" target="_blank">https://github.com/paghiper/whmcs</a></strong></li>
</ul><br>'
        )
       
    );
    return $config;
}

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
        $tutorial .= '<br>Caso o campo de CPF não esteja disponível, <strong><a href="https://github.com/paghiper/whmcs/wiki/Criando-o-campo-de-CPF-CNPJ" target="_blank">acesse o tutorial clicando aqui</a></strong> e veja como pode criar o campo.';
       return $tutorial;
    } else {
        return '<br><br>Nenhum campo possível foi encontrado! Por favor <strong><a href="https://github.com/paghiper/whmcs/wiki/Criando-o-campo-de-CPF-CNPJ" target="_blank">acesse o tutorial clicando aqui</a></strong> e veja como pode criar o campo.';
    }
    
}

function paghiper_link($params) {

    // Definimos os dados para retorno e checkout.
    $systemurl = rtrim($params['systemurl'],"/");
    $urlRetorno = $systemurl.'/modules/gateways/'.basename(__FILE__);

    
    // Abrir o boleto automaticamente ao abrir a fatura 
    if($params['abrirauto'] == true):
        $target = '';
        $abrirAuto = "<script type='text/javascript'> document.paghiper.submit()</script>";
    else:
        $target =  "target='_blank'";
        $abrirAuto = ''; 
    endif;

    // Código do checkout
    $code = "<!-- INICIO DO FORM DO BOLETO PAGHIPER -->
    <form name=\"paghiper\" action=\"{$urlRetorno}?invoiceid={$params['invoiceid']}&uuid={$params['clientdetails']['userid']}&mail={$params['clientdetails']['email']}\" method=\"post\">
    <input type='image' src='https://www.paghiper.com/img/checkout/boleto/boleto-240px-148px.jpg' 
    title='Pagar com Boleto' alt='Pagar com Boleto' border='0'
     align='absbottom' width='120' height='74' /><br>
    <button class='btn btn-success' style='margin-top: 5px;' type=\"submit\"><i class='fa fa-barcode'></i> Gerar Boleto</button>
    <!-- FIM DO BOLETO PAGHIPER -->
    </form>
    {$abrirAuto}";
    
   return $code; 

}                 

function httpPost($params,$GATEWAY,$invoiceid,$urlRetorno,$vencimentoBoleto) {
    $postData    = '';
    $query       = "SELECT tblinvoices.*,tblclients.id as myid, tblclients.firstname,tblclients.lastname,tblclients.companyname,tblclients.address1,tblclients.address2,tblclients.city,tblclients.state,tblclients.postcode,tblclients.email,tblclients.phonenumber FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE tblinvoices.id='$invoiceid'";
    $result      = mysql_query($query);
    $data        = mysql_fetch_array($result);
    $id          = $data["id"];
    $myid        = $data["myid"];
    $firstname   = $data["firstname"];
    $lastname    = $data["lastname"];
    $companyname = $data["companyname"];
    $address1    = $data["address1"];
    $address2    = $data["address2"];
    $city        = $data["city"];
    $state       = $data["state"];
    $postcode    = $data["postcode"];
    $date        = $data["email"];
    $subtotal    = $data["subtotal"];
    $total       = $data["total"];
    $credit      = $data["credit"];
    $tax         = $data["tax"];
    $taxrate     = $data["taxrate"];
    $phone       = $data["phonenumber"];
    $email       = $data["email"];
    $total       = $data["total"];
    $cpfcnpj     = $GATEWAY['cpf_cnpj'];
    $query2      = "SELECT * FROM tblcustomfieldsvalues WHERE relid = $myid and fieldid = $cpfcnpj";
    $result2     = mysql_query($query2);
    $data2       = mysql_fetch_array($result2);
    $cpf         = $data2["value"];

    // Aplicamos as taxas do gateway sobre o total
    $total = apply_custom_taxes($total, $GATEWAY, $params);
    
    // Preparate data to send
    $paghiper_data = array(
       "apiKey"                         => $GATEWAY['api_key'],
       "partners_id"                    => "12WIT2XD",
       "order_id"                       => $invoiceid,

       // Informações para a criação e liquidação da fatura
       "notification_url"               => $urlRetorno,
       "days_due_date"                  => $vencimentoBoleto,
       'type_bank_slip'                 => 'boletoA4',

       // Dados da fatura
       'items'                          =>  array(
                                                array(
                                                    'item_id'       => $invoiceid,
                                                    'description'   => 'Fatura #'.$invoiceid,
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
    if ($cpf != " " or $cpf != "on file") {
        if(strpos($cpf, '/') !== false) {
            $paghiper_data["payer_name"] = $companyname;
            $paghiper_data["payer_cpf_cnpj"] = substr(trim(str_replace(array('+','-'), '', filter_var($cpf, FILTER_SANITIZE_NUMBER_INT))), -14);
        } else {
            $paghiper_data["payer_cpf_cnpj"] = substr(trim(str_replace(array('+','-'), '', filter_var($cpf, FILTER_SANITIZE_NUMBER_INT))), -15);
        }
    } elseif(!isset($cpfcnpj) || $cpfcnpj == '') {
        logTransaction($GATEWAY["name"],$_POST,"Boleto não exibido. Você não definiu o campo CPF/CNPJ");
    } elseif(!isset($cpf_cnpj) || $cpf_cnpj == '') {
        logTransaction($GATEWAY["name"],$_POST,"Boleto não exibido. CPF/CNPJ do cliente não foi informado");
    } else {
        logTransaction($GATEWAY["name"],$_POST,"Boleto não exibido. Erro indefinido");
    }

    // Checamos os valores booleanos, 1 por 1
    // Dados do boleto
    $additional_config_boolean = array(
        'fixed_description'             => $GATEWAY['fixed_description'],
        'per_day_interest'              => $GATEWAY['per_day_interest'],
    );

    foreach($additional_config_boolean as $k => $v) {
        if($v === TRUE || $v === FALSE) {
            $paghiper_data[$k] = convert_to_numeric($v);
        }
    }

    $discount_config = (!empty($GATEWAY['early_payment_discounts_cents'])) ? ltrim(preg_replace('/\D/', '', $GATEWAY['early_payment_discounts_cents']), 0) : '';
    $discount_value = (!empty($discount_config)) ? convert_to_numeric( number_format($total * (($discount_config > 99) ? 99 / 100 : $discount_config / 100), 2, '.', '' ), 2, '.', '' ) : '';

    $additional_config_text = array(
        'early_payment_discounts_days'  => $GATEWAY['early_payment_discounts_days'],
        'early_payment_discounts_cents' => $discount_value,
        'open_after_day_due'            => $GATEWAY['open_after_day_due'],
        'late_payment_fine'             => $GATEWAY['late_payment_fine'],
        'open_after_day_due'            => $GATEWAY['open_after_day_due'],
    );

    foreach($additional_config_text as $k => $v) {
        if(!empty($v)) {
            $paghiper_data[$k] = $v;
        }
    }

    $data_post = json_encode( $paghiper_data );

    $url = "http://api.paghiper.com/transaction/create/";
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
        $transaction_id = $json['create_request']['transaction_id'];
        $order_id = $json['create_request']['order_id'];
        $due_date = $json['create_request']['due_date'];
        $status = $json['create_request']['status'];
        $url_slip = $json['create_request']['bank_slip']['url_slip'];
        $url_slip_pdf = $json['create_request']['bank_slip']['url_slip_pdf'];
        $digitable_line = $json['create_request']['bank_slip']['digitable_line'];
        $open_after_day_due = $GATEWAY['open_after_day_due'];

        $slip_value = $total;

        $sql = "INSERT INTO mod_paghiper (transaction_id,order_id,due_date,status,url_slip,url_slip_pdf,digitable_line,open_after_day_due, slip_value) VALUES ('$transaction_id','$order_id','$due_date','$status','$url_slip','$url_slip_pdf','$digitable_line','$open_after_day_due','$slip_value');";

        $query = full_query($sql);

        $output = fetch_remote_url($url_slip);

        return $output;

    } else {

        // Não foi possível solicitar o boleto.
        $return_log = var_dump($json);
        logTransaction($GATEWAY["name"],$_POST,"Não foi possível solicitar o boleto. Log: ".$return_log);
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
                create_table();
            }
        }
    } else {
        create_table();
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

function create_table() {
    $table_create = full_query("CREATE TABLE IF NOT EXISTS `mod_paghiper` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `transaction_id` varchar(16) NOT NULL,
          `order_id` int(11) NOT NULL,
          `due_date` date,
          `status` varchar(45) NOT NULL,
          `url_slip` varchar(255) DEFAULT NULL,
          `url_slip_pdf` varchar(255) DEFAULT NULL,
          `digitable_line` varchar(54) DEFAULT NULL,
          `open_after_day_due` int(2) DEFAULT NULL,
          `slip_value` float(11) DEFAULT NULL,
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

// Nenhuma das funções foi executada, então o script foi acessado diretamente.
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    
    header("access-control-allow-origin: *");

    // Inicializar WHMCS, carregar o gateway e a fatura.
    require "../../init.php";
    $whmcs->load_function("gateway");
    $whmcs->load_function("invoice");

    // Initialize module settings
    $GATEWAY = getGatewayVariables("paghiper");

    // Define variáveis para configurações do gateway
    $gateway_name = $GATEWAY["name"];
    $account_email = trim($GATEWAY["email"]);
    $account_token = trim($GATEWAY['token']);
    $account_api_key = trim($GATEWAY['api_key']);

    // Se o usuário admin estiver vazio nas configurações, usamos o padrão
    $whmcsAdmin = (empty(trim($GATEWAY['admin'])) ? 'admin' : trim($GATEWAY['admin']));

    // Checamos se a tabela da PagHiper está pronta pra uso
    $custom_table = check_table();

    // Se as condições baterem, estamos lidando com um post do checkout transparente.
    if(isset($_GET["invoiceid"])) {

        // Vamos precisar pegar a URL do sistema direto do banco de dados. A variável $params não está disponível nesse momento.
        $systemurl = rtrim(($CONFIG['SystemSSLURL'] ? $CONFIG['SystemSSLURL'] : $CONFIG['SystemURL']),"/");
        $user_id = intval($_GET["uuid"]);
        $user_email = $_GET["mail"];

        // Pegamos a fatura no banco de dados
        $getinvoice = 'getinvoice';
        $getinvoiceid['invoiceid'] = intval($_GET["invoiceid"]);
        $getinvoiceResults = localAPI($getinvoice,$getinvoiceid,$whmcsAdmin);

        // Checamos se a fatura está sendo exibida por um usuário de sub-conta
        if( check_if_subaccount($user_id, $user_email, $getinvoiceResults['userid'] ) == FALSE ) {
            if(intval($getinvoiceResults['userid']) !== $user_id) {
                // ID não bate
                die("Desculpe, você não está autorizado a visualizar esta fatura.");
                exit;
            } else {
                $query = "SELECT email FROM tblclients WHERE id = '".$user_id."' LIMIT 1"; 
                $result = mysql_query($query);
                $data = mysql_fetch_array($result);
                $email = $data[0]; 
                if($email !== $user_email) {
                    exit;
                }
            }
        }
  
        // Pegamos a data de vencimento e a data de hoje
        $invoiceDuedate = $getinvoiceResults['duedate']; // Data de vencimento da fatura
        $dataHoje = date('Y-m-d'); // Data de Hoje
        
        // Se a data do vencimento da fatura for maior que o dia de hoje
        if ( $invoiceDuedate >= date('Y-m-d') ) {
            // Usamos a data de vencimento normalmente
            $billetDuedate  = $invoiceDuedate; 
        // Se a data de vencimento da fatura for menor que o dia de hoje
        } elseif( $invoiceDuedate < date('Y-m-d')) {
            // Pegamos a data de hoje, adicionamos um dia e usamos como nova data de vencimento
            $billetDuedate  = date('Y-m-d', strtotime('+1 day'));  
        } 

        // Lógica: Checar se um boleto ja foi emitido pra essa fatura
        $order_id = $getinvoiceResults['invoiceid'];
        $invoice_total = (int) $getinvoiceResults['total'];

        $sql = "SELECT * FROM mod_paghiper WHERE order_id = '$order_id' AND status = 'pending' AND slip_value = '$invoice_total' AND due_date = '$invoiceDuedate' LIMIT 1;";

        $billet = mysql_fetch_array(mysql_query($sql), MYSQL_ASSOC);
        $due_date = $billet['due_date'];
        $grace_days = $billet['open_after_day_due'];
        $billet_url = $billet['url_slip'];
        $billet_value = $billet['slip_value'];
        $limit_date = date('Y-m-d', strtotime($due_date . " +$grace_days days"));

        // Só re-emitimos a fatura se o limite para pagamento ja tiver expirado (somando os dias de tolerência) e se o status for não-pago.
        if( 
            (
                (int) $getinvoiceResults['total'] !== (int) $billet_value || 
                $limit_date < date('Y-m-d') || 
                empty($billet)  || 
                $billetDuedate !== $due_date
            ) 
            && $getinvoiceResults['status'] == 'Unpaid') {
                $reissue = TRUE;
            }

        // Lógica: Checar se a data de vencimento + dias de tolerancia > data de hoje.
        if(isset($reissue) && $reissue) {
        
            // Pegamos as datas que definimos anteriormente e transformamos em objeto Date do PHP
            $data1 = new DateTime($billetDuedate); 
            $data2 = new DateTime($dataHoje);

            // Comparamos as datas para enviar o resultado a PagHiper. Isso é necessário pois o gateway pede o vencimento em número de dias no futuro, não como data.
            $intervalo = $data1->diff($data2); 
            $vencimentoBoleto = $intervalo->days;  


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
                $invoiceid = $_GET["invoiceid"];
                $urlRetorno = $systemurl.'/modules/gateways/'.basename(__FILE__);
                // Executamos o checkout transparente e printamos o resultado

                try {
                    echo httpPost($params,$GATEWAY,$invoiceid,$urlRetorno,$vencimentoBoleto);
                } catch (Exception $e) {
                    echo 'Erro ao solicitar boleto: ',  $e->getMessage(), "\n";
                }
                
                exit;
            }

        } else {
            //url_slip;
            echo fetch_remote_url($billet_url);

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

        // Agora vamos buscar o status da transação diretamente na PagHiper, usando a API.
        $url = "https://api.paghiper.com/transaction/notification/";
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
            logTransaction($GATEWAY["name"],$json,"Notificação Inválida."); 

        } elseif($request['result'] == 'success') {

            $invoice_id = $order_id;

            $status             = $request['status'];
            $transaction_id     = $request['transaction_id'];
            $ammount_paid       = to_monetary($request['value_cents_paid'] / 100);
            $transaction_fee    = to_monetary($request['value_fee_cents'] / 100);

            // Pegamos a fatura como array e armazenamos na variável para uso posterior
            $command = "getinvoice";
            $values["invoiceid"] = $order_id;
            $results = localAPI($command,$values,$whmcsAdmin);
            
                // Função que vamos usar na localAPI
                $addtransaction = "addtransaction";
                // Cliente fez emissão do boleto, logamos apenas como memorando
                if ($status == "pending" || $status == "Aguardando") {
                    $addtransvalues['userid'] = $results['userid'];
                    $addtransvalues['invoiceid'] = $order_id;
                    $addtransvalues['description'] = "Boleto gerado aguardando pagamento.";
                    $addtransvalues['amountin'] = '0.00';
                    $addtransvalues['fees'] = '0.00';
                    $addtransvalues['paymentmethod'] = 'paghiper';
                    $addtransvalues['transid'] = $transaction_id.'-Boleto-Gerado';
                    $addtransvalues['date'] = date('d/m/Y');
                    $addtransresults = localAPI($addtransaction,$addtransvalues,$whmcsAdmin);
                    logTransaction($GATEWAY["name"],$_POST,"Aguardando o Pagamento"); # Salva informações da transação no log do WHMCS.

                    // Logamos status no banco
                    log_status_to_db($status, $transaction_id);
                    
                // Transação foi aprovada
                } elseif ($status == "paid" || $status == "Aprovado") {

                    // Essa função checa se a transação ja foi registrada no banco de dados. 
                    checkCbTransID($transaction_id);

                    // Calcula a taxa cobrada pela PagHiper de maneira dinâmica e registra para uso no painel.
                    $fee = $transaction_fee;

                    // Se estiver tudo certo, checamos se o valor pago é diferente do configurado na fatura
                    if($results['total'] !== $ammount_paid) {

                            // Subtraimos o total do valor pago. Funciona tanto para desconto como acréscimo.
                            // Ex. 1: Valor pago | R$ 18 - R$ 20 (Total) = -R$ 2 [Desconto]
                            // Ex. 2: Valor pago | R$ 21 - R$ 20 (Total) = +R$ 1 [Multa]
                            $value = $ammount_paid - $results['total'];

                        if($results['total'] > $ammount_paid) {

                            // Conciliação: Desconto por antecipação (Valor total da Invoice - Valor total pago)
                            $desc = 'Desconto por pagamento antecipado';
                            add_to_invoice($invoice_id, $desc, $value, $whmcsAdmin);


                        } else {

                            // Conciliação: Juros e Multas = (Valor total pago - Valor contido na Invoice)
                            $desc = 'Juros e multa por atraso';
                            add_to_invoice($invoice_id, $desc, $value, $whmcsAdmin);

                        }
                    }

                    // Registramos o pagamento e damos baixa na fatura
                    addInvoicePayment($invoice_id,$transaction_id,$ammount_paid,$fee,'paghiper');

                    // Logamos a transação no log de Gateways do WHMCS.
                    logTransaction($GATEWAY["name"],$request,"Transação Concluída");

                    // Logamos status no banco
                    log_status_to_db($status, $transaction_id);
                // Transação Cancelada. 
                } else if ($status == "canceled" || $status == "Cancelado") {
                    // Boleto não foi pago, logamos apenas como memorando
                    logTransaction($GATEWAY["name"],$request,"Transação Cancelada");

                    // Logamos status no banco
                    log_status_to_db($status, $transaction_id);
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

function add_to_invoice($invoice_id, $desc, $value, $whmcsAdmin) {

    $postData = array(
        'invoiceid'             => (int) $invoice_id,
        'newitemdescription'    => array('PAGHIPER: '. $desc),
        'newitemamount'         => array($value),
        'newitemtaxed'          => 0
    );

    // Atualizamos a invoice com os valores novos
    $results = localAPI('UpdateInvoice',$postData,$whmcsAdmin);


}

function to_monetary($int) {
    return number_format ( $int, 2, '.', '' );
}

function log_status_to_db($status, $transaction_id) {
    //TODO: Implementar log de status na transação armazenada no banco
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
    $query = "SELECT userid, id, email, permissions, invoiceemails FROM tblcontacts WHERE userid = '".$user_id."' AND email = '".$email."' LIMIT 1"; 
    $result = mysql_query($query);
    $user = mysql_fetch_array($result);

    $allow_invoices = ((strpos($user['permissions'], 'invoices') || $user['invoiceemails'] == 1) && $invoice_userid == $user['userid'] ? TRUE : FALSE);
    if($allow_invoices) {
        return $user['userid'];
    }
    return false;
}
?>

<?php

/**
 * PagHiper - Módulo oficial para integração com WHMCS
 * 
 * @package    PagHiper para WHMCS
 * @version    1.12
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Colaboração de Henrique Cruz - Intelihost
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017, Henrique Cruz
 * @link       https://www.paghiper.com/
 */


/**
 * O arquivo foi acessado diretamente. 
 * Se trata de um post da PagHiper, de um boleto emitido com checkout transparente 
 * ou um link direto enviado por e-mail.
 */

// Opções padrão do Gateway
function paghiper_config($params) {
    $config = array(

        'FriendlyName' => array(
            "Type" => "System",
            "Value" => "PagHiper Boleto"
        ),

        "nota" => array(
            "FriendlyName" => "Nota",
            "Description" => "
            <table><tbody><tr><td width='60%'><img src='https://s3.amazonaws.com/logopaghiper/whmcs/badge.oficial.png' style='
    max-width: 100%;
  '></td><td>
Versão
  <h2 style='
    font-weight: bold;
    margin-top: 0px;
    font-size: 300%;
'>1.12</h2>
</td></tr></tbody></table>
   <h2>Para que o modulo funcione, siga as etapas abaixo:</h2>
   <ul>
   <li>Caso não possua uma conta PagHiper, <a href='https://www.paghiper.com/abra-sua-conta/' target='_blank'><strong> crie a sua aqui</strong></a> <br>
       Precisa de ajuda para criar sua conta? <a href='https://www.paghiper.com/duvidas/como-se-cadastrar-no-paghiper/' target='_blank'><strong> clique aqui e veja como criar de maneira rápida e facil.</strong></a><br></li>
   <li>Certifique-se que a conta esteja verificada e valida na página de <a href='https://www.paghiper.com/painel/detalhes-da-conta/' target='_blank'><strong>Detalhes da sua conta</strong></a> PagHiper</li>
   <li>Gere o seu token PagHiper na <a href='https://www.paghiper.com/painel/token/' target='_blank'><strong> pagina de token</strong></a></li>
   <li>Ative o prazo máximo de vencimento do boleto para 180 dias, no Painel do PagHiper  &gt; Ferramentas  &gt; Vencimento do Boleto ( <a href='https://www.paghiper.com/painel/prazo-vencimento-boleto/' target='_blank'><strong>Confira Aqui</strong></a> ). Somente assim, a data do vencimento do boleto será igual a da fatura.</li>
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

        "frasefixa" => array(
            "FriendlyName" => "Exibe ou não a frase do boleto",
            "Type" => "yesno"
        ),

        "porcento" => array(
            "FriendlyName" => "Taxa Percentual (%)",
            "Type" => "text",
            "Size" => "3",
            "Description" => "Porcentagem da fatura a se pagar a mais por usar o PagHiper. Ex.: (2.5). Obs.: não precisa colocar o % no final. Obs²: Use o ponto (.) como delimitador de casas decimais. <br> Recomendamos não cobrar nenhuma taxa."
        ),

        "integracao_avancada" => array(
            "FriendlyName" => "Integração Avançada",
            "Type" => "yesno",
            "Description" => "Ao ativar essa opção, as informações de retorno são armazenadas no banco de dados para uso posterior. Com isso, podemos fazer coisas como mandar o código de barras direto no corpo do e-mail de fatura, integrar o boleto ao PDF da fatura e reutilizar boletos ja emitidos. Para mais informações, acesse nossa <a href='https://github.com/paghiper/whmcs' target='_blank'>Página do GitHub</a>"
        ),

        "transparentcheckout" => array(
            "FriendlyName" => "Mostrar boleto usando Checkout Transparente?",
            "Type" => "yesno"
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

</ul><br>

<h2>Nota de Agradecimento</h2>
Esse modulo foi desenvolvido através da colaboração do desenvolvedor <strong><a href="https://henriquecruz.com.br" target="_blank">Henrique Cruz</a></strong> <br> <br>'
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

    $function = 'getinvoice';
    $getinvoiceid['invoiceid'] = $params['invoiceid'];
    $whmcsAdmin = $params['admin'];
    $getinvoiceResults = localAPI($function,$getinvoiceid,$whmcsAdmin);
  
    $invoiceDuedate = $getinvoiceResults['duedate']; // Data de vencimento da fatura
    $dataHoje = date('Y-m-d'); // Data de Hoje
    
    // Se a data de vencimento estiver no futuro ou presente
    if ( $invoiceDuedate >= date('Y-m-d') ) {

            // Usar a data de vencimento original
            $billetDuedate  = $invoiceDuedate; 

    // Caso contrario...
    } elseif ( $invoiceDuedate < date('Y-m-d') ) {

            // usar a data de hoje, acrescida de um dia extra como vencimento.
            $billetDuedate  = date('Y-m-d', strtotime('+1 day'));
            
    } 
    
    // instancia as datas para a comparacao de internvalos de dias
    $data1 = new DateTime($billetDuedate); 
    $data2 = new DateTime($dataHoje);

    // Calcula a diferença de dias entre o dia de hoje e a data de vencimento. Vamos usar esse número para enviar ao PagHiper.
    $intervalo = $data1->diff($data2); 
    $vencimentoBoleto = $intervalo->days;  

    // Definimos &&
    $systemurl = get_system_url($CONFIG, $params);
    $urlRetorno = $systemurl.'/modules/gateways/'.basename(__FILE__);

    // Pegamos os dados de CPF/CNPJ
    $cpf_field = 'customfields'.$params["cpf_cnpj"];
    $cpf_cnpj = $params['clientdetails'][$cpf_field];
    if(strpos($cpf_cnpj, '/') !== false) {
        $razao_social = $params['clientdetails']['companyname'];
        $cnpj = substr(trim(str_replace(array('+','-'), '', filter_var($cpf_cnpj, FILTER_SANITIZE_NUMBER_INT))), -14);

        $code_insert = '<input name="razao_social" type="hidden" value="'.$razao_social.'" />';
        $code_insert .= '<input name="cnpj" type="hidden" value="'.$cnpj.'" />';
    } else {
        $cpf = substr(trim(str_replace(array('+','-'), '', filter_var($cpf_cnpj, FILTER_SANITIZE_NUMBER_INT))), -15);
        $code_insert = '<input name="cpf" type="hidden" value="'.$cpf.'" />';
    }
    
    // faz os calculos (caso tenha definido um percentual ou taxa a mais, será incluida no calculo agora)
    $valorInvoice = apply_custom_taxes($params['amount'], $params); # Formato: ##.##
    
    ### abrir o boleto automaticamente ao abrir a fatura 
    if($params['abrirauto']==true):
        $target = '';
        $abrirAuto = "<script type='text/javascript'> document.paghiper.submit()</script>";
    else:
        $target =  "target='_blank'";
        $abrirAuto = ''; 
    endif;
// ALLOW CHECKOUT TRANSPARENTE
if($params['transparentcheckout'] == true) {
$code = "
<!-- INICIO DO FORM DO BOLETO PAGHIPER -->
    <form name=\"paghiper\" action=\"{$urlRetorno}?invoiceid={$params['invoiceid']}&uuid={$params['clientdetails']['userid']}&mail={$params['clientdetails']['email']}\" method=\"post\">
    <input type='image' src='https://www.paghiper.com/img/checkout/boleto/boleto-120px-69px.jpg' 
    title='Pagar com Boleto' alt='Pagar com Boleto' border='0'
     align='absbottom' /><br>
    <input type=\"submit\" value=\"Gerar boleto\">
    <!-- FIM DO BOLETO PAGHIPER -->
    </form>
    {$abrirAuto}";
} else {
$code = "
<!-- INICIO DO FORM DO BOLETO PAGHIPER -->
    <form name=\"paghiper\" {$target} action=\"https://www.paghiper.com/checkout/\" method=\"post\">
    <input name='email_loja' type='hidden' value='{$params['email']}'>
    <!-- Informações opcionais -->
    <input name='urlRetorno' type='hidden' value='{$urlRetorno}'>
    <input name='vencimentoBoleto' type='hidden' value='{$vencimentoBoleto}'>
    <!-- Dados do produto -->
    <input name='id_plataforma' type='hidden' value='{$params['invoiceid']}' />
    <input name='produto_codigo_1' type='hidden' value='{$params['invoiceid']}' />
    <input name='produto_valor_1' type='hidden' value='{$valorInvoice}'>
    <input name='produto_descricao_1' type='hidden' value='Fatura #{$params['invoiceid']}'>
    <input name='produto_qtde_1' type='hidden' value='1'>";

$code .= $code_insert;
$code .= "

    <!-- Dados do cliente -->
    <input name='email' type='hidden' value='{$params['clientdetails']['email']}' />
    <input name='nome' type='hidden' value='{$params['clientdetails']['firstname']} {$params['clientdetails']['lastname']}'>

    <input name='telefone' type='hidden' value='{$params['clientdetails']['phonenumber']}' />

    <input name='endereco' type='hidden' value='{$params['clientdetails']['address1']}' />
    <input name='bairro' type='hidden' value='{$params['clientdetails']['address2']}' />
    <input name='cidade' type='hidden' value='{$params['clientdetails']['city']}' />
    <input name='estado' type='hidden' value='{$params['clientdetails']['state']}' />
    <input name='cep' type='hidden' value='{$params['clientdetails']['postcode']}' />
    <input name='numero_casa' type='hidden' value='S/N' />
    <input name='complemento' type='hidden' value='{$params['clientdetails']['address2']}' />
    <input name='idPartners' type='hidden' value='D1J0M5GD' />    
    <input name='pagamento' type='hidden' id='pagamento'  />
    <input type='image' src='https://www.paghiper.com/img/checkout/boleto/boleto-120px-69px.jpg' 
    title='Pagar com Boleto' alt='Pagar com Boleto' border='0'
     align='absbottom' /><br>
    <input type=\"submit\" value=\"Gerar boleto\">
    <!-- FIM DO BOLETO PAGHIPER -->
    </form>
    {$abrirAuto}";
}
    


   return $code;                  
}

function httpPost($url,$params,$GATEWAY,$invoiceid,$urlRetorno,$vencimentoBoleto)
{
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

    //TODO
    $subtotal = apply_custom_taxes($subtotal, $GATEWAY, $params);
    
    // Preparate data to send
    $paghiper_data = array(
       "email_loja" => $GATEWAY['email'],
       "idPartners" => "D1J0M5GD",

       // Informações opcionais
       "urlRetorno" => $urlRetorno,
       "vencimentoBoleto" => $vencimentoBoleto,

       // Dados do produto
       "id_plataforma" => $invoiceid,
       "produto_codigo_1" => $invoiceid,
       "produto_valor_1" => $subtotal,
       "produto_descricao_1" => 'Fatura #'.$invoiceid,
       "produto_qtde_1" => 1,

       // Dados do cliente
       "email"      => $email,
       "nome"       => $firstname . ' ' . $lastname,
       "telefone"   => $phone,
       "endereco"   => $address1,
       "bairro"     => $address2,
       "cidade"     => $city,
       "estado"     => $$state,
       "cep"        => $postcode,
    );


    // Checa se incluimos dados CPF ou CNPJ no post
    if ($cpf != " " or $cpf != "on file") {
        if(strpos($cpf, '/') !== false) {
            $paghiper_data["razao_social"] = $companyname;
            $paghiper_data["cnpj"] = substr(trim(str_replace(array('+','-'), '', filter_var($cpf, FILTER_SANITIZE_NUMBER_INT))), -14);
        } else {
            $paghiper_data["cpf"] = substr(trim(str_replace(array('+','-'), '', filter_var($cpf, FILTER_SANITIZE_NUMBER_INT))), -15);
        }
    } elseif(!isset($cpfcnpj) || $cpfcnpj == '') {
        logTransaction($GATEWAY["name"],$_POST,"Boleto não exibido. Você não definiu o campo CPF/CNPJ");
    } elseif(!isset($cpf_cnpj) || $cpf_cnpj == '') {
        logTransaction($GATEWAY["name"],$_POST,"Boleto não exibido. CPF/CNPJ do cliente não foi informado");
    } else {
        logTransaction($GATEWAY["name"],$_POST,"Boleto não exibido. Erro indefinido");
    }

    if($GATEWAY['frasefixa'] == true) {
        $paghiper_data["frase_fixa_boleto"] = true;
    }

    $paghiper_data["pagamento"]  = "pagamento";

   // Pegar todos os dados que setamos e preparamos para enviar. Os dados vão todos na URL.
   foreach($paghiper_data as $k => $v) 
   { 
      $postData .= $k . '='.$v.'&'; 
   }
   $postData = rtrim($postData, '&');
 
    $ch = curl_init();  
 
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_HEADER, false); 
    curl_setopt($ch, CURLOPT_POST, count($postData));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

 
    $output=curl_exec($ch);
 
    curl_close($ch);
    return $output;
 
}

function check_table() {
    $table_create = full_query("CREATE TABLE IF NOT EXISTS `mod_paghiper` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `idTransacao` varchar(16) NOT NULL,
      `status` varchar(45) NOT NULL,
      `urlPagamento` varchar(255) DEFAULT NULL,
      `linhaDigitavel` varchar(54) DEFAULT NULL,
      `codRetorno` varchar(64) NOT NULL,
      `type` varchar(10) NOT NULL,
      PRIMARY KEY (`id`),
      KEY `idTransacao` (`idTransacao`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 PAGE_CHECKSUM=1 AUTO_INCREMENT=1 ;");
    if(!$table_create) {
        return false;
        logTransaction($GATEWAY["name"],$_POST,"Não foi possível gravar os dados no banco.");
    } else {
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
    $transparent_checkout = $GATEWAY['transparentcheckout'];

    // Se o usuário admin estiver vazio nas configurações, usamos o padrão
    $whmcsAdmin = (empty(trim($GATEWAY['admin'])) ? 'admin' : trim($GATEWAY['admin']));



    // Se as condições baterem, estamos lidando com um post do checkout transparente.
    if($GATEWAY['transparentcheckout'] == true && isset($_GET["invoiceid"])) {

        // Vamos precisar pegar a URL do sistema direto do banco de dados. A variável $params não está disponível nesse momento.
        $systemurl = ($CONFIG['SystemSSLURL'] ? $CONFIG['SystemSSLURL'] : $CONFIG['SystemURL']);

        $user_id = intval($_GET["uuid"]);
        $user_email = $_GET["mail"];
        //echo 'a';

        // Pegamos a fatura no banco de dados
        $getinvoice = 'getinvoice';
        $getinvoiceid['invoiceid'] = intval($_GET["invoiceid"]);
        $getinvoiceResults = localAPI($getinvoice,$getinvoiceid,$whmcsAdmin);
        //print_r($getinvoiceResults);
        //echo $user_id . ' / '. $getinvoiceResults['userid'];

        if(intval($getinvoiceResults['userid']) !== $user_id) {
                //echo 'ops!';
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
        
        // Pegamos as datas que definimos anteriormente e transformamos em objeto Date do PHP
        $data1 = new DateTime($billetDuedate); 
        $data2 = new DateTime($dataHoje);

        // Comparamos as datas para enviar o resultado a PagHiper. Isso é necessário pois o gateway pede o vencimento em número de dias no futuro, não como data.
        $intervalo = $data1->diff($data2); 
        $vencimentoBoleto = $intervalo->days;  

        // Checamos uma ultima vez se o ID da fatura não veio vazio
        if($_GET["invoiceid"] == '') {
            exit("Fatura inexistente");
        } else {
            $invoiceid = $_GET["invoiceid"];
            $urlRetorno = $systemurl.'modules/gateways/'.basename(__FILE__);

            // Executamos o checkout transparente e printamos o resultado
            echo httpPost("https://www.paghiper.com/checkout/",$params,$GATEWAY,$invoiceid,$urlRetorno,$vencimentoBoleto);
            exit;
        }
        
    // Caso contrário, é um post do PagHiper.
    } else {

        // Pegamos os campos enviados por POST. Vamos checar esses dados.
        $idTransacao    = $_POST['idTransacao'];
        $status         = $_POST['status'];
        $urlPagamento   = $_POST['urlPagamento'];
        $linhaDigitavel = $_POST['linhaDigitavel'];
        $codRetorno     = $_POST['codRetorno'];
        $valorOriginal  = $_POST['valorOriginal']; 
        $valorLoja      = $_POST['valorLoja'];
        $amountGateway  = $_POST['valorTotal'];
        $idPlataforma   = $_POST['idPlataforma'];

        // Calcula a taxa cobrada pela PagHiper de maneira dinâmica e registra para uso no painel.
        $fee = $_POST['valorTotal']-$_POST['valorLoja'];

        // Antes de mais nada, checamos se o ID da fatura existe no banco. 
        $invoiceid = checkCbInvoiceID($idPlataforma,$GATEWAY["name"]);

        // Vamos enviar esses dados a PagHiper pra termos certeza de que são autênticos
        $post = "idTransacao=$idTransacao" .
        "&status=$status" .
        "&codRetorno=$codRetorno" .
        "&valorOriginal=$valorOriginal" .
        "&valorLoja=$valorLoja" .
        "&token=$account_token";
        $enderecoPost = "https://www.paghiper.com/checkout/confirm/"; 

        // Prepara a chamada Curl que vamos usar.
        ob_start();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $enderecoPost);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resposta = curl_exec($ch); // Importante, verifica se o retorno automatico é verdadeiro
        curl_close($ch);

        // O valor que procuramos na confirmação da PagHiper é esse. Se der true, podemos prosseguir.
        $confirmado = (strcmp ($resposta, "VERIFICADO") == 0);
        
        // Só executamos o bloco condicional abaixo se o post da PagHiper for confirmado.
        if ($confirmado) {

            if($GATEWAY["integracao_avancada"] == TRUE) {
                $custom_table = check_table();
                if($custom_table) {
                    $log_transaction = full_query("INSERT INTO 'mod_paghiper' (idTransacao,status,urlPagamento,linhaDigitavel,codRetorno,type) VALUES ($idTransacao,$status,$urlPagamento,$linhaDigitavel,$codRetorno,'retorno'");
                    if(!$log_transaction) {
                        logTransaction($GATEWAY["name"],$_POST,"Não foi possível gravar os dados no banco.");
                    }
                }
            }

            // Pegamos a fatura como array e armazenamos na variável para uso posterior
            $command = "getinvoice";
            $values["invoiceid"] = $invoiceid;
            $results = localAPI($command,$values,$whmcsAdmin);
            
            // Abaixo evitamos que eventuais taxas extrar sejam adicionadas como crédito na conta do cliente.
            if($results['total'] <  $amountGateway):
               $amountGateway = $results['total'];
            endif;

            // Função que vamos usar na localAPI
            $addtransaction = "addtransaction";

                // Cliente fez emissão do boleto, logamos apenas como memorando
                if ($status == "Aguardando") {

                        $addtransvalues['userid'] = $results['userid'];
                        $addtransvalues['invoiceid'] = $invoiceid;
                        $addtransvalues['description'] = "Boleto gerado aguardando pagamento.";
                        $addtransvalues['amountin'] = '0.00';
                        $addtransvalues['fees'] = '0.00';
                        $addtransvalues['paymentmethod'] = 'paghiper';
                        $addtransvalues['transid'] = $idTransacao.'-Boleto-Gerado';
                        $addtransvalues['date'] = date('d/m/Y');
                        $addtransresults = localAPI($addtransaction,$addtransvalues,$whmcsAdmin);
                        logTransaction($GATEWAY["name"],$_POST,"Aguardando o Pagamento"); # Salva informações da transação no log do WHMCS.

                // Transação foi aprovada
                } else if ($status == "Aprovado") {

                    // Essa função checa se a transação ja foi registrada no banco de dados. 
                    checkCbTransID($idTransacao);

                    // Registramos o pagamento e damos baixa na fatura
                    addInvoicePayment($invoiceid,$idTransacao,$amountGateway,$fee,'paghiper');

                    // Logamos a transação no log de Gateways do WHMCS.
                    logTransaction($GATEWAY["name"],$_POST,"Transação Concluída");

                // Transação Cancelada. 
                } else if ($status == "Cancelado") {

                    // Boleto não foi pago, logamos apenas como memorando
                    logTransaction($GATEWAY["name"],$_POST,"Transação Cancelada");
                }

                //TODO
                // Prever todos os tipos de retorno.
        }
    
    
    }
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

?>

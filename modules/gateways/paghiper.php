<?php
    function paghiper_config()
    {
        return array(
            'FriendlyName' => array(
                "Type" => "System",
                "Value" => "PagHiper Boleto"
            ),
            "nota" => array(
                "FriendlyName" => "Nota",
                "Description" => "
                <p><H2>Para que o modulo funcione, siga as etapas abaixo:</H2></p>
                <strong>1)</strong> Caso não possua uma conta PagHiper, <a href='https://www.paghiper.com/abra-sua-conta/' target='_blank'><strong> crie a sua aqui</strong></a> <br><br>
                <strong>2</strong> Certifique-se que a conta esteja verificada e valida na página de <a href='https://www.paghiper.com/painel/detalhes-da-conta/' target='_blank'><strong>Detalhes da sua conta</strong></a> do PagHiper<br><br>
                <strong>3)</strong> Gere o seu token PagHiper na <a href='https://www.paghiper.com/painel/token/' target='_blank'><strong> pagina de token</strong></a>    <br><br>           
                <strong>4)</strong> Ative o prazo máximo de vencimento do boleto para 180 dias, no Painel do PagHiper  > Ferramentas  > Vencimento do Boleto ( <a href='https://www.paghiper.com/painel/prazo-vencimento-boleto/' target='_blank'><strong>Confira Aqui</strong></a> ). Somente assim, a data do vencimento do boleto será igual a da fatura.<br><br> 
                <strong>5</strong> Ative a integração entre o PagHiper e o <a href='https://www.paghiper.com/painel/whmcs' target='_blank'><strong>WHMCS</strong></a>, <a href='https://www.paghiper.com/painel/whmcs' target='_blank'><strong>Acesse aqui</strong></a> e ative.<br><br>
                <p>Se tiver qualquer duvida, visite a nossa <a href='https://www.paghiper.com/atendimento/' target='_blank'><strong>central de atendimento</strong></a></p>
                ",
                ),
            
            'email' => array(
                "FriendlyName" => "Email",
                "Type" => "text",
                "Size" => "100",
                "Description" => "Email da conta PagHiper que irá receber"
            ),
            'key' => array(
                "FriendlyName" => "Token",
                "Type" => "text",
                "Size" => "66",
                "Description" => "Extremamente importante, você pode gerar seu token em nossa pagina: Painel > Ferramentas > Token ( <a href='https://www.paghiper.com/painel/token/' target='_blank'><strong>Confira Aqui</strong></a> )."
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
                "Type" => "yesno",
            ),
            
            "admin" => array(
                "FriendlyName" => "Administrador atribuído",
                "Type" => "text",
                "Size" => "10",
                "Default" => "",
                "Description" => "Insira o nome de usuário ou ID do administrador do WHMCS que será atribuído as transações. Necessário para usar a API interna do WHMCS.",
		),
            
            'doacao' => array(
                "FriendlyName" => "<span class='label label-primary'><i class='fa fa-credit-card'></i> Doação</span>",
                "Description" => "A primeira versão do modulo foi desenvolvida pelo prejeto <a href='http://whmcs.red/doacao/victor.php' target='_blank'> WHMCS.RED </a>, você pode <a href='http://whmcs.red/doacao/victor.php' target='_blank'><strong><i class='fa fa-dollar'></i> doar qualquer valor</strong></a> para esse projeto se desejar.</a>."
            )
           
        );
    }
    if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
        
        header("access-control-allow-origin: *");
        require "../../init.php"; // esse arquivo deve ser impostado de acordo com o seu diretorio, geralmente ele fica na pasta principal do WHMCS
        
        $whmcs->load_function("gateway");
        $whmcs->load_function("invoice");
        $GATEWAY = getGatewayVariables("paghiper");

        
        $whmcsAdmin = $GATEWAY['admin']; // id ou nome de usuario admin do whmcs
        $token = $GATEWAY['key'];  // token do paghiper
        $valorOriginal = $_POST['valorOriginal'];  // consulte a documentacao https://www.paghiper.com/documentacao/
        $valorLoja = $_POST['valorLoja']; // consulte a documentacao https://www.paghiper.com/documentacao/
        $amountGateway = $_POST['valorTotal']; // consulte a documentacao https://www.paghiper.com/documentacao/
        $fee = $_POST['valorTotal']-$_POST['valorLoja']; // Tarifa da Transacao
        $status = $_POST['status']; // consulte a documentacao https://www.paghiper.com/documentacao/
        $idTransacao = $_POST['idTransacao']; // consulte a documentacao https://www.paghiper.com/documentacao/
        $idPlataforma = $_POST['idPlataforma']; // consulte a documentacao https://www.paghiper.com/documentacao/
        
        // PREPARA O POST PARA CONFIRMAR O RETORNO DIRETO NO PAGHIPER
        $post = "idTransacao=$idTransacao" .
        "&status=$status" .
        "&codRetorno=$codRetorno" .
        "&valorOriginal=$valorOriginal" .
        "&valorLoja=$valorLoja" .
        "&token=$token";
        $enderecoPost = "https://www.paghiper.com/checkout/confirm/"; 

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
  
        $invoiceid = checkCbInvoiceID($idPlataforma,$GATEWAY["name"]); # Confere se a ID do pedido é válida, se não, termina o programa.

        $confirmado = (strcmp ($resposta, "VERIFICADO") == 0);
        
         // Rotina para descobrir o userid da fatura
        $command = "getinvoice";
        $values["invoiceid"] = $invoiceid;
        $results = localAPI($command,$values,$adminuser);
        
        ###### IMPORTANTE
        # - se estiver ativado a opcao de cobrar taxas extras pelo uso do boleto, a condicacao abaixo ira evitar que a taxa extra entre como credito para o cliente
        if($results['total'] <  $amountGateway):
           $amountGateway = $results['total'];
        endif;
        ###### FIM IMPORTANTE
        
        
        
        
        if ($confirmado) // se o retorno automatico for confirmado
        {
                if ($status == "Aguardando")
                {

                        # Transação em Andamento
                        $addtransaction = "addtransaction";
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
                }
                else if ($status == "Aprovado")
                {
                        # Transação Aprovada
                        checkCbTransID($idTransacao); # Confere se a ID dessa transação não está na base de dados e termina o programa se já estiver.
                        addInvoicePayment($invoiceid,$idTransacao,$amountGateway,$fee,'paghiper'); # Adiciona o pagamento à transação no WHMCS.
                        logTransaction($GATEWAY["name"],$_POST,"Transação Concluída"); # Salva informações da transação no log do WHMCS.
                }
                else if ($status == "Cancelado")
                {
                        # Transação Cancelada
                        logTransaction($GATEWAY["name"],$_POST,"Transação Cancelada"); # Salva informações da transação no log do WHMCS.
                }
        }
    }

    function paghiper_link($params)
    {
        $getinvoice = 'getinvoice';
        $getinvoiceid['invoiceid'] = $params['invoiceid'];
        $whmcsAdmin = $params['admin'];
        $getinvoiceResults = localAPI($getinvoice,$getinvoiceid,$whmcsAdmin);
      
        $invoiceDuedate	= $getinvoiceResults['duedate']; // Data de vencimento da fatura
        $dataHoje = date('Y-m-d'); // Data de Hoje
        
        if ( $invoiceDuedate >= date('Y-m-d') ) { // Se a data do vencimento da fatura for maior que o dia de hoje
                $billetDuedate	= $invoiceDuedate; 

        } elseif( $invoiceDuedate < date('Y-m-d')) { // Se a data de vencimento da fatura for menor que o dia de hoje
                $billetDuedate	= date('Y-m-d', strtotime('+1 day')); // Se fatura já venceu, data de vencimento do boleto		= Hoje + 1 dia
                
        } 
        
        // instancia as datas para a comparacao de internvalos de dias
        $data1 = new DateTime($billetDuedate); 
        $data2 = new DateTime($dataHoje);

        $intervalo = $data1->diff($data2); 
        $vencimentoBoleto = $intervalo->days;  
        // fim intervalo de datas
        $urlRetorno = $params['systemurl'].'/modules/gateways/'.basename(__FILE__);
        
        // faz os calculos (caso tenha definido um percentual ou taxa a mais, será incluida no calculo agora)
        $valorInvoice = number_format(($params['amount']+((($params['amount'] / 100) * $params['porcento']) + $params['taxa'])), 2, '.', ''); # Formato: ##.##
        
        ### abrir o boleto automaticamente ao abrir a fatura 
        if($params['abrirauto']==true):
            $target = '';
            $abrirAuto = "<script type='text/javascript'> document.paghiper.submit()</script>";
        else:
            $target =  "target='_blank'";
            $abrirAuto = ''; 
        endif;
        
        
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
        <input name='produto_qtde_1' type='hidden' value='1'>

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
        <input name='idPartners' type='hidden' value='3N78MPCR' />    
        <input name='pagamento' type='hidden' id='pagamento'  />
        <input type='image' src='https://www.paghiper.com/img/checkout/boleto/boleto-120px-69px.jpg' 
        title='Pagar com Boleto' alt='Pagar com Boleto' border='0'
         align='absbottom' /><br>
        <input type=\"submit\" value=\"Gerar boleto\">
        <!-- FIM DO BOLETO PAGHIPER -->
        </form>
        {$abrirAuto}";
 
       return $code;                  
    }
?>

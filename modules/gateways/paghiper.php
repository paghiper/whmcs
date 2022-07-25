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

// Opções padrão do Gateway
function paghiper_config($params = null) {
    $custom_fields_conf = paghiper_get_customfield_id();

    $config = [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'PagHiper Boleto',
        ],
        'nota' => [
            'FriendlyName' => 'Nota',
            'Description' => "
            <table>
                <tbody>
                    <tr>
                        <td width='60%'><img src='https://s3.amazonaws.com/logopaghiper/whmcs/badge.oficial.png' style='max-width: 100%;'></td>
                        <td>Versão <h2 style='font-weight: bold; margin-top: 0px; font-size: 300%;'>2.3</h2></td>
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
           </ul>",
        ],

        'email' => [
            'FriendlyName' => 'Email',
            'Type' => 'text',
            'Size' => '100',
            'Description' => 'Email da conta PagHiper que irá receber',
        ],
        'api_key' => [
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '66',
            'Description' => "Campo composto de números, letras, traços e hífen.
Sempre começa por apk_. Caso não tenha essa informação, pegue sua chave API <a href='https://www.paghiper.com/painel/credenciais/' target='_blank'><strong>aqui</strong></a>.",
        ],
        'token' => [
            'FriendlyName' => 'Token',
            'Type' => 'text',
            'Size' => '66',
            'Description' => "Extremamente importante, você pode gerar seu token em nossa pagina: Painel > Ferramentas > Token ( <a href='https://www.paghiper.com/painel/token/' target='_blank'><strong>Confira Aqui</strong></a> ).",
        ],
        'cpf_cnpj' => [
            'FriendlyName' => 'ID do custom field contendo CPF/CNPJ',
            'Type' => 'text',
            'Size' => '3',
            'Description' => 'Defina aqui o ID do campo usado para coletar CPF/CNPJ do seu cliente. Isso é necessário para usar o checkout transparente.' . $custom_fields_conf,
        ],
        'razao_social' => [
            'FriendlyName' => 'ID do custom field contendo Razão Social',
            'Type' => 'text',
            'Size' => '3',
            'Description' => 'Defina aqui o ID do campo usado, caso utilize um campo personalizado para coletar a Razão Social do seu cliente. Isso é opcional.',
        ],
        'porcento' => [
            'FriendlyName' => 'Taxa Percentual (%)',
            'Type' => 'text',
            'Size' => '3',
            'Description' => 'Porcentagem da fatura a se pagar a mais por usar o PagHiper. Ex.: (2.5). Obs.: não precisa colocar o % no final. Obs²: Use o ponto (.) como delimitador de casas decimais. <br> Recomendamos não cobrar nenhuma taxa.',
        ],
        'taxa' => [
            'FriendlyName' => 'Taxa fixa',
            'Type' => 'text',
            'Size' => '7',
            'Description' => 'Taxa cobrada a mais do cliente por utilizar esse meio de pagamento, exemplo: 2.0 (dois reais). Obs: Use o ponto (.) como delimitador de casas decimais.<br> Recomendamos não cobrar nenhuma taxa.',
        ],
        'abrirauto' => [
            'FriendlyName' => 'Abrir boleto ao abrir fatura?',
            'Type' => 'yesno',
        ],
        'fixed_description' => [
            'FriendlyName' => 'Exibe ou não a frase fixa do boleto (configurada no painel da PagHiper)',
            'Type' => 'yesno',
        ],
        'open_after_day_due' => [
            'FriendlyName' => 'Tolerância para pagto',
            'Type' => 'text',
            'Size' => '2',
            'Description' => 'Número máximo de dias em que o boleto poderá ser pago após o vencimento. (Prática comum para quem opta por cobrar juros e multas).',
        ],
        'reissue_unpaid' => [
            'FriendlyName' => 'Vencimento padrão para boletos emitidos',
            'Type' => 'dropdown',
            'Options' => [
                '-1'    => 'Não permitir reemissão',
                '0'     => 'Vcto. no mesmo dia',
                '1'     => '+1 dia',
                '2'     => '+2 dias',
                '3'     => '+3 dias',
                '4'     => '+4 dias',
                '5'     => '+5 dias',
            ],
            'Description' => 'Escolha a quantidade de dias para o vencimento de boletos reemitidos (para faturas ja vencidas). Caso decida não permitir reemissão, você precisará mudar a data de vencimento manualmente.',
        ],
        'late_payment_fine' => [
            'FriendlyName' => 'Percentual da multa por atraso (%)',
            'Type' => 'text',
            'Size' => '1',
            'Description' => 'O percentual máximo autorizado é de 2%, de acordo artigo 52, parágrafo primeiro do Código de Defesa do Consumidor, Lei 8.078/90',
        ],
        'per_day_interest' => [
            'FriendlyName' => 'Juros proporcional',
            'Type' => 'yesno',
            'Description' => 'Ao aplicar 1% de juros máximo ao mês, esse percentual será cobrado proporcionalmente aos dias de atraso.<br><br>Dividindo 1% por 30 dias = 0,033% por dia de atraso.',
        ],
        'early_payment_discounts_days' => [
            'FriendlyName' => 'Qtde. de dias para aplicação de desconto',
            'Type' => 'text',
            'Size' => '2',
            'Description' => 'Número de dias em que o pagamento pode ser realizado com antecedência recebendo o desconto extra.',
        ],
        'early_payment_discounts_cents' => [
            'FriendlyName' => 'Desconto por pagto. antecipado',
            'Type' => 'text',
            'Size' => '6',
            'Description' => 'Valor do desconto que será aplicado caso o pagamento ocorra de forma antecipada. Em percentual (Ex.: 10%)',
        ],
        'issue_all' => [
            'FriendlyName' => 'Gerar boletos para todos os pedidos?',
            'Type' => 'dropdown',
            'Options' => [
                '1'    => 'Sim',
                '0'     => 'Não',
            ],
            'Description' => 'Caso selecione não, boletos bancários e lihnas digitáveis serão selecionadas somente caso o cliente selecione "Boleto Bancário" (ou o nome que você configurar no primeiro campo de configuração) como método de pagamento padrão.',
        ],
        "tax_id_validation" => array(
            "FriendlyName" => "Validar campos de CPF/CNPJ no checkout?",
            'Type' => 'dropdown',
            'Options' => array(
                'strict'        => 'Aceitar pedidos somente com CPF e CNPJ válidos',
                'flexible_f'    => 'Aceitar pedidos, desde que CPF seja válido',
                'flexible_j'    => 'Aceitar pedidos, desde que CNPJ seja válido',
                'flexible'      => 'Aceitar pedidos com CPF ou CNPJ válidos, tanto faz',
                '0'             => 'Não validar nenhum dos campos',
            ),
            'Description' => 'Caso selecione não, boletos bancários e lihnas digitáveis serão selecionadas somente caso o cliente selecione "Boleto Bancário" (ou o nome que você configurar no primeiro campo de configuração) como método de pagamento padrão.',
        ),
        "admin" => array(
            "FriendlyName" => "Administrador atribuído",
            "Type" => "text",
            "Size" => "10",
            "Default" => "admin",
            "Description" => "Insira o nome de usuário ou ID do administrador do WHMCS que será atribuído as transações. Necessário para usar a API interna do WHMCS."
        ),
        'suporte' => [
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
</ul><br>',
        ],

    ];

    return $config;
}

function paghiper_link($params) {

    // Definimos os dados para retorno e checkout.
    $systemurl = rtrim($params['systemurl'], '/');
    $urlRetorno = $systemurl . '/modules/gateways/' . basename(__FILE__);
    $customFields = explode('|', $params['cpf_cnpj']); // id dos campos de acordo com configuração do whmcs

    // Abrir o boleto automaticamente ao abrir a fatura
    if($params['abrirauto'] == true):
        $target = '';
    $abrirAuto = "<script type='text/javascript'> document.paghiper.submit()</script>"; else:
        $target =  "target='_blank'";
    $abrirAuto = '';
    endif;

    // Checamos o CPF/CNPJ novamente, para evitar problemas no checkout
    $taxIdFields = explode("|", $params['cpf_cnpj']);
    $payerNameField = $params['razao_social'];

    $clientCustomFields = [];
    foreach ($params['clientdetails']['customfields'] as $key => $value) {
        $clientCustomFields[$value['id']] = $value['value'];
    }

    $clientTaxIds = [];
    if (count($taxIdFields) > 1) {
        $clientTaxIds[] = $clientCustomFields[$taxIdFields[0]];
        $clientTaxIds[] = $clientCustomFields[$taxIdFields[1]];
    } else {
        $clientTaxIds[] = $clientCustomFields[$taxIdFields[0]];
    }

    $isValidTaxId = false;
    foreach ($clientTaxIds as $clientTaxId) {
        if (paghiper_is_tax_id_valid($clientTaxId)) {
            $isValidTaxId = true;

            break 1;
        }
    }

    $code = '';

    $isValidPayerName = true;
    $clientPayerName = $clientCustomFields[$payerNameField];
    foreach($clientTaxIds as $clientTaxId) {

        $taxid_value = preg_replace('/\D/', '', $clientTaxId);

        if(strlen( $taxid_value ) > 11 && empty($params['clientdetails']['companyname']) && empty($payerNameField) && empty($clientPayerName)) {

            $isValidPayerName = false;
            $code .= sprintf('<div class="alert alert-danger" role="alert">%s</div>', 'Razão social inválida, atualize seus dados cadastrais.');

        }
    }

    if($isValidTaxId) {

        $client_details = [
            'firstname' 	=> $params['clientdetails']['firstname'],
            'lastname'		=> $params['clientdetails']['lastname'],
            'companyname'	=> $params['clientdetails']['companyname'],
            'email'		    => $params['clientdetails']['email'],
            'phonenumber'	=> $params['clientdetails']['phonenumber'],
            'address1'		=> $params['clientdetails']['address1'],
            'address2'		=> $params['clientdetails']['address2'],
            'city'   		=> $params['clientdetails']['city'],
            'state'   		=> $params['clientdetails']['state'],
            'postcode'		=> $params['clientdetails']['postcode'],
            'cpf_cnpj'		=> $clientTaxId,
            'razao_social'  => $clientPayerName
        ];

        if($isValidPayerName) {
            // Código do checkout
            $code .= "<!-- INICIO DO FORM DO BOLETO PAGHIPER -->
            <form name=\"paghiper\" action=\"{$urlRetorno}?invoiceid={$params['invoiceid']}&uuid={$params['clientdetails']['userid']}&mail={$params['clientdetails']['email']}\" method=\"post\">
                <input type=\"hidden\" name=\"client_data\" value='".json_encode($client_details)."'>
                <input type='image' src='{$systemurl}/modules/gateways/paghiper/assets/img/billet.jpg' title='Pagar com Boleto' alt='Pagar com Boleto' border='0' align='absbottom' width='120' height='74' /><br>
                <button formtarget='_blank' class='btn btn-success' style='margin-top: 5px;' type=\"submit\"><i class='fa fa-barcode'></i> Gerar Boleto</button>
                <br> <br>
                <div class='alert alert-warning' role='alert'>
                <strong>Importante:</strong> A compensação bancária poderá levar até 2 dias úteis.
                </div>
                <!-- FIM DO BOLETO PAGHIPER -->
            </form>
            {$abrirAuto}";
        } else {
            $code = sprintf('<div class="alert alert-danger" role="alert">%s</div>', 'CPF ou CNPJ inválido, atualize seus dados cadastrais.');
        }

    } else {
        $code .= sprintf('<div class="alert alert-danger" role="alert">%s</div>', 'CPF ou CNPJ inválido, atualize seus dados cadastrais.');
    }

    return $code;
}

$is_pix = false;

require_once('paghiper/inc/helpers/gateway_functions.php');
require_once('paghiper/inc/helpers/process_payment.php');

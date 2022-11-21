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

use WHMCS\Database\Capsule;

function paghiper_get_customfield_id() {
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

function paghiper_add_to_invoice($invoice_id, $desc, $value, $whmcsAdmin) {
    $postData = [
        'invoiceid'             => (int) $invoice_id,
        'newitemdescription'    => ['PAGHIPER: ' . $desc],
        'newitemamount'         => [$value]
    ];

    // Atualizamos a invoice com os valores novos
    $results = localAPI('UpdateInvoice', $postData, $whmcsAdmin);
}

function paghiper_to_monetary($int) {
    return number_format($int, 2, '.', '');
}

function paghiper_log_status_to_db($status, $transaction_id) {
    $update = mysql_query("UPDATE mod_paghiper SET status = '$status' WHERE transaction_id = '$transaction_id';");
    if (!$update) {
        return false;
    }

    return true;
}

function paghiper_write_lock_id($lock_id, $transaction_id) {
    $update = mysql_query("UPDATE mod_paghiper SET lock_id = '$lock_id' WHERE transaction_id = '$transaction_id';");
    if (!$update) {
        return false;
    }

    return true;
}

function paghiper_get_lock_id($transaction_id) {
    $result = mysql_fetch_array(mysql_query("SELECT * FROM mod_paghiper WHERE transaction_id = '$transaction_id';"));
    if (!$result) {
        return false;
    }

    return $result['lock_id'];
}

function paghiper_fetch_remote_url($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output = curl_exec($ch);

    return $output;
}

function paghiper_convert_to_numeric($str) {
    return preg_replace('/\D/', '', $str);
}

function paghiper_query_scape_string($string) {
    if (function_exists('mysql_real_escape_string')) {
        return mysql_real_escape_string($string);
    }

    return mysql_escape_string($string);
}

function paghiper_apply_custom_taxes($amount, $GATEWAY, $params = null) {
    if (array_key_exists('amount', $params)) {
        $amount     = $params['amount'];
        $porcento   = $params['porcento'];
        $taxa       = $params['taxa'];
    } else {
        $porcento   = $GATEWAY['porcento'];
        $taxa       = $GATEWAY['taxa'];
    }

    return number_format(($amount+((($amount / 100) * $porcento) + $taxa)), 2, '.', ''); # Formato: ##.##
}

/**
 * Checa se o Tax ID é um CPF ou CNPJ e chama a função correspondente
 *
 * @param  string $cpf CPF a ser validado.
 *
 * @return bool
 */

function paghiper_is_tax_id_valid($cpf_cnpj) {
    $taxid_value = preg_replace('/\D/', '', $cpf_cnpj);

    if (strlen($taxid_value) > 11) {
        return paghiper_is_valid_cnpj($taxid_value);
    } else {
        return paghiper_is_valid_cpf($taxid_value);
    }
}

/**
 * Checa se o CNPJ informado é válido
 *
 * @param  string $cpf CPF a ser validado.
 *
 * @return bool
 */
function paghiper_is_valid_cpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    if (11 !== strlen($cpf) || preg_match('/^([0-9])\1+$/', $cpf)) {
        return false;
    }

    $digit = substr($cpf, 0, 9);

    for ($j = 10; $j <= 11; $j++) {
        $sum = 0;

        for ($i = 0; $i < $j - 1; $i++) {
            $sum += ($j - $i) * intval($digit[$i]);
        }

        $summod11 = $sum % 11;
        $digit[$j - 1] = $summod11 < 2 ? 0 : 11 - $summod11;
    }

    return intval($digit[9]) === intval($cpf[9]) && intval($digit[10]) === intval($cpf[10]);
}

/**
 * Checa se o CNPJ informado é válido
 *
 * @param  string $cnpj CNPJ a ser validado.
 *
 * @return bool
 */
function paghiper_is_valid_cnpj($cnpj) {
    $cnpj = sprintf('%014s', preg_replace('{\D}', '', $cnpj));

    if (14 !== strlen($cnpj) || 0 === intval(substr($cnpj, -4))) {
        return false;
    }

    for ($t = 11; $t < 13;) {
        for ($d = 0, $p = 2, $c = $t; $c >= 0; $c--, ($p < 9) ? $p++ : $p = 2) {
            $d += $cnpj[$c] * $p;
        }

        if (intval($cnpj[++$t]) !== ($d = ((10 * $d) % 11) % 10)) {
            return false;
        }
    }

    return true;
}

function paghiper_check_if_subaccount($user_id, $email, $invoice_userid) {
    $query = "SELECT userid, id, email, permissions, invoiceemails FROM tblcontacts WHERE userid = '$user_id' AND email = '$email' LIMIT 1";
    $result = mysql_query($query);
    $user = mysql_fetch_array($result);

    $allow_invoices = ((strpos($user['permissions'], 'invoices') || $user['invoiceemails'] == 1) && $invoice_userid == $user['userid'] ? true : false);
    if ($allow_invoices) {
        return $user['userid'];
    }

    return false;
}

function paghiper_print_screen($ico, $title, $message, $conf = null) {
    global $systemurl;
    $img_url = (preg_match('/http/i', $ico)) ? $ico : $systemurl . '/modules/gateways/paghiper/assets/img/' . $ico;
    $ico_style = ((!preg_match('/http/i', $ico)) ? 'style="max-width: 200px;"' : '');
    $code = ($code) ? $code : '';

    $is_pix             = ($conf && array_key_exists('is_pix', $conf) && $conf['is_pix'] === true) ? $conf['is_pix'] : false;
    $invoice_id         = ($conf && array_key_exists('invoice_id', $conf)) ? $conf['invoice_id'] : null;
    $pix_emv            = ($conf && array_key_exists('pix_emv', $conf)) ? $conf['pix_emv'] : null;
    $payment_value      = ($conf && array_key_exists('payment_value', $conf)) ? $conf['payment_value'] : null;

    $gateway_configs = getGatewayVariables('paghiper_pix');
    $disconto_pix_config = isset($gateway_configs['disconto_pagamento_pix']) ? $gateway_configs['disconto_pagamento_pix'] : '';

    if ($is_pix) {
        $discounts_percentage = paghiper_get_discount_percentage_for_invoice($invoice_id, $gateway_configs);

        if ($discounts_percentage !== 0.0) {
            $valor_convertido = $payment_value / (1 - $discounts_percentage);
        }
    }

    $upper_instructions = ($conf && array_key_exists('upper_instructions', $conf)) ? $conf['upper_instructions'] : null;
    $lower_instructions = ($conf && array_key_exists('lower_instructions', $conf)) ? $conf['lower_instructions'] : null;

    $page_title = ($is_pix) ? 'Pagamento por PIX' : $title;
    $title      = ($is_pix) ? '' : "<h3>{$title}</h3>";
    $message    = ($is_pix) ? '' : "<p>{$message}</p>";

    $lateral_instructions = ($is_pix) ? "<div class='ul-container'><ul>
        <li>Abra o app do seu banco ou instituição financeira e <strong>entre no ambiente Pix</strong>.</li>
        <li>Escolha a opção <strong>Pagar com QR Code</strong> e escanele o código ao lado.</li>
        <li>Confirme as informações e finalize o pagamento.</li>
    </ul></div>" : '';

    $upper_instructions = ($is_pix) ? (($invoice_id) ? sprintf('<h3>Fatura #%s</h3>', $invoice_id) : '') : '';
    if ($is_pix) {
        if (isset($valor_convertido) && !empty($valor_convertido)) {
            $payment_value = number_format($payment_value, 2, ',', '.');
            $payment_value_no_discount = number_format($valor_convertido, 2, ',', '.');

            $upper_instructions .= sprintf(
                '<p><s>De R$ %s</s></p>
                <p>por R$ %s</p>',
                $payment_value_no_discount,
                $payment_value
            );
        } else {
            $upper_instructions .= sprintf('<p>Valor: R$ %s</p>', number_format($payment_value, 2, ',', '.'));
        }
    }

    $lower_instructions = ($is_pix) ? sprintf('
                    <div id="emvCode" data-emv="%s">
                        <pre>%s</pre>
                        <button>
                            <span>Pagar com <strong>PIX copia e cola</strong></span>
                        </button>
                    </div>
                    <div>Após o pagamento, podemos levar alguns segundos para confirmar o seu pagamento.<br>
                    Você será avisado assim que isso ocorrer!</div>', $pix_emv, $pix_emv) : '';

    $code = "
        <!DOCTYPE html>
        <html>

        <head>
          <meta charset='utf-8'>
          <title>{$page_title}</title>
          <meta name='author' content='>
          <meta name='description' content='>
          <meta name='viewport' content='width=device-width, initial-scale=1'>
        </head>

        <body>
        <link href='https://fonts.googleapis.com/css?family=Open+Sans:300,600' rel='stylesheet'>

        <div class='container'>
          <div>
            <div class='img-container'>
                <div>
                    <img {$ico_style} src='{$img_url}'>
                    {$upper_instructions}
                </div>

                {$lateral_instructions}
            </div>

            {$lower_instructions}

            {$title}
            {$message}

          </div>
        </div>

        <style type='text/css'>
            html, body {
              width: 100%;
              height: 100%;
              overflow-x: auto;
              margin: 0px;
              padding: 0px;
            }
            * {
              font-family: Open Sans;
            }
            .container {
              width: 100%;
              height: 100%;
              display: inline-flex;
              flex-direction: column;
              justify-content: center;
            }
            .container div {
              text-align: center;
            }
            .container div * {
              max-width: 90%;
              margin: 0px auto;
            }

            #emvCode {
                background: #cfd4e1;
                padding: 10px;
                margin: 20px auto !important;
                border-radius: 4px;
                border: 1px solid #545d6f;
                color: #545d6f;
                position: relative;
                text-align: left;
                padding-right: 180px;
                cursor: pointer;
            }

            #emvCode pre {
                white-space: normal;
                line-break: anywhere;
            }

            button {
                background: #545d6f;
                color: white;
                padding: 5px 10px;
                position: absolute;
                right: 4px;
                bottom: 4px;
                border: unset;
                border-radius: 3px;
                width: 160px;
                height: calc(100% - 8px);
                vertical-align: middle;
                display: flex;
                justify-content: center;
                flex-direction: column;
                align-items: center;
                cursor: pointer;
            }

            .ul-container {
                display: flex;
                flex-direction: column;
                justify-content: center;
                flex-grow: 1;
            }

            .ul-container ul {
                text-align: left;
                list-style-type: none;
                max-width: 400px;
                margin: 0px;
            }

            .ul-container ul li {
                margin-bottom: 40px !important;
                padding-left: 65px;
                position: relative;
            }

            .ul-container ul li:before {
                content: '';
                position: absolute;
                display: block;
                width: 50px;
                height: 50px;
                border-radius: 40px;
                left: 0px;
                top: 50%;
                transform: translateY(-50%);
                background-size: contain;
                box-shadow: 0 2px 4px rgba(0, 0, 0, .15);
            }

            .ul-container ul li:first-child:before {     background-image: url({$systemurl}/modules/gateways/paghiper/assets/img/ico_1-app.png); }
            .ul-container ul li:nth-child(2):before { background-image: url({$systemurl}/modules/gateways/paghiper/assets/img/ico_2-qr.png); }
            .ul-container ul li:last-child:before { background-image: url({$systemurl}/modules/gateways/paghiper/assets/img/ico_3-ok.png); }

            @media (max-width: 980px) {

                body {
                    font-size: 25px;
                }

                .ul-container ul {
                    max-width: 80%;
                    margin: 60px auto 20px;
                }

                img {
                    min-width: 70%;
                }

                #emvCode {
                    padding: 20px 10px 90px;
                }

                button {
                    width: calc(100% - 8px);
                    max-width: unset !important;
                    height: 70px;
                    text-align: center;
                    flex-direction: row;

                    font-size: 18px
                }
            }

            @media only screen and (min-width: 1024px) {

                .container div * {
                    max-width: 940px;
                }

                #emvCode {
                    max-width: calc(940px - 180px) !important;
                }

                button strong {
                    display: block;
                }

                .img-container {
                    display: flex;
                }

            }
        </style>
        <script>
            var emvContainer = document.getElementById('emvCode');
            var emvCode = emvContainer.getAttribute('data-emv');

            emvContainer.addEventListener('click', function() {
                alert('Código PIX copiado!');

                // Create new element
                var el = document.createElement('textarea');
                // Set value (string to be copied)
                el.value = emvCode;
                // Set non-editable to avoid focus and move outside of view
                el.setAttribute('readonly', '');
                el.style = {position: 'absolute', left: '-9999px'};
                document.body.appendChild(el);
                // Select text inside element
                el.select();
                // Copy text to clipboard
                document.execCommand('copy');
                // Remove temporary element
                document.body.removeChild(el);
            });
        </script>
        </body>

        </html>";

    return $code;
}

/**
 * @since 2.4.0
 *
 * @param string $discount_rule
 * @param int $invoice_id
 *
 * @return bool
 */
function does_invoice_meet_discount_rule($discount_rule, $invoice_id) {
    if ($discount_rule === 'new_orders') {
        $hasOrderInPedingStatus = Capsule::table('tblorders')
            ->where('invoiceid', $invoice_id)
            ->where('status', 'Pending')
            ->exists();

        return $hasOrderInPedingStatus;
    }
}

/**
 * @since 1.0.0
 *
 * @param  string $value
 *
 * @return float
 */
function paghiper_pix_string_to_float($value) {
    return round(floatval(str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $value))), 2);
}

/**
 * @since 1.0.0
 * @link https://documentation.com
 *
 * @param  string $invoice_id
 * @param  array $gateway_settings
 *
 * @return float
 */
function paghiper_get_discount_percentage_for_invoice($invoice_id, $gateway_settings) {
    $total_discount_percentage = 0.0;

    $discount_pix = paghiper_pix_string_to_float($gateway_settings['disconto_pagamento_pix'] ?? '');

    if ($discount_pix !== 0.0) {
        $discount_pix_percentage = $discount_pix / 100;
        $total_discount_percentage += $discount_pix_percentage;
    }

    $discount_rule = $gateway_settings['discount_rule'] ?? 'disabled';
    $discount_rule_percentage = paghiper_pix_string_to_float($gateway_settings['discount_rule_percentage'] ?? '');

    if (
        $discount_rule !== 'disabled' &&
        $discount_rule_percentage !== 0.0 &&
        does_invoice_meet_discount_rule($discount_rule, $invoice_id)
    ) {
        $discount_rule_percentage = $discount_rule_percentage / 100;

        $total_discount_percentage += $discount_rule_percentage;
    }

    return $total_discount_percentage;
}

function generate_paghiper_billet($invoice, $params) {
    global $return_json, $is_pix;

    // Prepare variables that we'll be using during the process
    $postData    = [];

    // Data received from the invoice
    $total = $invoice['balance'];

    if ($is_pix) {
        $discounts_percentage = paghiper_get_discount_percentage_for_invoice($invoice['invoiceid'], $params['gateway_settings']);

        if ($discounts_percentage !== 0.0) {
            $total = $total - ($total * $discounts_percentage);
        }
    }

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
    $gateway_settings 	= $params['gateway_settings'];

    $notification_url 	= $params['notification_url'];
    $cpfcnpj 			= $gateway_settings['cpf_cnpj'];
    $razaosocial        = $gateway_settings['razao_social'];

    // Data received through function params
    $invoice_id			= $invoice['invoiceid'];
    $client_id 			= $invoice['userid'];

    if (empty($cpf_cnpj)) {
        // Checamos se o campo é composto ou simples
        if (strpos($cpfcnpj, '|')) {
            // Se composto, pegamos ambos os campos
            $fields = explode('|', $cpfcnpj);

            $i = 0;

            foreach ($fields as $field) {
                $result  = mysql_fetch_array(mysql_query("SELECT * FROM tblcustomfieldsvalues WHERE relid = '$client_id' and fieldid = '" . trim($field) . "'"));
                ($i == 0) ? $cpf = paghiper_convert_to_numeric(trim($result['value'])) : $cnpj = paghiper_convert_to_numeric(trim($result['value']));
                if ($i == 1) {
                    break;
                }
                $i++;
            }
        } else {
            // Se simples, pegamos somente o que temos
            $cpf_cnpj     = paghiper_convert_to_numeric(trim(array_shift(mysql_fetch_array(mysql_query("SELECT value FROM tblcustomfieldsvalues WHERE relid = '$client_id' and fieldid = '$cpfcnpj'")))));
        }
    }

    if (empty($cpf) && empty($cnpj)) {
        if (strlen($cpf_cnpj) > 11) {
            $cnpj = $cpf_cnpj;
        } else {
            $cpf = $cpf_cnpj;
        }
    }

    // Validate CPF/CNPJ
    if (!paghiper_is_tax_id_valid($cpf) && !paghiper_is_tax_id_valid($cnpj)) {
        $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
        $title = 'Ops! Não foi possível emitir o ' . ((!$is_pix) ? 'boleto bancário' : 'PIX') . '.';
        $message = 'Número de CPF/CNPJ inválido! Por favor atualize seus dados ou entre em contato com o suporte';

        echo paghiper_print_screen($ico, $title, $message);
        logTransaction($gateway_settings['name'], ['tax_id' => (!empty($cnpj)) ? $cnpj : $cpf, 'invoice_id' => $invoice_id, 'exception' => 'Failed Paghiper TaxID validation'], sprintf('Número de CPF/CNPJ inválido! Não foi possível gerar o %s.', ($is_pix) ? 'PIX' : 'boleto'));
        exit();
    }


    // Aplicamos as taxas do gateway sobre o total
    $total = paghiper_apply_custom_taxes($total, $gateway_settings, $params);

    // Preparate data to send
    $paghiper_data = [
        'apiKey'                         => $gateway_settings['api_key'],
        'partners_id'                    => (($is_pix) ? '98IS0XYC' : '12WIT2XD'),
        'order_id'                       => $invoice_id,

        // Informações para a criação e liquidação da fatura
        'notification_url'               => $notification_url,
        'days_due_date'                  => $due_date,
        'type_bank_slip'                 => 'boletoA4',

        // Dados da fatura
        'items'                          =>  [
            [
                'item_id'       => $invoice_id,
                'description'   => 'Fatura #' . $invoice_id,
                'price_cents'   => paghiper_convert_to_numeric($total),
                'quantity'      => 1
            ],
        ],

        // Dados do cliente
        'payer_email'                    => $email,
        'payer_name'                     => $firstname . ' ' . $lastname,
        'payer_phone'                    => paghiper_convert_to_numeric($phone),
        'payer_street'                   => $address1,
        'payer_complement'               => $address2,
        'payer_city'                     => $city,
        'payer_state'                    => $state,
        'payer_zip_code'                 => $postcode,
    ];

    // Checa se incluimos dados CPF ou CNPJ no post
    if ((isset($cpf) && !empty($cpf) && $cpf != 'on file') || (isset($cnpj) && !empty($cnpj) && $cnpj != 'on file')) {
        if (isset($cnpj) && !empty($cnpj) && paghiper_is_tax_id_valid($cnpj)) {
            if (isset($companyname) && !empty($companyname)) {
                $paghiper_data['payer_name'] = $companyname;
            }

            if (empty($razaosocial_val)) {
                if (isset($razaosocial) && !empty($razaosocial) && isset($cnpj) && !empty($cnpj)) {
                    $razaosocial_val = trim(array_shift(mysql_fetch_array(mysql_query("SELECT value FROM tblcustomfieldsvalues WHERE relid = '$client_id' and fieldid = '$razaosocial'"))));
                }
            }

            if (isset($razaosocial_val) && !empty($razaosocial_val) && strlen($razaosocial_val) > 5) {
                $paghiper_data['payer_name'] =  $razaosocial_val;
            }

            $paghiper_data['payer_cpf_cnpj'] = substr(trim(str_replace(['+', '-'], '', filter_var($cnpj, FILTER_SANITIZE_NUMBER_INT))), -14);
        } else {
            $paghiper_data['payer_cpf_cnpj'] = substr(trim(str_replace(['+', '-'], '', filter_var($cpf, FILTER_SANITIZE_NUMBER_INT))), -15);
        }
    } elseif (!isset($cpfcnpj) || $cpfcnpj == '') {
        logTransaction($gateway_settings['name'], ['post' => $_POST, 'json' => $paghiper_data], 'Boleto não exibido. Você não definiu os campos de CPF/CNPJ');
    } elseif (!isset($cpf_cnpj) || $cpf_cnpj == '' || (empty($cpf) && empty($cnpj))) {
        logTransaction($gateway_settings['name'], ['post' => $_POST, 'json' => $paghiper_data], 'Boleto não exibido. CPF/CNPJ do cliente não foi informado');
    } else {
        logTransaction($gateway_settings['name'], ['post' => $_POST, 'json' => $paghiper_data], 'Boleto não exibido. Erro indefinido');
    }

    // Checamos os valores booleanos, 1 por 1
    // Dados do boleto
    $additional_config_boolean = [
        'fixed_description'             => $gateway_settings['fixed_description'],
        'per_day_interest'              => $gateway_settings['per_day_interest'],
    ];

    foreach ($additional_config_boolean as $k => $v) {
        if ($v === true || $v === false) {
            $paghiper_data[$k] = $v;
        } elseif ($v == 'on') {
            $paghiper_data[$k] = true;
        } else {
            $paghiper_data[$k] = false;
        }
    }

    $discount_config = (!empty($gateway_settings['early_payment_discounts_cents'])) ? (float) trim(str_replace(['%', ','], ['', '.'], $gateway_settings['early_payment_discounts_cents']), 0) : '';
    $discount_value = (!empty($discount_config)) ? $total * (($discount_config > 99) ? 99 / 100 : $discount_config / 100) : '';
    $discount_cents = (!empty($discount_value)) ? paghiper_convert_to_numeric(number_format($discount_value, 2, '.', '')) : 0;

    if (($total - $discount_value) < 3) {
        // Mostrar tela de boleto cancelado
        $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
        $title = 'Não foi possível gerar o ' . (($is_pix) ? 'PIX' : 'boleto') . '!';
        $message = 'O valor com desconto por pagto. antecipado é inferior a R$3,00! Por favor, revise a configuração.';
        echo paghiper_print_screen($ico, $title, $message);
        exit();
    }

    $additional_config_text = [
        'early_payment_discounts_days'  => $gateway_settings['early_payment_discounts_days'],
        'early_payment_discounts_cents' => $discount_cents,
        'open_after_day_due'            => $gateway_settings['open_after_day_due'],
        'late_payment_fine'             => $gateway_settings['late_payment_fine'],
        'open_after_day_due'            => ($is_pix) ? 0 : $gateway_settings['open_after_day_due'],
    ];

    foreach ($additional_config_text as $k => $v) {
        if (!empty($v)) {
            $paghiper_data[$k] = paghiper_convert_to_numeric($v);
        }
    }

    $data_post = json_encode($paghiper_data);

    $url = ($is_pix) ? 'https://pix.paghiper.com/invoice/create/' : 'https://api.paghiper.com/transaction/create/';
    $mediaType = 'application/json'; // formato da requisição
    $charset = 'UTF-8';
    $headers = [];
    $headers[] = 'Accept: ' . $mediaType;
    $headers[] = 'Accept-Charset: ' . $charset;
    $headers[] = 'Accept-Encoding: ' . $mediaType;
    $headers[] = 'Content-Type: ' . $mediaType . ';charset=' . $charset;
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
    if ($httpCode == 201) {
        // Exemplo de como capturar a resposta json
        $transaction_type 	        = ($is_pix) ? 'pix' : 'billet';
        $transaction_id 	        = ($is_pix) ? $json['pix_create_request']['transaction_id'] : $json['create_request']['transaction_id'];
        $order_id 			        = ($is_pix) ? $json['pix_create_request']['order_id'] : $json['create_request']['order_id'];
        $due_date 			        = ($is_pix) ? $json['pix_create_request']['due_date'] : $json['create_request']['due_date'];
        $status 			        = ($is_pix) ? $json['pix_create_request']['status'] : $json['create_request']['status'];
        $url_slip 			        = ($is_pix) ? null : $json['create_request']['bank_slip']['url_slip'];
        $url_slip_pdf 		        = ($is_pix) ? null : $json['create_request']['bank_slip']['url_slip_pdf'];
        $digitable_line 	        = ($is_pix) ? null : $json['create_request']['bank_slip']['digitable_line'];
        $bar_code_number_to_image   = ($is_pix) ? null : $json['create_request']['bank_slip']['bar_code_number_to_image'];
        $open_after_day_due         = ($is_pix) ? 0 : $gateway_settings['open_after_day_due'];

        $qrcode_base64 		        = ($is_pix) ? $json['pix_create_request']['pix_code']['qrcode_base64'] : null;
        $qrcode_image_url 	        = ($is_pix) ? $json['pix_create_request']['pix_code']['qrcode_image_url'] : null;
        $emv 				        = ($is_pix) ? $json['pix_create_request']['pix_code']['emv'] : null;
        $bacen_url 			        = ($is_pix) ? $json['pix_create_request']['pix_code']['bacen_url'] : null;
        $pix_url 			        = ($is_pix) ? $json['pix_create_request']['pix_code']['pix_url'] : null;

        $slip_value = $total;

        try {
            $sql = "INSERT INTO mod_paghiper (transaction_type,transaction_id,order_id,due_date,status,url_slip,url_slip_pdf,digitable_line,bar_code_number_to_image,open_after_day_due,slip_value,qrcode_base64,qrcode_image_url,emv,bacen_url,pix_url) VALUES ('$transaction_type', '$transaction_id','$order_id','$due_date','$status','$url_slip','$url_slip_pdf','$digitable_line','$bar_code_number_to_image', '$open_after_day_due','$slip_value','$qrcode_base64','$qrcode_image_url','$emv','$bacen_url','$pix_url');";

            $query = full_query($sql);
        } catch (Exception $e) {
            $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
            $title = 'Ops! Não foi possível emitir o ' . (($is_pix) ? 'boleto bancário' : 'PIX') . '.';
            $message = 'Por favor entre em contato com o suporte. Erro 0x004681';

            echo paghiper_print_screen($ico, $title, $message);
            logTransaction($GATEWAY['name'], ['json' => $json, 'query' => $sql, 'query_result' => $query, 'exception' => $e], 'Não foi possível inserir a transação no banco de dados. Por favor entre em contato com o suporte.');
            exit();
        }


        if ($return_json) {
            header('Content-Type: application/json');

            return ($is_pix) ? json_encode($json['pix_create_request']) : json_encode($json['create_request']);
        }

        if (!empty($qrcode_image_url)) {
            return paghiper_print_screen($qrcode_image_url, null, null, ['is_pix' => true, 'invoice_id' => $order_id, 'payment_value' => $slip_value, 'pix_emv' => $emv]);
        } else {
            return paghiper_fetch_remote_url($url_slip);
        }
    } else {
        // Não foi possível solicitar o boleto.
        $ico = ($is_pix) ? 'pix-cancelled.png' : 'billet-cancelled.png';
        $title = 'Ops! Não foi possível emitir o ' . (($is_pix) ? 'boleto bancário' : 'PIX') . '.';
        $message = 'Por favor entre em contato com o suporte. Erro 0x004682';

        echo paghiper_print_screen($ico, $title, $message);

        logTransaction($GATEWAY['name'], ['json' => $json, 'post' => $_POST, 'request' => $data_post], 'Não foi possível criar a transação.');

        return false;
    }
}

function paghiper_check_table() {
    $checktable = full_query("SHOW TABLES LIKE 'mod_paghiper'");
    $table_exists = mysql_num_rows($checktable) > 0;

    if ($table_exists) {
        $table_columns = full_query("SHOW COLUMNS FROM `mod_paghiper` LIKE 'transaction_id';");
        if (mysql_num_rows($table_columns) == 0) {
            $delete_table = full_query('DROP TABLE mod_paghiper;');
            if ($delete_table) {
                create_paghiper_table();
            }
        }

        // TODO Checar todos os campos e modificar as tabelas a partir de uma lista dinâmica
        $slip_value = full_query("SHOW COLUMNS FROM `mod_paghiper` WHERE `field` = 'slip_value' AND `type` = 'decimal(11,2)'");
        if (mysql_num_rows($slip_value) == 0) {
            $alter_table = full_query('ALTER TABLE `mod_paghiper` CHANGE `slip_value` `slip_value` DECIMAL(11,2) NULL DEFAULT NULL;');
            if (!$alter_table) {
                logTransaction($GATEWAY['name'], $_POST, 'Não foi possível alterar o formato de dados da coluna slip_value. Por favor altere manualmente para decimal(11,2).');
            }
        }

        $transaction_type = full_query("SHOW COLUMNS FROM `mod_paghiper` WHERE `field` = 'transaction_type'");
        if (mysql_num_rows($transaction_type) == 0) {
            $alter_table = full_query('ALTER TABLE `mod_paghiper` ADD COLUMN transaction_type varchar(45) DEFAULT NULL AFTER id, ADD COLUMN bar_code_number_to_image varchar(54) AFTER digitable_line, ADD COLUMN qrcode_base64 varchar(255) DEFAULT NULL, ADD COLUMN qrcode_image_url varchar(255) DEFAULT NULL, ADD COLUMN emv varchar(255) DEFAULT NULL, ADD COLUMN pix_url varchar(255) DEFAULT NULL, ADD COLUMN bacen_url varchar(255) DEFAULT NULL;');
            if (!$alter_table) {
                logTransaction($GATEWAY['name'], $_POST, 'Não foi possível adicionar os campos para suporte ao PIX. Por favor cheque se o usuário MySQL tem permissões para alterar a tabela mod_paghiper');
            }
        }

        $lock_id = full_query("SHOW COLUMNS FROM `mod_paghiper` WHERE `field` = 'lock_id'");
        if (mysql_num_rows($lock_id) == 0) {
            $alter_table = full_query('ALTER TABLE `mod_paghiper` ADD COLUMN lock_id varchar(64) DEFAULT NULL AFTER bacen_url;');
            if (!$alter_table) {
                logTransaction($GATEWAY['name'], $_POST, 'Não foi possível atualizar o banco de dados da Paghiper para a versão 1.4. Por favor cheque se o usuário MySQL tem permissões para alterar a tabela mod_paghiper');
            }
        }
    } else {
        create_paghiper_table();
    }


    if ($result = full_query("SHOW TABLES LIKE 'mod_paghiper'")) {
        if ($result->num_rows == 1) {
            echo 'Table exists';
        }
    } else {
        echo 'Table does not exist';
    }
}

function create_paghiper_table() {
    $table_create = full_query('CREATE TABLE IF NOT EXISTS `mod_paghiper` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
		  `transaction_type` varchar(45) DEFAULT NULL,
          `transaction_id` varchar(16) NOT NULL,
          `order_id` int(11) NOT NULL,
          `due_date` date,
          `status` varchar(45) NOT NULL,
          `url_slip` varchar(255) DEFAULT NULL,
          `url_slip_pdf` varchar(255) DEFAULT NULL,
          `digitable_line` varchar(54) DEFAULT NULL,
          `bar_code_number_to_image` varchar(54) DEFAULT NULL,
          `open_after_day_due` int(2) DEFAULT NULL,
          `slip_value` decimal(11,2) DEFAULT NULL,
		  `qrcode_base64` varchar(255) DEFAULT NULL,
		  `qrcode_image_url` varchar(255) DEFAULT NULL,
		  `emv` varchar(255) DEFAULT NULL,
		  `pix_url` varchar(255) DEFAULT NULL,
		  `bacen_url` varchar(255) DEFAULT NULL,
		  `lock_id` varchar(64) DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `transaction_id` (`transaction_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;');

    if (!$table_create) {
        logTransaction($GATEWAY['name'], $_POST, 'Não foi possível criar o banco de dados para armazenamento das faturas.');

        return false;
    } else {
        logTransaction($GATEWAY['name'], $_POST, 'Banco de dados criado com sucesso');

        return true;
    }
}

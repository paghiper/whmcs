<?php
/**
 * Mostra campos da Paghiper na lista de campos disponíveis para uso nos templates
 * @author     Henrique Cruz
 * @copyright  Copyright (c) 2021 https://www.paghiper.com/
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");
function show_paghiper_tpl_fields($vars) {
    $merge_fields = [];
    $merge_fields['codigo_pix'] = "Mostra o código PIX Paghiper e informações de pagamento";
    $merge_fields['linha_digitavel'] = "Mostra a linha digitável e código de barras do boleto PagHiper";
    return $merge_fields;
}
add_hook('EmailTplMergeFields', 1, 'show_paghiper_tpl_fields');
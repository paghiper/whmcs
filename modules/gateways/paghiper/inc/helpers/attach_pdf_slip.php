<?php
/**
 * Adiciona boleto bancário como página adicional na fatura anexa no WHMCS
 * 
 * @package    PagHiper para WHMCS
 * @version    2.1
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Desenvolvido e mantido Henrique Cruz - https://henriquecruz.com.br/
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2020, PagHiper
 * @link       https://www.paghiper.com/
 */

use setasign\Fpdi;

$query = mysql_query("SELECT paymentmethod FROM tblinvoices WHERE id = {$invoiceid};");
$result = mysql_fetch_assoc($query);
$payment_method_slug = $result['paymentmethod'];

$is_pix = ($payment_method_slug == 'paghiper_pix');

$whmcs_url = rtrim(\App::getSystemUrl(),"/");
$json_url = $whmcs_url."/modules/gateways/";
$json_url .= ($is_pix) ? 'paghiper_pix.php' : 'paghiper.php';
$json_url .= "?invoiceid=".$invoiceid."&uuid=".$clientsdetails['userid']."&mail=".$clientsdetails['email']."&json=1";
$json_url .= ($is_pix) ? '&pix=true' : '';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $json_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$json = curl_exec($ch);
$result = json_decode($json);

$transaction_id = (isset($result->transaction_id)) ? $result->transaction_id : '';
$asset_url = (!$is_pix) ? 
    ((isset($result->bank_slip)) ? $result->bank_slip->url_slip_pdf : $result->url_slip_pdf) : 
    ((isset($result->pix_code)) ? $result->pix_code->qrcode_image_url : $result->qrcode_image_url);

if ((in_array($status, array('Unpaid', 'Payment Pending'))) && (isset($asset_url) && !empty($asset_url)) && (isset($transaction_id) && !empty($transaction_id))){

    $basedir    = (function_exists('dirname')) ? dirname(__DIR__, 2) : realpath(__DIR__ . '/../..');
    $assetdir   = $basedir.'/tmp/'.( (!$is_pix) ? 'billets' : 'pix');
    $filename   = $assetdir.'/'.$transaction_id.( (!$is_pix) ? '.pdf' : '.png');

    $print_paghiper_page = FALSE;

    // Checamos se temos um boleto para disponibilizar
    if(file_exists($filename)) {
        $print_paghiper_page = TRUE;
    } else {
        if(is_writable($filename) || touch($filename)) {

            echo $asset_url;
            exit();
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $asset_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
            $rawdata = curl_exec($ch);

            $fp = fopen($filename, 'w');
            fwrite($fp, $rawdata);
            fclose($fp);
            
            if(file_exists($filename)) {
                $print_paghiper_page = TRUE;
            }

        }
    }

    // If file doesn't exist and cannot be written, don't waste efforts. Just send the default PDF
    if($print_paghiper_page) {

        // Primeiro checamos se a transação não se trata um PIX
        if(!$is_pix) {

            /* Bloco inicializador do boleto */
            require_once($basedir.'/inc/fpdi/autoload.php');
            require_once($basedir.'/inc/fpdi/TcpdfFpdi.php');
            $pdf = new Fpdi\TcpdfFpdi();
        
            // TODO: Implementar header e footer aqui
            $pdf->SetPrintHeader(false);
            $pdf->SetPrintFooter(false);
        
            $pdf->AddPage();
    
            $pdf->setSourceFile($filename);
            $tplIdx = $pdf->importPage(1);
        
            $pdf->useTemplate($tplIdx, 0, 0, 210);
        
            /* Bloco inicializador do template comum */
            $pdf->SetPrintHeader(false);
            $pdf->SetPrintFooter(false);
    
        // Caso não seja, tentamos printar os dados do PIX
        } else {            
            // Position at 15 mm from bottom
            $pdf->SetY(15);
            // Set font
            $pdf->SetFont('opensans', '', 8);
            // Page number
            $pdf->Cell(0, 10, 'COMPANY NAME');
            $pdf->Image($filename, 'C', 50, '', '', 'PNG', false, 'C', false, 300, 'C', false, false, 0, false, false, false);
        }

        $pdf->AddPage();

    }

} 

/*header("Content-type: application/pdf");
$pdf->Output('name.pdf', 'I');
exit();*/

?>
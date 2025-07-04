<?php
/**
 * Adiciona boleto bancário como página adicional na fatura anexa no WHMCS
 * 
 * @package    PagHiper para WHMCS
 * @version    2.5.3-rc1
 * @author     Equipe PagHiper https://github.com/paghiper/whmcs
 * @author     Desenvolvido e mantido Henrique Cruz - https://henriquecruz.com.br/
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017-2024, PagHiper
 * @link       https://www.paghiper.com/
 */

use WHMCS\Database\Capsule;
use setasign\Fpdi;

// PHP 5.x compatibility
if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
    $basedir = (function_exists('dirname')) ? dirname(__DIR__, 2) : realpath(__DIR__ . '/../..');
} else {
    $basedir = (function_exists('dirname') && function_exists('dirname_with_levels')) ? dirname_with_levels(__DIR__, 2) : realpath(__DIR__ . '/../..');
}

$assets_dir = "{$basedir}/assets/img";
require_once($basedir.'/classes/PaghiperTransaction.php');

$transactionData = [
    'invoiceID'     => $invoiceid,
    'format'        => 'json'
];
$paghiperTransaction    = new PaghiperTransaction($transactionData);
$invoiceTransaction     = json_decode($paghiperTransaction->process(), TRUE);

// Bail if we don't have a transaction to process
if(!is_null($invoiceTransaction)) {

    if(array_key_exists('transaction_type', $invoiceTransaction) && $invoiceTransaction['transaction_type'] == 'billet') {
        $is_pix = FALSE;
    } elseif(array_key_exists('emv', $invoiceTransaction) && !empty($invoiceTransaction['emv'])) {
        $is_pix = TRUE;
    }
    
    $transaction_id = (isset($invoiceTransaction['transaction_id'])) ? $invoiceTransaction['transaction_id'] : '';
    $asset_url = (!$is_pix) ? 
        ((array_key_exists('bank_slip', $invoiceTransaction) && !empty($invoiceTransaction['bank_slip'])) ? $invoiceTransaction['bank_slip']['url_slip_pdf'] : $invoiceTransaction['url_slip_pdf']) : 
        ((array_key_exists('pix_code', $invoiceTransaction) && !empty($invoiceTransaction['pix_code'])) ? $invoiceTransaction['pix_code']['qrcode_image_url'] : $invoiceTransaction['qrcode_image_url']);
    
    if($is_pix) {
        $asset_url = $invoiceTransaction['qrcode_image_url'];
    } else {
        $asset_url = $invoiceTransaction['url_slip_pdf'];
    }

}

if ((in_array($status, array('Unpaid', 'Payment Pending'))) && (isset($assets_dir) && !empty($assets_dir)) && (isset($transaction_id) && !empty($transaction_id))){


    $assetdir   = $basedir.'/tmp/'.( (!$is_pix) ? 'billets' : 'pix');
    $filename   = $assetdir.'/'.$transaction_id.( (!$is_pix) ? '.pdf' : '.png');

    $print_paghiper_page = FALSE;

    // Checamos se temos um boleto para disponibilizar
    if(file_exists($filename) && filesize($filename)) {
        $print_paghiper_page = TRUE;
    } else {

        set_error_handler(
            function ($severity, $message, $file, $line) {
                throw new ErrorException($message, $severity, $severity, $file, $line);
            }
        );

        try {

            // Criamos o arquivo primeiro para evitar problemas
            fopen($filename, 'w');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $asset_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_REFERER, $asset_url);
        
            $rawdata = curl_exec($ch);
            file_put_contents($filename, $rawdata);

            $print_paghiper_page = TRUE;

        }
        catch (Exception $e) {
            echo $e->getMessage();
        }

        restore_error_handler();
    }

    // If file doesn't exist and cannot be written, don't waste efforts. Just send the default PDF
    if($print_paghiper_page) {

        // Primeiro checamos se a transação não se trata um PIX
        if(!$is_pix) {

            // Bloco inicializador do boleto
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
        
            // Bloco inicializador do template comum
            $pdf->SetPrintHeader(false);
            $pdf->SetPrintFooter(false);
    
        // Caso não seja, tentamos printar os dados do PIX
        } else {

            $emv        = $invoiceTransaction['emv'];
            $pix_url    = $invoiceTransaction['pix_url'];
            $bacen_url  = $invoiceTransaction['bacen_url'];

            $pdf->Image($assets_dir . '/pix.jpg', 10, 10, 25, '', 'JPEG');

            $pdf->SetXY(38, 8);

            // Set font
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(30, 20, 'Pague sua fatura usando PIX!', 0, 'C');


            // Instruções
            $pdf->SetXY(20, 35);
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Ln(10);
            $pdf->SetX(60);
            $pdf->Image("{$assets_dir}/ico_1-app.png", 30, 35, 30, '', 'PNG');
            $pdf->Multicell(120, 0, 'Abra o app do seu banco ou instituição financeira e entre no ambiente Pix.', 0, 'L');
            $pdf->Ln(20);
            $pdf->SetX(60);
            $pdf->Image("{$assets_dir}/ico_2-qr.png", 30, 65, 30, '', 'PNG');
            $pdf->Multicell(120, 0, 'Escolha a opção Pagar com QR Code e escanele o código abaixo.', 0, 'L');
            $pdf->Ln(20);
            $pdf->SetX(60);
            $pdf->StartTransform();
            // set clipping mask
            $pdf->Circle(45, 109, 10, 0, 360, 'CNZ');
            $pdf->Image("{$assets_dir}/ico_3-ok.png", 35, 99, 20, '', 'PNG');
            $pdf->StopTransform();
            $pdf->Multicell(120, 0, 'Confirme as informações e finalize o pagamento.', 0, 'L');


            $pdf->Image($filename, 0, 130, '', '', 'PNG', false, 'C', false, 300, 'C', false, false, 0, false, false, false);

            $pdf->SetY(215);
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Multicell(0, 10, "Fatura #{$invoiceid} - ".$total, $border=0, $align='C');

            $pdf->SetY(234);
            $pdf->SetFont('dejavusans', 'B', 9);
            $pdf->Multicell(0, 10, 'Você também pode pagar usando PIX copia e cola:', $border=0, $align='C');

            $pdf->SetFont('dejavusans', '', 8);
            $pdf->SetY(240);
            $pdf->SetFont('dejavusans', '', 13);
            $html = '<form method="post" action="'.$invoice_url.'" enctype="multipart/form-data">
            <textarea cols="62" rows="3" name="text">'.$emv.'</textarea><br />
            </form>';
            $pdf->writeHTML($html, true, 0, true, 0);
            
            $pdf->SetY(260);
            $pdf->SetFont('dejavusans', '', 8);
            $pdf->Multicell(0, 10, 'Após o pagamento, podemos levar alguns segundos para confirmar o seu pagamento.
            Você será avisado assim que isso ocorrer!', $border=0, $align='C');
        }

        $pdf->AddPage();

    }

}

// Uncomment for debugging
/*header("Content-type: application/pdf");
$pdf->Output('name.pdf', 'I');
exit();*/

?>
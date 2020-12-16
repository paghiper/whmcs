<?php
/**
 * https://www.scriptbrasil.com.br/download/codigo/6491/
 */
function codificar($codigo)
{
	$cbinicio = "NNNN";
	$cbfinal = "WNN";
	$cbnumeros = array("NNWWN", "WNNNW", "NWNNW", "WWNNN", "NNWNW", "WNWNN", "NWWNN", "NNNWW", "WNNWN", "NWNWN");
	$cbresult = '';

	if (is_numeric($codigo)&(!(strlen($codigo)&1))) {

		for($i = 0; $i < strlen($codigo); $i = $i+2) {
		
			$cbvar1 = $cbnumeros[$codigo[$i]];
			$cbvar2 = $cbnumeros[$codigo[$i+1]];

			for ($j = 0; $j <= 4; $j++) {
				$cbresult .= $cbvar1[$j].$cbvar2[$j];
			}
		}
	
		return $cbinicio.$cbresult.$cbfinal;
	}
	
	else return '';
}

function pintarbarras($mapaI25, $altura, $espmin)
{
	$espmin--;
	if($espmin < 0) {
		$espmin = 0;
	}
	
	if($altura < 5) {
		$altura = 5;
	}

	$largura = (strlen($mapaI25)/5*((($espmin+1)*3)+(($espmin+3)*2)))+20;

	$im = imagecreate($largura * 4, $altura);
	imagecolorallocate($im, 255, 255, 255);

	$spH = 10;
	for($k = 0; $k < strlen($mapaI25); $k++) {
		if (!($k&1)) {
			$corbarra = ImageColorAllocate($im,0,0,0);
		}
		else {
			$corbarra = ImageColorAllocate($im,255,255,255);
		}
		
		if ($mapaI25[$k] == 'N') {
			ImageFilledRectangle($im, $spH * 4, $altura-3, ($spH+$espmin+1) * 4, 2, $corbarra);
			$spH = $spH+$espmin+1;
		}
		else {
			ImageFilledRectangle($im, $spH * 4, $altura-3, ($spH+$espmin+2) * 4, 2, $corbarra);
			$spH = $spH+$espmin+3;
		}
	}

	imagepng($im);
	imagedestroy($im);
}

// Recupera o código e cria a imagem jpeg
$codigo = $_GET['codigo'];
header("Content-Type: image/png");
pintarbarras(codificar($codigo), 250, 1);
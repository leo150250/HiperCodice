<?php
$path = "../";
if (!isset($_GET['d']) || !isset($_GET['c'])) {
    http_response_code(400);
    echo "Parâmetros inválidos.";
    exit;
}
$d = (int)$_GET['d'];
$c = (int)$_GET['c'];
$f = isset($_GET["f"]); // Carregar imagem completa (full)
if (!$f) {
	if (!extension_loaded('gd')) {
		die("ERRO: É necessário habilitar GD para redimensionar.");
	}
}
$caminho = $path."/img/decks/$d/$c";
if (!file_exists($caminho.".jpg")) {
	http_response_code(404);
	echo "Imagem não encontrada.";
	exit;
}
$tempoCache = 86400;
header("Content-Type: image/jpeg");
header("Cache-Control: public, max-age=$tempoCache");
header("Expires: " . gmdate("D, d M Y H:i:s", time() + $tempoCache) . " GMT");
$lastModified = filemtime($caminho.".jpg");
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT");

if ($f) {
	header("Content-Length: ".filesize($caminho.".jpg"));
	readfile($caminho.".jpg");
} else {
	if (file_exists($caminho."_c.jpg")) {
		header("Content-Length: ".filesize($caminho."_c.jpg"));
		readfile($caminho."_c.jpg");
	} else {
		$origem = imagecreatefromjpeg($caminho.".jpg");
		if (!$origem) {
			http_response_code(500);
			echo "Erro ao processar imagem.";
			exit;
		}
		$larguraOriginal = imagesx($origem);
		$alturaOriginal = imagesy($origem);
		$maxLargura = 300;
		$maxAltura = 300;
		$ratio = min($maxLargura / $larguraOriginal, $maxAltura / $alturaOriginal, 1);
		$novaLargura = (int)($larguraOriginal * $ratio);
		$novaAltura = (int)($alturaOriginal * $ratio);
		$destino = imagecreatetruecolor($novaLargura, $novaAltura);
		imagecopyresampled($destino,$origem,0,0,0,0,$novaLargura,$novaAltura,$larguraOriginal,$alturaOriginal);
		imagejpeg($destino,$caminho."_c.jpg",90);
		imagejpeg($destino,null,90);
		imagedestroy($origem);
		imagedestroy($destino);
	}
}
?>
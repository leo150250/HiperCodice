<?php
$path = "../";
include $path.".interno/conexaoBD.php";
include $path.".interno/funcoes.php";

$listaSalas = [
	"proximaSala"=>15000,
	"numSalas"=>0,
	"salas"=>[]
];
if (file_exists($path."salas.json")) {
	$arquivo = fopen($path."salas.json","r");
	if (flock($arquivo,LOCK_SH)) {
		$conteudo = stream_get_contents($arquivo);
		flock($arquivo,LOCK_UN);
		$listaSalas = json_decode($conteudo,true);
	}
	fclose($arquivo);
} else {
	file_put_contents($path."salas.json",json_encode($listaSalas,JSON_PRETTY_PRINT),LOCK_EX);
}
header('Content-Type: application/json');
echo json_encode($listaSalas);
?>
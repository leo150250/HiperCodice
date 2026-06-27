<?php
$path = __DIR__."/../";
include $path.".interno/conexaoBD.php";
include $path.".interno/funcoes.php";

$listaSalas = [
	"proximaSala"=>15000,
	"numSalas"=>0,
	"salas"=>[]
];

//Estrutura JSON de cada sala
/*
{
	"porta": 15000,
	"pid: 1234,
	"nome": "Sala 1",
	"deque": 1,
	"nomeDeque": "Deque 1",
	"maxJogadores": 4,
	"jogadores": 2,
	"emExecucao": false,
	"senha": null
}
*/

if (file_exists($path."salas.json")) {
	$arquivo = fopen($path."salas.json","r");
	if (flock($arquivo,LOCK_SH)) {
		$conteudo = stream_get_contents($arquivo);
		flock($arquivo,LOCK_UN);
		$listaSalas = json_decode($conteudo,false);
	}
	fclose($arquivo);
} else {
	$jsonSalas = json_encode($listaSalas,JSON_PRETTY_PRINT);
	file_put_contents($path."salas.json",$jsonSalas,LOCK_EX);
	$listaSalas = json_decode($jsonSalas,false);
}
foreach ($listaSalas->salas as $sala) {
	if ($sala->senha !== null) {
		$sala->senha = true;
	}
}
header('Content-Type: application/json');
http_response_code(200);
echo json_encode($listaSalas);
?>
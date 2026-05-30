<?php
$path = "";
include_once $path.".interno/conexaoBD.php";
include_once $path.".interno/funcoes.php";

function obterImagem() {
	$resDeques = BD_query("SELECT id,nome FROM deques");
	BD_seek($resDeques,rand(0,BD_num_rows($resDeques)-1));
	$deque = BD_fetch($resDeques);

	$resCartas = BD_query("SELECT id,nome FROM cartas WHERE idDeque = {$deque['id']}");
	BD_seek($resCartas,rand(0,BD_num_rows($resCartas)-1));
	$carta = BD_fetch($resCartas);

	$dataImagem = [
		'idDeque' => (int)$deque['id'],
		'nomeDeque' => $deque['nome'],
		'idCarta' => (int)$carta['id'],
		'nomeCarta' => $carta['nome']
	];
	return $dataImagem;
}

$data = [];
for ($i = 0; $i < 5; $i ++) {
	$data[$i] = obterImagem();
}

header('Content-Type: application/json');
echo json_encode($data);
?>
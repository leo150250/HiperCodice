<?php
$path = "../";
include $path.".interno/conexaoBD.php";
include $path.".interno/funcoes.php";

$nomeSala = $_POST["nome"];
$jogadores = $_POST["jogadores"];
$senha = $_POST["senha"];

if (file_exists($path."salas.json")) {
	$arquivo = fopen($path."salas.json","r+");
	if (flock($arquivo,LOCK_EX)) {
		$conteudo = stream_get_contents($arquivo);
		$listaSalas = json_decode($conteudo);
		$novaSala = [
			"porta"=>$listaSalas->proximaSala,
			"pid"=>0,
			"nome"=>$nomeSala,
			"deque"=>1,
			"nomeDeque"=>"Deque",
			"maxJogadores"=>$jogadores,
			"jogadores"=>0,
			"emExecucao"=>false,
			"senha"=>$senha
		];
		$listaSalas->salas[] = $novaSala;
		$listaSalas->numSalas++;
		$listaSalas->proximaSala++;
		if ($listaSalas->proximaSala > 15999) {
			$listaSalas->proximaSala = 15000;
		}
		rewind($arquivo);
		ftruncate($arquivo,0);
		fwrite($arquivo,json_encode($listaSalas,JSON_PRETTY_PRINT));
		flock($arquivo,LOCK_UN);
	}
	fclose($arquivo);
} else {
	echo "ERRO: Arquivo salas.json não encontrado.";
}
?>
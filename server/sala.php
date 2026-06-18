<?php
if (!isset($argv)) {
	die("Este arquivo precisa ser executado diretamente no servidor.");
}
$path = "";
include $path.".interno/conexaoBD.php";
include $path.".interno/funcoes.php";
include $path.".interno/estrutura.php";
include $path."comunicacao.php";
//registrarBuffer();
//$logFile = $path . "logs/sala_" . date("Y-m-d_H-i-s") . ".txt";



$idSala = 0;

verbose(true);
$porta = 0;
verbose($argv);
foreach ($argv as $chave => $valor) {
	if ($valor == "-p") {
		$porta = $argv[$chave+1];
	}
}
if ($porta == 0) {
	die("Precisa especificar a porta");
}
$timer = 0;
construirDeque(1,6);
//$Deque->info();
iniciarSala($porta);
$jogoRodando = true;
$timerInatividade = new Timer(function(Timer $_this){
	global $Jogadores;
	if (count($Jogadores)>0) {
		$_this->resetar(10000);
		return;
	}
	verbose("Nenhum jogador presente, a sala será encerrada em ".(6-$_this->execucoes)."...\n");
	if ($_this->execucoes < 5) {
		$_this->reexecutar();
		$_this->redefinir(1000);
	} else {
		global $encerrada;
		$encerrada = true;
	}
},10000);
while ($jogoRodando) {
	executaTimers();
	checarConexoes();
	$jogoRodando = checarRodada();
	$timer=($timer+1)%60;
	//verbose("Timer: $timer\n");
}

fclose($server);
verbose("Fim da sala!");
?>
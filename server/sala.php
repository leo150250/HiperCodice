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
$timer = 0;
construirDeque(1,6);
//$Deque->info();
iniciarSala($porta);
while (true) {
	checarConexoes();
	checarRodada();
	$timer=($timer+1)%60;
	verbose("Timer: $timer\n");
	sleep(1);
}

fclose($server);
verbose("Fim da sala!");
?>
<?php
if (!isset($argv)) {
	die("Este arquivo precisa ser executado diretamente no servidor.");
}
function outBuffer($_buffer) {
	$_buffer = "Buffer ".date("Y-m-d H:i:s")."\n----------\n".$_buffer;
	file_put_contents("outBuffer.txt",$_buffer);
	return $_buffer;
}
ob_start("outBuffer");
//$logFile = $path . "logs/sala_" . date("Y-m-d_H-i-s") . ".txt";



$idSala = 0;
$path = "";
include $path.".interno/conexaoBD.php";
include $path."estrutura.php";

verbose(true);

verbose($argv);
foreach ($argv as $chave => $valor) {
	
}
$timer = 0;
construirDeque(1,6);
$Deque->info();
iniciarJogoTeste();
while ($timer < 10) {
	if (rodada()) {
		$timer = 0;
	}
	sleep(1);
	$timer++;
	verbose("Timer em $timer\n");
}
verbose("Fim da sala!");
?>
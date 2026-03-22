<?php
$path = "";
$jsonSalas = json_decode(file_get_contents($path."salas.json"));
$os = strtoupper(substr(PHP_OS, 0, 3));
$command = ($os === 'WIN') 
	? 'start /B C:\xampp\php\php.exe sala.php' 
	: 'php sala.php';
$argumentos = [
	"-p",
	$jsonSalas->proximaSala
];
$command = $command." ".implode(" ",$argumentos);
$command .= ($os === 'WIN')
	? ''
	: '> /dev/null 2>&1 &';

//Debug:
//$command = "dir";

if ($os === 'WIN') {
	$execucao = pclose(popen($command, "r"));
} else {
	$execucao = exec($command);
}
//echo "<pre>".$execucao."</pre>";
if ($execucao == "") {
	echo "Falhou ao inicializar. Tem o PHP instalado?";
} else {
	echo date("Y-m-d H:i:s")." - Executou com sucesso!";
}
?>
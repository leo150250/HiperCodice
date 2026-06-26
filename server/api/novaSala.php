<?php
$path = __DIR__."/../";
include $path.".interno/conexaoBD.php";
include $path.".interno/funcoes.php";
verbose(true);

$nomeSala = "";
$jogadores = 4;
$senha = null;

$_POST = json_decode(file_get_contents("php://input"),true);

if (isset($_POST["nome"])) {
	$nomeSala = $_POST["nome"];
}
$nomeSala = trim($nomeSala);
if (strpos($nomeSala," ") !== false) {
	$nomeSala = str_replace('"','\"',$nomeSala);
	$nomeSala = '"'.$nomeSala.'"';
}

if (isset($_POST["jogadores"])) {
	$jogadores = (int)$_POST["jogadores"];
}

if (isset($_POST["senha"])) {
	$senha = $_POST["senha"];
}
if ($senha === "") {
	$senha = null;
} else if ($senha !== null) {
	$senha = md5(trim($senha));
}

if (file_exists($path."salas.json")) {
	$arquivo = fopen($path."salas.json","r+");
	if (flock($arquivo,LOCK_EX)) {
		$conteudo = stream_get_contents($arquivo);
		$listaSalas = json_decode($conteudo);
		$argumentos = [];
		$argumentos[] = "-p";
		$porta = (int)$listaSalas->proximaSala;
		$argumentos[] = $porta;
		if ($nomeSala !== "") {
			$argumentos[] = "-n";
			$argumentos[] = $nomeSala;
		}
		$argumentos[] = "-j";
		$argumentos[] = $jogadores;
		if ($senha !== null) {
			$argumentos[] = "-s";
			$argumentos[] = $senha;
		}
		$listaSalas->numSalas++;
		$listaSalas->proximaSala++;
		if ($listaSalas->proximaSala > 15999) {
			$listaSalas->proximaSala = 15000;
		}
		$command = "php";
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			// Windows: procura pelo PHP executable
			$phpPath = php_ini_loaded_file();
			$phpDir = dirname($phpPath);
			$phpExe = $phpDir."/php.exe";
			
			if (!file_exists($phpExe)) {
				$phpExe = "php"; // fallback
			}
			$command = $phpExe." ".dirname(__DIR__)."/sala.php ".implode(" ",$argumentos);
		} else {
			// Linux/Unix
			$command = "php ".dirname(__DIR__)."/sala.php ".implode(" ",$argumentos);
		}
		flock($arquivo,LOCK_UN);
		//$output = shell_exec($command);
		//echo $output;

		//verbose($command."\n");

		//Iniciar a sala desacoplando o ponteiro do executável
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			// Windows
			$result = pclose(popen('start /B ' . $command, 'r'));
			echo ($result === 0) ? $porta : 'ERRO';
		} else {
			// Linux/Unix
			$output = shell_exec($command . ' > /dev/null 2>&1 &');
			echo ($output === null) ? $porta : 'ERRO';
		}
		//rewind($arquivo);
		//ftruncate($arquivo,0);
		//fwrite($arquivo,json_encode($listaSalas,JSON_PRETTY_PRINT));
	}
	fclose($arquivo);
} else {
	echo "ERRO: Arquivo salas.json não encontrado.";
}
?>
<?php
$path = "../";
include $path.".interno/conexaoBD.php";
include $path.".interno/funcoes.php";

$nomeSala = trim($_POST["nome"] ?? "");
$jogadores = (int)($_POST["jogadores"] ?? "4");
$senha = $_POST["senha"] ?? null;

if (strpos($nomeSala," ") !== false) {
	$nomeSala = str_replace('"','\"',$nomeSala);
	$nomeSala = '"'.$nomeSala.'"';
}
if ($senha == "") {
	$senha = null;
}
if ($senha !== null) {
	$senha = md5(trim($senha));
}

if (file_exists($path."salas.json")) {
	$arquivo = fopen($path."salas.json","r+");
	if (flock($arquivo,LOCK_EX)) {
		$conteudo = stream_get_contents($arquivo);
		$listaSalas = json_decode($conteudo);
		$argumentos = [];
		$argumentos[] = "-p";
		$argumentos[] = (int)$listaSalas->proximaSala;
		if ($nomeSala != "") {
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
			$command = $phpExe." ".dirname(__DIR__)."/../sala.php ".implode(" ",$argumentos);
		} else {
			// Linux/Unix
			$command = "php ".dirname(__DIR__)."/../sala.php ".implode(" ",$argumentos);
		}

		//$output = shell_exec($command);
		//echo $output;

		//Iniciar a sala desacoplando o ponteiro do executável
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			// Windows
			$result = pclose(popen('start /B ' . $command, 'r'));
			echo ($result === 0) ? 'success' : 'error';
		} else {
			// Linux/Unix
			$output = shell_exec($command . ' > /dev/null 2>&1 &');
			echo ($output === null) ? 'success' : 'error';
		}
		//rewind($arquivo);
		//ftruncate($arquivo,0);
		//fwrite($arquivo,json_encode($listaSalas,JSON_PRETTY_PRINT));
		flock($arquivo,LOCK_UN);
	}
	fclose($arquivo);
} else {
	echo "ERRO: Arquivo salas.json não encontrado.";
}
?>
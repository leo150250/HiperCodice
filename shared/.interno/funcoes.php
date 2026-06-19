<?php
if (!isset($path)) {
	$path = "../";
}
function verbose($_verbose) {
	global $path;
	if ($_verbose === true) {
		file_put_contents($path."logSala.txt","Iniciando log em ".date("Y-m-d H:i:s")."\n----------\n",LOCK_EX);
	} else {
		$conteudo = print_r($_verbose,true);
		echo $conteudo;
		file_put_contents($path."logSala.txt",$conteudo,FILE_APPEND | LOCK_EX);
		flush();
	}
}
function outBuffer($_buffer) {
	$_buffer = "Buffer ".date("Y-m-d H:i:s")."\n----------\n".$_buffer;
	file_put_contents("outBuffer.txt",$_buffer);
	return $_buffer;
}
function registrarBuffer() {
	ob_start("outBuffer",0,PHP_OUTPUT_HANDLER_STDFLAGS ^ PHP_OUTPUT_HANDLER_REMOVABLE);
}

$palavrasProibidas = [];
function carregarPalavrasProibidas() {
	global $path, $palavrasProibidas;
	$arquivo = $path.".interno/palavroes.txt";
	if (file_exists($arquivo)) {
		$palavrasProibidas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$palavrasProibidas = array_map('trim', $palavrasProibidas);
		foreach ($palavrasProibidas as $chave => $palavra) {
			$novaPalavra = new stdClass();
			$novaPalavra->regex = substr($palavra, 0, strpos($palavra, " "));
			$valores = explode(" ",substr($palavra, strpos($palavra, " ")+1));
			//$novaPalavra->score = intval(substr($palavra, strpos($palavra, " ")+1));
			$novaPalavra->score = intval($valores[0]);
			$novaPalavra->offset = isset($valores[1]) ? intval($valores[1]) : 0;
			$palavrasProibidas[$chave] = $novaPalavra;
		}
	} else {
		$palavrasProibidas = [];
	}
	//print_r($palavrasProibidas);
}
carregarPalavrasProibidas();

function filtrarString($_string) {
	global $palavrasProibidas;

	// Converte os números para letras, pra tentar encontrar slangs ocultos
	$replacedIndices = [];
	$mapNumeros = [
		"0" => "o",
		"1" => "i",
		"2" => "z",
		"3" => "e",
		"4" => "a",
		"5" => "s",
		"6" => "g",
		"7" => "t",
		"8" => "b",
		"9" => "g"
	];
	$chars = preg_split('//u', $_string, -1, PREG_SPLIT_NO_EMPTY);
	foreach ($chars as $idx => $char) {
		if (isset($mapNumeros[$char])) {
			$chars[$idx] = $mapNumeros[$char];
			$replacedIndices[$idx] = $char;
		}
	}
	$_string = implode('', $chars);

	$matches = [];
	// para cada palavra proibida, procura todas as ocorrências
	foreach ($palavrasProibidas as $palavra) {
		$pattern = $palavra->regex;
		if (preg_match_all($pattern, $_string, $m, PREG_OFFSET_CAPTURE)) {
			foreach ($m[0] as $occ) {
				$matchText = $occ[0];
				$byteOffset = $occ[1] + $palavra->offset;
				// converte offset em número de caracteres (UTF-8)
				$charStart = mb_strlen(mb_substr($_string, 0, $byteOffset, 'UTF-8'), 'UTF-8');
				$charLen = mb_strlen($matchText, 'UTF-8');
				$matches[] = [
					'start' => $charStart,
					'len' => $charLen,
					'score' => $palavra->score
				];
			}
		}
	}
	// converte string para array de caracteres UTF-8
	$chars = preg_split('//u', $_string, -1, PREG_SPLIT_NO_EMPTY);
	if (!empty($replacedIndices)) {
		foreach ($replacedIndices as $idx => $origDigit) {
			$chars[$idx] = $origDigit;
		}
	}
	if (empty($matches)) {
		return implode('', $chars);
	}

	$consolidados = [];
	foreach ($matches as $m) {
		$start = $m['start'];
		if (!isset($consolidados[$start])) {
			$consolidados[$start] = [
				'start' => $start,
				'len' => $m['len'],
				'score' => $m['score'],
				'_minScore' => $m['score']
			];
			continue;
		}
		$consolidados[$start]['score'] += $m['score'];
		if ($m['score'] < $consolidados[$start]['_minScore']) {
			$consolidados[$start]['_minScore'] = $m['score'];
			$consolidados[$start]['len'] = $m['len'];
		}
	}
	$matches = array_values($consolidados);
	unset($consolidados);

	//print_r($matches);

	// aplica máscaras para cada ocorrência cujo score for negativo
	foreach ($matches as $m) {
		if ($m['score'] < 0) {
			$start = $m['start'];
			$len = $m['len'];
			for ($i = $start; $i < $start + $len && $i < count($chars); $i++) {
				$chars[$i] = '♥';
			}
		}
	}

	return implode('', $chars);
}

$timers = [];
class Timer {
	private $funcao;
	private $horaInicio;
	private $horaExec;
	private $_reexecutar = false;
	public $ms = 1000;
	public $execucoes = 0;
	public function __construct($_funcao, $_ms = 1000) {
		global $timers;
		$this->funcao = $_funcao;
		$this->ms = $_ms;
		$this->redefinir();
		array_push($timers,$this);
	}
	public function executar() {
		if (new DateTime() >= $this->horaExec) {
			$this->_reexecutar = false;
			$this->execucoes++;
			call_user_func($this->funcao,$this);
			if (!$this->_reexecutar) {
				$this->desativar();
			}
		}
	}
	public function desativar() {
		verbose("Timer desativado\n");
		$this->__destruct();
	}
	public function __destruct()
	{
		global $timers;
		$indice = array_search($this, $timers, true);
		if ($indice !== false) {
			unset ($timers[$indice]);
			$timers = array_values($timers);
		}
	}
	public function reexecutar($_novoMs = null) {
		$this->_reexecutar = true;
		$this->redefinir($_novoMs);
	}
	public function redefinir($_novoMs = null) {
		if ($_novoMs !== null) {
			$this->ms = $_novoMs;
		}
		$this->horaInicio = new DateTime();
		$this->horaExec = clone $this->horaInicio;
		$this->horaExec->add(DateInterval::createFromDateString("{$this->ms}ms"));
	}
	public function resetar($_novoMs = null) {
		$this->execucoes = 0;
		$this->reexecutar($_novoMs);
	}
}
function executaTimers() {
	global $timers;
	foreach ($timers as $timer) {
		$timer->executar();
	}
}

$ssl = false;
$sslCertFile = '/etc/letsencrypt/live/seu-dominio/fullchain.pem';
$sslKeyFile = '/etc/letsencrypt/live/seu-dominio/privkey.pem';
$sslPassphrase = '';
function carregarConfigSSL() {
	global $path, $ssl, $sslCertFile, $sslKeyFile, $sslPassphrase;
	if (file_exists($path.".interno/ssl.json")) {
		$configSSL = file_get_contents($path.".interno/ssl.json");
		$configSSL = json_decode($configSSL);
		if ($configSSL->useSSL) {
			$ssl = true;
			$sslCertFile = $configSSL->certFile;
			$sslKeyFile = $configSSL->keyFile;
			$sslPassphrase = $configSSL->passphrase;
		}
	} else {
		verbose("Arquivo de configuração SSL não encontrado, usando configurações padrão (sem SSL).\n");
		$configSSL = json_encode([
			"useSSL" => false,
			"certFile" => "",
			"keyFile" => "",
			"passphrase" => ""
		]);
		$configSSL = json_decode($configSSL);
		$ssl = true;
		$sslCertFile = $configSSL->certFile;
		$sslKeyFile = $configSSL->keyFile;
		$sslPassphrase = $configSSL->passphrase;
		salvarConfigSSL();
	}
}
function salvarConfigSSL() {
	global $path, $ssl, $sslCertFile, $sslKeyFile, $sslPassphrase;
	$configSSL = json_encode([
		"useSSL" => $ssl,
		"certFile" => $sslCertFile,
		"keyFile" => $sslKeyFile,
		"passphrase" => $sslPassphrase
	], JSON_PRETTY_PRINT);
	file_put_contents($path.".interno/ssl.json",$configSSL);
}
?>
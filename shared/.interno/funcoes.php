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
?>
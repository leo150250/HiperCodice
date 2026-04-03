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
?>
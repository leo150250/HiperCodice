<?php
//Endpoint de teste para confirmar comunicação com SSL via endpoint

$path = "../";
include $path.".interno/funcoes.php";
carregarConfigSSL();
if ($ssl) {
	$comando = "cat {$sslCertFile} 2>&1";
	echo "Executando $comando";
	echo "<hr>";
	echo shell_exec($comando);
} else {
	echo "SSL não habilitado para este servidor.";
}
?>
<?php
//Endpoint de teste para confirmar comunicação com SSL via endpoint

$path = "../";
include $path.".interno/funcoes.php";
carregarConfigSSL();
if ($ssl) {
	echo shell_exec("cat {$sslCertFile} 2>&1");
} else {
	echo "SSL não habilitado para este servidor.";
}
?>
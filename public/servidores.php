<?php
$path = "";
include_once $path.".interno/conexaoBD.php";
include_once $path.".interno/funcoes.php";

$listaServidores = [
	[
		"host"=>"localhost",
		"api"=>"/hipercodice/server/",
		"ssl"=>false
	]
];
if (file_exists($path.".interno/servidoresMP.json")) {
	$servidoresMP = json_decode(file_get_contents($path.".interno/servidoresMP.json"),true);
	$listaServidores = [];
	if (is_array($servidoresMP)) {
		foreach ($servidoresMP as $servidor) {
			$listaServidores[] = $servidor;
		}
	}
} else {
	file_put_contents($path.".interno/servidoresMP.json",json_encode($listaServidores,JSON_PRETTY_PRINT));
}
header('Content-Type: application/json');
echo json_encode($listaServidores);
?>
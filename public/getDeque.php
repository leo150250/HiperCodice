<?php
$path = "";
include_once $path.".interno/conexaoBD.php";
include_once $path.".interno/funcoes.php";
include_once $path.".interno/estrutura.php";

$idDeque = 0;
$numAtributos = 0;

if (isset($_POST["deque"])) {
	$idDeque = (int)$_POST["deque"];
}
if (isset($_POST["numAtributos"])) {
	$idDeque = (int)$_POST["numAtributos"];
}

if ($idDeque == 0) {
	$resDeques = BD_query("SELECT id FROM Deques WHERE situacao = 'b' OR situacao = 'c'");
	//print_r($resDeques);
	BD_seek($resDeques,rand(0,BD_num_rows($resDeques)-1));
	$deque = BD_fetch($resDeques);
	$idDeque = (int)$deque["id"];
}
if ($numAtributos == 0) {
	$numAtributos = 6;
}

construirDeque($idDeque,$numAtributos);
$data = $Deque->json();

header('Content-Type: application/json');
echo json_encode($data);
?>
<?php
$path = "";
include_once $path.".interno/conexaoBD.php";
include_once $path.".interno/funcoes.php";
include_once $path.".interno/estrutura.php";

construirDeque(1,6);
$data = $Deque->json();

header('Content-Type: application/json');
echo json_encode($data);
?>
<?php
$path = "";
include $path.".interno/conexaoBD.php";
include $path.".interno/funcoes.php";
include $path.".interno/estrutura.php";

echo "<pre>";
$idDeque = 0;
if (isset($_GET["deque"])) {
	$idDeque = (int)$_GET["deque"];
} else {
	die("Informe o ID do deque via GET (deque)");
}
echo "Deque selecionado: ".$idDeque."\n";

$resDeques = BD_query("SELECT * FROM Deques WHERE id = {$idDeque}");
$deque = BD_fetch($resDeques);
print_r(json_encode($deque,JSON_PRETTY_PRINT));
echo "<hr>";

$resAtributos = BD_query("SELECT * FROM Atributos Where idDeque = {$idDeque}");
$atributos = [];
for ($i=0; $i < BD_num_rows($resAtributos); $i++) { 
	array_push($atributos,BD_fetch($resAtributos));
}
print_r(json_encode($atributos,JSON_PRETTY_PRINT));
echo "<hr>";

$resCartas = BD_query("SELECT * FROM Cartas Where idDeque = {$idDeque}");
$cartas = [];
for ($i=0; $i < BD_num_rows($resCartas); $i++) { 
	array_push($cartas,BD_fetch($resCartas));
}
print_r(json_encode($cartas,JSON_PRETTY_PRINT));
echo "<hr>";

$resValores = BD_query("SELECT Valores.* FROM Valores INNER JOIN Cartas ON Valores.idCarta = Cartas.id Where Cartas.idDeque = {$idDeque}");
$valores = [];
for ($i=0; $i < BD_num_rows($resValores); $i++) { 
	array_push($valores,BD_fetch($resValores));
}
print_r(json_encode($valores));
echo "<hr>";
?>
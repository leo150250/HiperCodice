<?php
$path = "";
include_once $path.".interno/conexaoBD.php";
include_once $path.".interno/funcoes.php";
include_once $path.".interno/estrutura.php";

$pesquisaDeque = "";
$deques = [];

if (isset($_POST["pesquisaDeque"])) {
	$pesquisaDeque = $_POST["pesquisaDeque"];
}

if ($pesquisaDeque == "") { //Nenhum deque em específico. Exibir deques recomendados
	$resDeques = BD_query("SELECT Deques.* FROM Deques WHERE Deques.situacao = 'b' OR Deques.situacao = 'c' LIMIT 10");
	for ($i = 0; $i < $resDeques->num_rows; $i++) {
		$regDeques = BD_fetch($resDeques);
		array_push($deques,$regDeques);
	}
} else {
	$idPesquisaDeque = intval($pesquisaDeque);
	$pesquisaDeque = addslashes($pesquisaDeque);
	$resDeques = BD_query("SELECT Deques.* FROM Deques WHERE Deques.id = ".$idPesquisaDeque." OR Deques.nome LIKE '%".$pesquisaDeque."%' OR Deques.descricao LIKE '%".$pesquisaDeque."%' LIMIT 10");
	for ($i = 0; $i < $resDeques->num_rows; $i++) {
		$regDeques = BD_fetch($resDeques);
		array_push($deques,$regDeques);
	}
}

for ($i = 0; $i < count($deques); $i++) {
	$resAtributos = BD_query("SELECT Atributos.id, Atributos.nome, Atributos.medida FROM Atributos WHERE Atributos.idDeque = ".$deques[$i]["id"]);
	$atributos = [];
	for ($j = 0; $j < $resAtributos->num_rows; $j++) {
		$regAtributos = BD_fetch($resAtributos);
		array_push($atributos, $regAtributos);
	}
	$deques[$i]["atributos"] = $atributos;
}

header('Content-Type: application/json');
echo json_encode($deques, JSON_UNESCAPED_SLASHES);
?>
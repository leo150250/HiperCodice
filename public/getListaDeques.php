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
	$resDeques = BD_query("SELECT Deques.*, (
		SELECT JSON_ARRAYAGG(JSON_OBJECT('id', Atributos.id, 'nome', Atributos.nome))
		FROM Atributos WHERE Atributos.idDeque = Deques.id) AS atribsDeque
	FROM Deques WHERE Deques.situacao = 'b' OR Deques.situacao = 'c' LIMIT 10");
	for ($i = 0; $i < $resDeques->num_rows; $i++) {
		$regDeques = BD_fetch($resDeques);
		array_push($deques,$regDeques);
	}
}

header('Content-Type: application/json');
echo json_encode($deques, JSON_UNESCAPED_SLASHES);
?>
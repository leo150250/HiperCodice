<?php
$path = "../../";
include $path.".interno/conexaoBD.php";

if (!isset($_POST["idDeque"]) || !isset($_POST["nome"]) || !isset($_POST["descricao"]) || !isset($_POST["classe"]) || !isset($_POST["categoria"])) {
	die("Dados incompletos.");
}

$idDeque = $_POST["idDeque"];
$nome = $_POST["nome"];
$descricao = $_POST["descricao"];
$classe = $_POST["classe"];
$categoria = $_POST["categoria"];
$imagem = $_FILES["imagem"];
$extensao = pathinfo($imagem["name"], PATHINFO_EXTENSION);
if ($extensao != "jpg") {
	die("A imagem deve ser do tipo JPG.");
}

BD_transacao();

BD_query("INSERT INTO Cartas (idDeque, nome, descricao, classe, categoria) VALUES ($idDeque, '$nome', '$descricao', $classe, '$categoria')");

$idCarta = BD_insert_id();

$atributos = BD_query("SELECT * FROM Atributos WHERE idDeque = $idDeque");
while ($Atributo = BD_fetch($atributos)) {
	$idAtributo = $Atributo['id'];
	if (!isset($_POST["atributos"][$idAtributo])) {
		BD_rollback();
		die("Valor do atributo ".$Atributo['nome']." não especificado.");
	}
	$valor = $_POST["atributos"][$idAtributo];
	BD_query("INSERT INTO Valores (idCarta, idAtributo, valor) VALUES ($idCarta, $idAtributo, $valor)");
}

$diretorio = $path."img/decks/".$idDeque."/";
if (!file_exists($diretorio)) {
	mkdir($diretorio, 0777, true);
}
$destino = $diretorio.$idCarta.".jpg";
if (!move_uploaded_file($imagem["tmp_name"], $destino)) {
	BD_rollback();
	die("Erro ao salvar a imagem.");
}

BD_commit();
header("Location: ../deque.php?id=$idDeque&ok=2");
?>
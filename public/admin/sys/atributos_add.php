<?php
$path = "../../";
include $path.".interno/conexaoBD.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$idDeque = $_POST["idDeque"];
	$nome = $_POST["nome"];
	$medida = $_POST["medida"];
	$forma = $_POST["forma"];
	$descricao = $_POST["descricao"];
	if (empty($nome) || empty($medida) || !isset($forma) || empty($descricao)) {
		die("Todos os campos são obrigatórios.");
	}
	BD_query("INSERT INTO Atributos (idDeque, nome, medida, forma, descricao) VALUES ($idDeque, '$nome', '$medida', $forma, '$descricao')");
	header("Location: ../deque.php?id=$idDeque&ok=1");
	exit();
} else {
	die("Método de requisição inválido.");
}
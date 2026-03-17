<?php
$path = "../../";
include $path.".interno/conexaoBD.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	print_r($_POST);
	$nome = $_POST["nome"];
	$imagem = $_FILES["imagem"];
	$extensao = pathinfo($imagem["name"], PATHINFO_EXTENSION);
	if ($extensao != "jpg") {
		die("A imagem deve ser do tipo JPG.");
	}
	$descricao = $_POST["descricao"];
	if (empty($nome) || empty($descricao)) {
		die("Nome e descrição são obrigatórios.");
	}
	BD_query("INSERT INTO Deques (nome, descricao) VALUES ('$nome', '$descricao')");
	$idDeque = BD_insert_id();
	$diretorio = $path."img/decks/".$idDeque."/";
	if (!file_exists($diretorio)) {
		mkdir($diretorio, 0777, true);
	}
	$destino = $diretorio."default.jpg";
	if (!move_uploaded_file($imagem["tmp_name"], $destino)) {
		die("Erro ao salvar a imagem.");
	}
	header("Location: ../deques.php?ok=1");
	exit();
} else {
	die("Método de requisição inválido.");
}
?>
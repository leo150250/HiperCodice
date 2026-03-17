<?php
if (!isset($path)) {
	$path = "../";
}

$dadosConexao = (object) [
	"host" => "localhost",
	"porta" => "3306",
	"usuario" => "root",
	"senha" => "",
	"db" => "HiperCodice"
];
$conexaoDB = null;

if (file_exists($path.".interno/conexaoBD.json")) {
	$dadosConexao = file_get_contents($path.".interno/conexaoBD.json");
	$dadosConexao = json_decode($dadosConexao);
} else {
	if (!isset($config)) {
		header("Location: ".$path.".interno/config.php?BD");
		exit();
	} else {
		die("Configurações de conexão com o banco de dados não encontradas.");
	}
}

function BD_conectar() {
	global $dadosConexao;
	global $conexaoDB;
	$conexao = new mysqli($dadosConexao->host, $dadosConexao->usuario, $dadosConexao->senha, $dadosConexao->db, $dadosConexao->porta);
	if ($conexao->connect_error) {
		die("Erro de conexão: " . $conexao->connect_error);
	}
	$conexaoDB = $conexao;
	return $conexao;
}
function BD_desconectar() {
	global $conexaoDB;
	if ($conexaoDB) {
		$conexaoDB->close();
		$conexaoDB = null;
	}
}
function BD_query($_sql) {
	global $conexaoDB;
	if (!$conexaoDB) {
		BD_conectar();
	}
	$resultado = $conexaoDB->query($_sql);
	if ($resultado === false) {
		die("Erro na consulta: " . $conexaoDB->error);
	}
	return $resultado;
}
function BD_fetch($_resultado) {
	return $_resultado->fetch_assoc();
}
function BD_num_rows($_resultado) {
	return $_resultado->num_rows;
}
function BD_insert_id() {
	global $conexaoDB;
	return $conexaoDB->insert_id;
}
function BD_transacao() {
	global $conexaoDB;
	$conexaoDB->begin_transaction();
}
function BD_commit() {
	global $conexaoDB;
	$conexaoDB->commit();
}
function BD_rollback() {
	global $conexaoDB;
	$conexaoDB->rollback();
}

if (!isset($config)) {
	BD_conectar();
}
?>
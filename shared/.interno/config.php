<?php
$path = "../";
$config = true;
if (isset($_GET['BD'])) {
	$dadosConexao = (object) [
		"host" => "localhost",
		"porta" => "3306",
		"usuario" => "root",
		"senha" => "",
		"db" => "HiperCodice"
	];
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$dadosConexao->host = $_POST['host'];
		$dadosConexao->porta = $_POST['porta'];
		$dadosConexao->usuario = $_POST['usuario'];
		$dadosConexao->senha = $_POST['senha'];
		$dadosConexao->db = $_POST['db'];
		file_put_contents($path.".interno/conexaoBD.json", json_encode($dadosConexao, JSON_PRETTY_PRINT));
	}
	if (file_exists($path.".interno/conexaoBD.json")) {
		$dadosConexao = file_get_contents($path.".interno/conexaoBD.json");
		$dadosConexao = json_decode($dadosConexao);
	}
	if (isset($_GET['BDGerar'])) {
		$sqlFile = $path . ".interno/BD.sql";
		if (file_exists($sqlFile)) {
			$sql = file_get_contents($sqlFile);
			try {
				$conn = new mysqli($dadosConexao->host, $dadosConexao->usuario, $dadosConexao->senha, "", $dadosConexao->porta);
				if ($conn->connect_error) {
					echo "Erro na conexão: " . $conn->connect_error;
				} else {
					$dbName = $dadosConexao->db;
					$conn->query("DROP DATABASE IF EXISTS `$dbName`");
					$conn->query("CREATE DATABASE `$dbName`");
					$conn->select_db($dbName);
					if ($conn->multi_query($sql)) {
						echo "Banco de dados gerado com sucesso!";
						while ($conn->next_result());
					} else {
						echo "Erro ao executar BD.sql: " . $conn->error;
					}
					$conn->close();
				}
			} catch (Exception $e) {
				echo "Erro: " . $e->getMessage();
			}
		} else {
			echo "Arquivo BD.sql não encontrado.";
		}
	}
}
?>
<html>
	<head>
		<title>Configurações</title>
	</head>
	<body>
		<h1>Configurações</h1>
		<?php if (isset($_GET['BD'])): ?>
			<h2>Configurações de Conexão com o Banco de Dados</h2>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?BD">
				<label for="host">Host:</label><br>
				<input type="text" id="host" name="host" value="<?php echo $dadosConexao->host; ?>" required><br><br>
				<label for="porta">Porta:</label><br>
				<input type="text" id="porta" name="porta" value="<?php echo $dadosConexao->porta; ?>" required><br><br>
				<label for="usuario">Usuário:</label><br>
				<input type="text" id="usuario" name="usuario" required><br><br>
				<label for="senha">Senha:</label><br>
				<input type="password" id="senha" name="senha" required><br><br>
				<label for="db">Banco de Dados:</label><br>
				<input type="text" id="db" name="db" value="<?php echo $dadosConexao->db; ?>" required><br><br>
				<input type="submit" value="Salvar Configurações">
			</form>
			<button onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?BD&BDGerar'">Gerar Banco de Dados</button>
			<?php include_once($path.".interno/conexaoBD.php"); ?>
		<?php else: ?>
			<p>Nenhuma configuração específica solicitada.</p>
		<?php endif; ?>
	</body>
</html>
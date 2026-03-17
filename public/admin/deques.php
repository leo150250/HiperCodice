<html>
	<body>
		<h1>Deques</h1>
		<div id="novoDeque">
			<button id="btnNovoDeque" onclick="mostrarFormNovoDeque()">Novo Deque</button>
			<form id="formNovoDeque" style="display:none;" action="sys/deques_add.php" method="POST" enctype="multipart/form-data">
				<input type="text" id="nome" name="nome" placeholder="Nome do Deque" required>
				<input type="file" id="imagem" name="imagem" accept="image/jpg" required>
				<textarea id="descricao" name="descricao" placeholder="Descrição do Deque" required></textarea>
				<button type="submit">Criar</button>
			</form>
		</div>
		<div id="listaDeques">
			<?php
			$path = "../";
			include $path.".interno/conexaoBD.php";
			$Deques = BD_query("SELECT * FROM Deques");
			if (BD_num_rows($Deques) == 0) {
				echo "<p>Nenhum deque encontrado.</p>";
			} else {
				while ($Deque = BD_fetch($Deques)) {
					echo "<a class='Deque' href='deque.php?id=".$Deque['id']."'>";
					echo "<h2>".$Deque['nome']."</h2>";
					echo "<img src='".$path."img/decks/".$Deque['id']."/default.jpg' alt='Imagem do Deque ".$Deque['nome']."'>";
					echo "<p>".$Deque['descricao']."</p>";
					echo "</a>";
				}
			}
			BD_desconectar();
			?>
		</div>
		<script>
			function mostrarFormNovoDeque() {
				document.getElementById("formNovoDeque").style.display = "block";
				document.getElementById("btnNovoDeque").style.display = "none";
			}
		</script>
	</body>
</html>
<?php
$path = "../";
include $path.".interno/conexaoBD.php";
if (!isset($_GET["id"])) {
	die("ID do deque não especificado.");
}
$Deque = BD_query("SELECT * FROM Deques WHERE id = ".$_GET["id"]);
if (BD_num_rows($Deque) == 0) {
	die("Deque não encontrado.");
}
$Deque = BD_fetch($Deque);
?>
<html>
	<head>
		<title>Administração - Deque <?php echo $Deque['nome']; ?></title>
		<link rel="stylesheet" href="<?php echo $path; ?>estilo.css">
	</head>
	<body>
		<h1>Deque ID #<?php echo $Deque['id']; ?>: <?php echo $Deque['nome']; ?></h1>
		<p><?php echo $Deque['descricao']; ?></p>
		<?php
		//Verifica a quantidade de cartas presentes em cada classe (1, 2, 3 e 4) para certificar se o deque pode ser utilizado
		$classes = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
		$Cartas = BD_query("SELECT classe FROM Cartas WHERE idDeque = ".$Deque['id']);
		while ($Carta = BD_fetch($Cartas)) {
			$classes[$Carta['classe']]++;
		}
		//Desenha divs dos quantitativos das cartas. Caso a classe possua 8 cartas ou mais, a div é verde.
		echo "<div id='quantitativoCartas'>";
		foreach ($classes as $classe => $quantidade) {
			$cor = $quantidade >= 8 ? "ok" : "erro";
			echo "<div class='quantitativoCarta $cor'>Classe $classe: $quantidade cartas</div>";
		}
		echo "</div>";
		?>
		<div id="atributos">
			<h2>Atributos</h2>
			<button id="btnNovoAtributo" onclick="mostrarFormNovoAtributo()">Novo Atributo</button>
			<form id="formNovoAtributo" style="display:none;" action="sys/atributos_add.php" method="POST">
				<input type="hidden" name="idDeque" value="<?php echo $Deque['id']; ?>">
				<input type="text" id="nomeAtributo" name="nome" placeholder="Nome do Atributo" required>
				<input type="text" id="medidaAtributo" name="medida" placeholder="Medida do Atributo" required>
				<select id="formaAtributo" name="forma" required>
					<option value="1">Maior vence</option>
					<option value="0">Menor vence</option>
				</select>
				<textarea id="descricaoAtributo" name="descricao" placeholder="Descrição do Atributo" required></textarea>
				<button type="submit">Criar</button>
			</form>
			<button onclick="visualizarAtributos()">Visualizar Atributos</button>
			<div id="listaAtributos" style="display:none;">
				<?php
				$Atributos = BD_query("SELECT * FROM Atributos WHERE idDeque = ".$Deque['id']);
				if (BD_num_rows($Atributos) == 0) {
					echo "<p>Nenhum atributo encontrado.</p>";
				} else {
					while ($Atributo = BD_fetch($Atributos)) {
						echo "<div class='Atributo'>";
						echo "<h3>".$Atributo['nome']." (".$Atributo['medida'].") - ".($Atributo['forma']==1 ? "Maior vence" : "Menor vence")."</h3>";
						echo "<p>".$Atributo['descricao']."</p>";
						echo "</div>";
					}
				}
				$Atributos->data_seek(0);
				?>
			</div>
		</div>
		<div id="cartas">
			<h2>Cartas</h2>
			<button id="btnNovaCarta" onclick="mostrarFormNovaCarta()">Nova Carta</button>
			<form id="formNovaCarta" style="display:none;" action="sys/cartas_add.php" method="POST" enctype="multipart/form-data">
				<input type="hidden" name="idDeque" value="<?php echo $Deque['id']; ?>">
				<input type="text" id="nomeCarta" name="nome" placeholder="Nome da Carta" required>
				<input type="file" id="imagemCarta" name="imagem" accept="image/jpg, image/jpeg">
				<textarea id="descricaoCarta" name="descricao" placeholder="Descrição da Carta" required></textarea>
				<input type="number" id="classeCarta" name="classe" placeholder="Classe da Carta" min="1" max="4" title="A classe da carta define a qualidade da mesma. Cartas de classe 1 possuem geralmente atributos melhores, cartas de classe 4 possuem geralmente atributos piores." required>
				<input type="text" id="categoriaCarta" name="categoria" placeholder="Categoria da Carta" required>
				<h3>Valores:</h3>
				<?php
				while ($Atributo = BD_fetch($Atributos)) {
					echo "<div class='valorCarta'>";
					echo "<label for='valor".$Atributo['id']."'>".$Atributo['nome'].": </label>";
					echo "<input type='number' step='any' id='valor".$Atributo['id']."' name='atributos[".$Atributo['id']."]' placeholder='".$Atributo['medida']."' required>".$Atributo['medida']."</input>";
					echo "</div>";
				}
				?>
				<button type="submit">Criar</button>
			</form>
			<?php
			$Cartas = BD_query("SELECT * FROM Cartas WHERE idDeque = ".$Deque['id'] ." ORDER BY classe ASC, nome ASC");
			if (BD_num_rows($Cartas) == 0) {
				echo "<p>Nenhuma carta encontrada.</p>";
			} else {
				while ($Carta = BD_fetch($Cartas)) {
					$classe = "";
					switch ($Carta['classe']) {
						case 1:
							$classe = "A";
							break;
						case 2:
							$classe = "B";
							break;
						case 3:
							$classe = "C";
							break;
						case 4:
							$classe = "D";
							break;
					}
					echo "<div class='Carta classe{$Carta['classe']}'>";
					echo "<img class='fundo' src='".$path."img/decks/".$Deque['id']."/default.jpg' alt='Fundo da Carta'>";
					echo "<h1>".$classe."</h1>";
					echo "<h2>".$Carta['nome']."</h2>";
					echo "<h3>".$Carta['categoria']."</h3>";
					echo "<img class='imagem' src='".$path."img/decks/".$Deque['id']."/".$Carta['id'].".jpg' alt='".$Carta['nome']."'><br>";
					$Valores = BD_query("SELECT * FROM Valores v JOIN Atributos a ON v.idAtributo = a.id WHERE v.idCarta = ".$Carta['id']);
					echo "<div class='valoresCarta'>";
					while ($Valor = BD_fetch($Valores)) {
						$valor = $Valor['valor'];
						$inverterMedida = false;
						if (strpos($Valor['medida'], "R$") !== false) {
							$inverterMedida = true;
							$valor = number_format($Valor['valor'], 2, ",", ".");
						}
						if ($inverterMedida) {
							echo "<div><div>".$Valor['nome'].": </div><div>".$Valor['medida']." ".$valor."</div></div>";
						} else {
							echo "<div><div>".$Valor['nome'].": </div><div>".$valor." ".$Valor['medida']."</div></div>";
						}
					}
					echo "</div>";
					echo "<p>".$Carta['descricao']."</p>";
					echo "</div>";
				}
			}
			?>
		</div>
		<script>
			function mostrarFormNovoAtributo() {
				document.getElementById("formNovoAtributo").style.display = "block";
				document.getElementById("btnNovoAtributo").style.display = "none";
			}
			function visualizarAtributos() {
				var lista = document.getElementById("listaAtributos");
				if (lista.style.display === "none") {
					lista.style.display = "block";
				} else {
					lista.style.display = "none";
				}
			}
			function mostrarFormNovaCarta() {
				document.getElementById("formNovaCarta").style.display = "block";
				document.getElementById("btnNovaCarta").style.display = "none";
			}
		</script>
	</body>
</html>
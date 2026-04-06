<html>
	<head>
		<title>Teste de deque</title>
		<meta charset="UTF-8">
		<link rel="stylesheet" href="estilo.css">
	</head>
	<?php
$path = "";
include $path.".interno/conexaoBD.php";
include $path.".interno/funcoes.php";
include $path.".interno/estrutura.php";

construirDeque(1,6);
	?>
	<body>
		<h1>Teste de deque</h1>
		<div id="deque"></div>
		<script src="estrutura.js"></script>
		<script>
			var jsonDeque = <?php echo $Deque->json(); ?>;
			gerarDequeJSON(jsonDeque);

			var cartaAnterior = -1;
			function gerarCarta(_id = 0) {
				if (cartaAnterior != -1) {
					document.getElementById("deque").innerHTML = "";
					cartaAnterior = -1;
				}
				document.getElementById("deque").appendChild(deque.cartas[_id].desenhar());
				cartaAnterior = _id;
			}
			gerarCarta(0);
		</script>
	</body>
</html>
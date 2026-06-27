<?php
$path = "";
echo "<pre>";
include $path.".interno/funcoes.php";
carregarPalavrasProibidas();
echo "<form method='POST'>";
echo "<input type='text' name='frase' id='frase' value='' placeholder='Digite uma frase'>";
echo "<input type='submit' value='Filtrar'>";
echo "</form>";
echo "<hr>";
$frase = "";
if (isset($_POST["frase"])) {
	$frase = $_POST["frase"];
};
if ($frase != "") {
	$fraseFiltrada = filtrarString($frase);
	echo $fraseFiltrada;
}
?>
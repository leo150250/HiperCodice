<?php
$idSala = 0;
$path = "";
include $path.".interno/conexaoBD.php";
include $path."estrutura.php";


construirDeque(1,6);

echo "<pre>";
$Deque->info();
echo $Deque->json();
echo "</pre>";
?>
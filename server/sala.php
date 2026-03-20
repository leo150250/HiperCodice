<?php
$idSala = 0;
include "estrutura.php";


construirDeque(1,6);

$Deque->info();
echo $Deque->json();
?>
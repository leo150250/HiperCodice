<?php
$inicio = microtime(true);

$i = 0;
while ($i < 1000) {
    $i++;
}

$fim = microtime(true);
$diferenca = $fim - $inicio;

echo ($diferenca * 1000);

?>
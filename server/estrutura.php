<?php
class Deque {
	public $atributos = [];
	public $cartas = [];
	public $id = 0;
	public $nome = "Deque";
	public function __construct($_id,$_nome) {
		$this->id = $_id;
		$this->nome = $_nome;
	}
	public function info() {
		echo "Deque: ".$this->nome." (ID: ".$this->id.")\n";
		echo "Atributos:\n";
		foreach ($this->atributos as $atributo) {
			$atributo->info();
		}
		echo "Cartas:\n";
		foreach ($this->cartas as $carta) {
			$carta->info();
		}
	}
	public function organizarDeque() {
		//Organiza as cartas por classe e número, mantendo tudo 1-1, 1-2...1-8,2-1,2-2... e assim por diante
		usort($this->cartas, function($a, $b) {
			if ($a->classe == $b->classe) {
				return $a->numero - $b->numero;
			}
			return $a->classe - $b->classe;
		});
	}
}
class Atributo {
	public function __construct($_deque, $_id) {
		$this->deque = $_deque;
		$this->id = $_id;
		$this->nome = "Atributo ".$this->id;
		$this->medida = "un";
		$this->forma = 1; // 1 = Maior vence, -1 = Menor vence
	}
	public function info() {
		echo "- ".$this->nome." (#".$this->id." - Medida: ".$this->medida.", Forma: ".($this->forma == 1 ? "Maior vence" : "Menor vence").")\n";
	}
}
class Carta {
	public $valores = [];
	public $numero = 0;
	public function __construct($_deque, $_id, $_classe) {
		$this->deque = $_deque;
		$this->id = $_id;
		$this->classe = $_classe;
		$this->nome = "Carta ".$this->id;
	}
	public function desenhar() {
		echo "<div class='Carta classe".$this->classe."'>";
		echo "<img class='fundo' src='".$path."img/decks/".$this->deque->id."/default.jpg' alt='Fundo da Carta'>";
		echo "<h1>".$this->classe."</h1>";
		echo "<h2>".$this->nome."</h2>";
		echo "<h3>".$this->categoria."</h3>";
		echo "<img class='imagem' src='".$path."img/decks/".$this->deque->id."/".$this->id.".jpg' alt='".$this->nome."'><br>";
		echo "<div class='valoresCarta'>";
		for ($i = 0; $i < count($this->deque->atributos); $i++) {
			$atributo = $this->deque->atributos[$i];
			$valor = $this->valores[$i];
			$inverterMedida = false;
			if (strpos($atributo->medida, "R$") !== false) {
				$inverterMedida = true;
				$valor = number_format($valor, 2, ",", ".");
			}
			if ($inverterMedida) {
				echo "<div><div>".$atributo->nome.": </div><div>".$atributo->medida." ".$valor."</div></div>";
			} else {
				echo "<div><div>".$atributo->nome.": </div><div>".$valor." ".$atributo->medida."</div></div>";
			}
		}
		echo "<p>".$Carta['descricao']."</p>";
		echo "</div>";
	}
	public function info() {
		echo "- [".$this->classe."-".$this->numero."] #".$this->id." - ".$this->nome." - ".$this->categoria."\n";
		for ($i = 0; $i < count($this->deque->atributos); $i++) {
			$atributo = $this->deque->atributos[$i];
			$valor = $this->valores[$i];
			echo "  - ".$atributo->nome.": ".$valor." ".$atributo->medida."\n";
		}
	}
}

$Deque = null;
function construirDeque($_id,$_numAtributos=6) {
	global $Deque;
	global $path;
	require_once($path.".interno/conexaoBD.php");
	$resDeque = BD_query("SELECT * FROM Deques WHERE id = ".$_id);
	if (BD_num_rows($resDeque) == 0) {
		die("Deque não encontrado.");
	}
	$regDeque = BD_fetch($resDeque);
	$Deque = new Deque($regDeque['id'], $regDeque['nome']);
	$resAtributos = BD_query("SELECT * FROM Atributos WHERE idDeque = ".$_id." ORDER BY id ASC");
	$atributos = [];
	while ($regAtributo = BD_fetch($resAtributos)) {
		$atributo = new Atributo($Deque, $regAtributo['id']);
		$atributo->nome = $regAtributo['nome'];
		$atributo->medida = $regAtributo['medida'];
		$atributo->forma = $regAtributo['forma'];
		$atributos[] = $atributo;
	}
	//Escolhe 6 atributos aleatórios para o deque
	shuffle($atributos);
	$Deque->atributos = array_slice($atributos, 0, $_numAtributos);
	$cartas = [];
	$resCartas = BD_query("SELECT * FROM Cartas WHERE idDeque = ".$_id." ORDER BY id ASC");
	while ($regCarta = BD_fetch($resCartas)) {
		$carta = new Carta($Deque, $regCarta['id'], $regCarta['classe']);
		$carta->nome = $regCarta['nome'];
		$carta->categoria = $regCarta['categoria'];
		$carta->descricao = $regCarta['descricao'];
		$resValores = BD_query("SELECT * FROM Valores WHERE idCarta = ".$regCarta['id']." AND idAtributo IN (".implode(",", array_map(function($a) { return $a->id; }, $Deque->atributos)).")");
		while ($regValor = BD_fetch($resValores)) {
			foreach ($Deque->atributos as $index => $atributo) {
				if ($atributo->id == $regValor['idAtributo']) {
					$carta->valores[$index] = $regValor['valor'];
					break;
				}
			}
		}
		$cartas[] = $carta;
	}
	$numCartasClasses = [0,0,0,0];
	//Embaralha as cartas, e adiciona elas ao deque, garantindo que haja 8 cartas de cada classe. Se a classe já tiver mais de 8, segue pra próxima carta
	shuffle($cartas);
	foreach ($cartas as $carta) {
		if ($numCartasClasses[$carta->classe - 1] < 8) {
			$Deque->cartas[] = $carta;
			$numCartasClasses[$carta->classe - 1]++;
			$carta->numero = $numCartasClasses[$carta->classe - 1];
		}
	}
	$Deque->organizarDeque();
}
?>
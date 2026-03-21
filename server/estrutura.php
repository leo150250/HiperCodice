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
	public function json() {
		$data = [
			"id" => $this->id,
			"nome" => $this->nome,
			"atributos" => [],
			"cartas" => []
		];
		foreach ($this->atributos as $atributo) {
			$data['atributos'][] = $atributo->json();
		}
		foreach ($this->cartas as $carta) {
			$data['cartas'][] = $carta->json();
		}
		return json_encode($data);
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
	public function json() {
		return [
			"id" => $this->id,
			"nome" => $this->nome,
			"medida" => $this->medida,
			"forma" => $this->forma
		];
	}
}
class Carta {
	public $valores = [];
	public $numero = 0;
	public $especial = false;
	public function __construct($_deque, $_id, $_classe) {
		$this->deque = $_deque;
		$this->id = $_id;
		$this->classe = $_classe;
		$this->nome = "Carta ".$this->id;
	}
	public function obterCodCarta() {
		$texto = "";
		switch ($this->classe) {
			case 1: $texto = "A"; break;
			case 2: $texto = "B"; break;
			case 3: $texto = "C"; break;
			case 4: $texto = "D"; break;
		}
		if ($this->numero > 0) {
			$texto.=$this->numero;
		}
		return $texto;
	}
	public function desenhar() {
		echo "<div class='Carta classe".$this->classe."'>";
		echo "<img class='fundo' src='".$path."img/decks/".$this->deque->id."/default.jpg' alt='Fundo da Carta'>";
		echo "<h1>".$this->obterCodCarta()."</h1>";
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
	public function info($_enumerar = false) {
		echo "- [".$this->obterCodCarta()."] #".$this->id." - ".$this->nome." - ".$this->categoria."\n";
		if ($this->especial) {
			echo "### HIPER CODICE! ###\n";
		}
		for ($i = 0; $i < count($this->deque->atributos); $i++) {
			$atributo = $this->deque->atributos[$i];
			$valor = $this->valores[$i];
			echo "  ".($_enumerar?"[".($i+1)."]":"-")." ".$atributo->nome.": ".$valor." ".$atributo->medida."\n";
		}
	}
	public function json() {
		$data = [
			"id" => $this->id,
			"classe" => $this->classe,
			"numero" => $this->numero,
			"nome" => $this->nome,
			"categoria" => $this->categoria,
			"descricao" => $this->descricao,
			"valores" => $this->valores
		];
		return $data;
	}
}
$Jogadores = [];
class Jogador {
	public $nome = "Jogador";
	public $cartas = [];
	public $ativo = true;
	public function __construct($_nome) {
		global $Jogadores;
		$this->nome = $_nome;
		$Jogadores[] = $this;
		echo "Jogador ".$this->nome." entrou!\n";
	}
	public function info() {
		echo "Jogador: ".$this->nome."\n";
		echo "Cartas:\n";
		foreach ($this->cartas as $carta) {
			$carta->info();
		}
	}
	public function cartaAtual() {
		return $this->cartas[0];
	}
	public function removerCartaAtual() {
		return array_shift($this->cartas);
	}
	public function adicionarCarta($carta) {
		$this->cartas[] = $carta;
	}
	public function enviarCartaAoFinal() {
		adicionarCarta(removerCartaAtual());
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
	//Escolhe uma carta aleatória, de classe 2, 3 ou 4, para ser a carta especial. Se não houver nenhuma carta dessas classes, escolhe uma carta aleatória de qualquer classe
	$cartasEspeciais = array_filter($Deque->cartas, function($carta) {
		return $carta->classe >= 2;
	});
	if (!empty($cartasEspeciais)) {
		$cartaEspecial = $cartasEspeciais[array_rand($cartasEspeciais)];
		$cartaEspecial->especial = true;
	}
}

$jogadorDaVez = 0;
function rodada($_interativo = false) {
	global $Jogadores;
	global $jogadorDaVez;
	global $Deque;
	$jogadorAtual = $Jogadores[$jogadorDaVez];
	echo "\n------------------------------------------------------\n";
	echo "É a vez de ".$jogadorAtual->nome."!\n";
	echo "Carta atual:\n";
	$jogadorAtual->cartaAtual()->info(true);
	$atributoEscolhido = -1;
	if ($_interativo) {
		while ($atributoEscolhido < 0 || $atributoEscolhido >= count($jogadorAtual->cartaAtual()->valores)) {
			$atributoEscolhido = intval(readline("Digite o número do atributo (1 a ".count($Deque->atributos)."): ")) - 1;
			if ($atributoEscolhido < 0 || $atributoEscolhido >= count($jogadorAtual->cartaAtual()->valores)) {
				echo "Atributo inválido. Tente novamente.\n";
			}
		}
	}
	echo "Atributo escolhido: ".$Deque->atributos[$atributoEscolhido]->nome." - ".$jogadorAtual->cartaAtual()->valores[$atributoEscolhido]."\n";
	echo "Valores dos jogadores: \n";
	$jogadorEspecial = null;
	foreach ($Jogadores as $jogador) {
		if (!$jogador->ativo) {
			continue;
		}
		echo " - ".$jogador->nome.": ";
		echo $jogador->cartaAtual()->valores[$atributoEscolhido]." (".$jogador->cartaAtual()->obterCodCarta().")";
		if ($jogador->cartaAtual()->especial) {
			echo " [Especial]";
			$jogadorEspecial = $jogador;
		}
		echo "\n";
	}
	$jogadoresVencedores = [];
	
	if ($_interativo) {
		sleep(2);
	}

	if ($jogadorEspecial !== null) {
		echo "Jogador ".$jogadorEspecial->nome." tem a carta especial";
		sleep(2);
		//Verifica se algum jogador (exceto o especial) possui uma carta de classe 1. Se sim, armazena os jogadores com carta de classe 1 como vencedores. Se não, o jogador com a carta especial é o vencedor
		$jogadoresClasse1 = array_filter($Jogadores, function($jogador) use ($jogadorEspecial) {
			if (!$jogador->ativo) {
				return false;
			}
			return $jogador !== $jogadorEspecial && $jogador->cartaAtual()->classe == 1;
		});
		if (!empty($jogadoresClasse1)) {
			echo " mas os jogadores com carta de classe 1 vencem: ";
			foreach ($jogadoresClasse1 as $jogador) {
				echo $jogador->nome." ";
				$jogadoresVencedores[] = $jogador;
			}
			echo "\n";
		} else {
			$jogadoresVencedores[] = $jogadorEspecial;
			echo " e vence a rodada!\n";
		}
		sleep(1);
	} else {
		//Verifica qual é o jogador que tem o maior (ou menor) valor do atributo escolhido, dependendo da forma do atributo e guarda os jogadores empatados
		$melhorValor = null;
		
		foreach ($Jogadores as $jogador) {
			if (!$jogador->ativo) {
				continue;
			}
			$valor = $jogador->cartaAtual()->valores[$atributoEscolhido];
			if ($melhorValor === null || ($Deque->atributos[$atributoEscolhido]->forma == 1 && $valor > $melhorValor) || ($Deque->atributos[$atributoEscolhido]->forma == 0 && $valor < $melhorValor)) {
				$melhorValor = $valor;
				$jogadoresVencedores = [$jogador];
			} elseif ($valor == $melhorValor) {
				$jogadoresVencedores[] = $jogador;
			}
		}
	}
	if (count($jogadoresVencedores) > 1) {
		echo "Empate entre: ";
		foreach ($jogadoresVencedores as $jogador) {
			echo $jogador->nome." ";
		}
		echo "\n";
		
		//Verifica qual deles tem a carta de menor classe. Se houver empate, verifica qual deles tem a carta de menor número. O que coincidir primeiro é o vencedor.
		$vencedor = null;
		foreach ($jogadoresVencedores as $jogador) {
			echo " - ".$jogador->nome.": ".$jogador->cartaAtual()->obterCodCarta()."\n";
			if ($vencedor === null || $jogador->cartaAtual()->classe < $vencedor->cartaAtual()->classe || ($jogador->cartaAtual()->classe == $vencedor->cartaAtual()->classe && $jogador->cartaAtual()->numero < $vencedor->cartaAtual()->numero)) {
				$vencedor = $jogador;
			}
		}
		$jogadoresVencedores = [$vencedor];
		sleep(2);
	}
	echo "Vencedor: ".$jogadoresVencedores[0]->nome;
	if ($jogadoresVencedores[0] !== $jogadorAtual) {
		echo ", com a seguinte carta:\n";
		$jogadoresVencedores[0]->cartaAtual()->info();
	}
	echo "\n";
	sleep(1);
	//O vencedor da rodada recebe as cartas dos outros jogadores (primeiro as cartas dos outros jogadores, depois a sua própria carta) e as coloca no final do seu deque. Os outros jogadores perdem a carta da vez.
	foreach ($Jogadores as $jogador) {
		if (!$jogador->ativo) {
			continue;
		}
		if ($jogador !== $jogadoresVencedores[0]) {
			$jogadoresVencedores[0]->adicionarCarta($jogador->removerCartaAtual());
		}
	}
	$jogadoresVencedores[0]->adicionarCarta($jogadoresVencedores[0]->removerCartaAtual());

	$jogadorDaVez = array_search($jogadoresVencedores[0], $Jogadores);
	//Verifica se algum jogador está ativo mas sem cartas. Se sim, ele é desativado.
	foreach ($Jogadores as $jogador) {
		if ($jogador->ativo && count($jogador->cartas) == 0) {
			$jogador->ativo = false;
			echo "Jogador ".$jogador->nome." eliminado!\n";
			sleep(1);
		}
	}
	//Verifica se há somente um jogador ativo. Se sim, ele é o vencedor do jogo.
	$jogadoresAtivos = array_filter($Jogadores, function($jogador) {
		return $jogador->ativo;
	});
	if (count($jogadoresAtivos) == 1) {
		$vencedor = array_values($jogadoresAtivos)[0];
		echo "Jogador ".$vencedor->nome." venceu o jogo!\n";
		return false;
	}
	exibirCartasJogadores();
	return true;
}

function iniciarJogoTeste() {
	global $Jogadores;
	global $Deque;
	if ($Deque === null) {
		construirDeque(1,6);
	}
	new Jogador("Alice");
	new Jogador("Bob");
	new Jogador("Charlie");
	new Jogador("Diana");

	$dequeEmbaralhado = $Deque->cartas;
	shuffle($dequeEmbaralhado);
	//Distribui as cartas do deque entre os jogadores
	$numJogadores = count($Jogadores);
	echo "Embaralhando e distribuindo deque para ".$numJogadores." jogadores...\n";
	foreach ($dequeEmbaralhado as $index => $carta) {
		$Jogadores[$index % $numJogadores]->adicionarCarta($carta);
	}
	exibirCartasJogadores();
}
function exibirCartasJogadores() {
	global $Jogadores;
	echo "Cartas dos jogadores:\n";
	foreach ($Jogadores as $jogador) {
		echo $jogador->nome.": ";
		if (!$jogador->ativo) {
			echo "Perdeu!";
		} else {
			foreach ($jogador->cartas as $carta) {
				echo "[".$carta->obterCodCarta()."] ";
			}
		}
		echo "\n";
	}
}
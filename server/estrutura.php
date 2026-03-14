<?php
class Deque {
	public $atributos = [];
	public $id = 0;
	public function __construct($_id,$_atributos=[0,1,2,3,5]) {
		$this->id = $_id;
		$this->nome = $jsonDeque->nome;
		foreach ($_atributos as $chave => $atributo) {
			$novoAtributo = new Atributo($this->id,$atributo);
			$this->atributos[] = $novoAtributo;
		}
	}
}
class Atributo {
	public function __construct($_deque, $_id) {
		$this->deque = $_deque;
		$this->id = $_id;
		$this->nome = "Atributo ".$this->id;
		$this->forma = 1; // 1 = Maior vence, -1 = Menor vence
	}
}
class Carta {
	public $atributos = [];
	public function __construct($_deque, $_id) {
		$this->deque = $_deque;
		$this->id = $_id;
		$this->nome = "Carta ".$this->id;
		$this->atributos = $_atributos;
	}
}

$jsonDeque = "{}";
function criarDequeTeste() {
	$jsonDeque = file_get_contents("dequeTeste.json");
	$jsonDeque = json_decode($jsonDeque);
}
?>
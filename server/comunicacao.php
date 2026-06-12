<?php
if (!isset($path)) {
	$path = "";
}
require_once $path.".interno/funcoes.php";
require_once $path.".interno/estrutura.php";

$server = null;
$clientes = array($server);
$comm = new Comm();
$porta = 0;

$timers = [];
class Timer {
	private $funcao;
	private $horaInicio;
	private $horaExec;
	private $_reexecutar = false;
	public $ms = 1000;
	public $execucoes = 0;
	public function __construct($_funcao, $_ms = 1000) {
		global $timers;
		$this->funcao = $_funcao;
		$this->ms = $_ms;
		$this->redefinir();
		array_push($timers,$this);
	}
	public function executar() {
		if (new DateTime() >= $this->horaExec) {
			$this->_reexecutar = false;
			$this->execucoes++;
			call_user_func($this->funcao,$this);
			if (!$this->_reexecutar) {
				$this->desativar();
			}
		}
	}
	public function desativar() {
		verbose("Timer desativado\n");
		$this->__destruct();
	}
	public function __destruct()
	{
		global $timers;
		$indice = array_search($this, $timers, true);
		if ($indice !== false) {
			unset ($timers[$indice]);
			$timers = array_values($timers);
		}
	}
	public function reexecutar($_novoMs = null) {
		$this->_reexecutar = true;
		$this->redefinir($_novoMs);
	}
	public function redefinir($_novoMs = null) {
		if ($_novoMs !== null) {
			$this->ms = $_novoMs;
		}
		$this->horaInicio = new DateTime();
		$this->horaExec = clone $this->horaInicio;
		$this->horaExec->add(DateInterval::createFromDateString("{$this->ms}ms"));
	}
	public function resetar($_novoMs = null) {
		$this->execucoes = 0;
		$this->reexecutar($_novoMs);
	}
}
function executaTimers() {
	global $timers;
	foreach ($timers as $timer) {
		$timer->executar();
	}
}

class Comm {
	protected $clients;

    public function __construct() {
        $this->clients = array();
    }

    public function onOpen($conn) {
        global $emExecucao;
		$this->clients[(int)$conn->resourceId] = $conn;
		verbose("Nova conexão: ({$conn->resourceId})\n");
		if ($emExecucao) {
			$conn->send(json_encode([
				'tipo' => 'goaway',
				'conteudo' => [
					'msg' => 'Partida em andamento. Aguarde a próxima rodada.'
				]
			]));
			return false;
		}
		$conn->send(json_encode([
			'tipo' => 'welcome',
			'conteudo' => [
				'resourceId' => $conn->resourceId
			]
		]));
		return true;
    }

    public function onMessage($from, $msg) {
        if (strpos($msg, '\\') === 0) {
            // Se tiver \ no início, Interpretar como comando ao servidor
            $parts = explode(' ', substr($msg, 1));
            $command = $parts[0];
            $args = array_slice($parts, 1);
            verbose(sprintf("Comando recebido de %d: %s\n", $from->resourceId, $msg));
            switch ($command) {
				case "thnx":
					verbose("Conexão {$from->resourceId} está acordada e ativa.\n");
					$novoJogador = new Jogador("Jogador {$from->resourceId}");
					$novoJogador->conexao = $from;
					$from->jogador = $novoJogador;
					$renome = implode(" ",$args);
					if ($renome != "") {
						$novoJogador->renomear($renome);
					}
					global $Jogadores, $timerInatividade;
					$timerInatividade->resetar(10000);
					$numJogadores = count($Jogadores);
					$numProntos = count(array_filter($Jogadores, function($jogador) {
						return $jogador->pronto;
					}));
					verbose("Jogadores prontos: {$numProntos} / {$numJogadores}\n");
					break;
				case "ready":
					verbose("Jogador {$from->resourceId} está pronto para iniciar a partida.\n");
					$this->jogadorConn($from)->pronto = true;
					foreach ($this->clients as $client) {
						$client->send(json_encode([
							"tipo"=>"ready",
							"conteudo"=>[
								"resourceId"=>$from->resourceId
							]
						]));
					}
					global $Jogadores, $timerInatividade;
					$timerInatividade->resetar(10000);
					$numJogadores = count($Jogadores);
					$numProntos = count(array_filter($Jogadores, function($jogador) {
						return $jogador->pronto;
					}));
					verbose("Jogadores prontos: {$numProntos} / {$numJogadores}\n");
					break;
				case "notready":
					verbose("Jogador {$from->resourceId} não está mais pronto.\n");
					$this->jogadorConn($from)->pronto = false;
					foreach ($this->clients as $client) {
						$client->send(json_encode([
							"tipo"=>"notready",
							"conteudo"=>[
								"resourceId"=>$from->resourceId
							]
						]));
					}
					global $Jogadores, $timerInatividade;
					$timerInatividade->resetar(10000);
					$numJogadores = count($Jogadores);
					$numProntos = count(array_filter($Jogadores, function($jogador) {
						return $jogador->pronto;
					}));
					verbose("Jogadores prontos: {$numProntos} / {$numJogadores}\n");
					break;
				case "escolha":
					verbose("Jogador {$from->resourceId} escolheu atributo {$args[0]}\n");
					global $Jogadores, $timerProntidao, $atributoEscolhido, $jogadorDaVez;
					if ($this->jogadorConn($from) === $Jogadores[$jogadorDaVez]) {
						$timerProntidao = 0;
						$atributoEscolhido = $args[0];
					}
					break;
                default:
                    verbose("Comando desconhecido: $command\n");
                    break;
            }
        } else {
            // Senão, interpretar como mensagem de chat
            $numRecv = count($this->clients) - 1;
            verbose(sprintf('Conexão %d enviou mensagem "%s" para %d outras conexões',
                $from->resourceId, $msg, $numRecv));
            foreach ($this->clients as $client) {
                if ($from !== $client) {
                    $client->send(json_encode([
                        "tipo"=>"msg",
                        "conteudo"=>[
                            "remetente"=>$from->resourceId,
                            "msg"=>$msg]]));
                }
            }
        }
    }

    public function onClose($conn) {
		$jogadorSaiu = $this->jogadorConn($conn);
		if ($jogadorSaiu !== null) {
			$jogadorSaiu->quitar();
		}
        unset($this->clients[(int)$conn->resourceId]);
		global $emExecucao, $timerInatividade;
		$timerInatividade->resetar(10000);
		if (!$emExecucao) {
			global $Jogadores;
			$numJogadores = count($Jogadores);
			$numProntos = count(array_filter($Jogadores, function($jogador) {
				return $jogador->pronto;
			}));
			verbose("Jogadores prontos: {$numProntos} / {$numJogadores}\n");
		}
        verbose("Conexão ({$conn->resourceId}) fechada\n");
    }

    public function onError($conn, $e) {
        verbose("Erro: {$e->getMessage()}\n");
        $conn->close();
    }

	public function enviarComm($conn,$tipo,$conteudo = new stdClass()) {
		$conn->send(json_encode([
			"tipo"=>$tipo,
			"conteudo"=>$conteudo
		]));
	}

	public function enviarCommTodos($tipo,$conteudo = new stdClass()) {
		foreach ($this->clients as $client) {
			$client->send(json_encode([
				"tipo"=>$tipo,
				"conteudo"=>$conteudo
			]));
		}
	}

	public function enviarMensagem($conn, $msg) {
		$conn->send(json_encode([
			"tipo"=>"msg",
			"conteudo"=>[
				"resourceId"=>-1,
				"msg"=>$msg
			]
		]));
	}

	public function enviarMensagemTodos($msg) {
		foreach ($this->clients as $client) {
			$client->send(json_encode([
				"tipo"=>"msg",
				"conteudo"=>[
					"resourceId"=>-1,
					"msg"=>$msg
				]
			]));
		}
	}

    public function obterClientes() {
        return $this->clients;
    }

	public function jogadorConn($conn) {
		global $Jogadores;
		foreach ($Jogadores as $jogador) {
			if ($jogador->conexao->resourceId === $conn->resourceId) {
				return $jogador;
			}
		}
		return null;
	}
}
class Conexao {
    public $resourceId;
    public $socket;
	public $jogador;

    public function __construct($socket) {
        $this->socket = $socket;
        $this->resourceId = (int)$socket;
    }

    public function send($msg) {
        fwrite($this->socket, encodeMessage($msg));
    }

    public function close() {
        fclose($this->socket);
    }
}

function iniciarSala($_porta) {
	global $server, $clientes, $comm, $porta;
	if ($_porta == 0) {
		$_porta = intval(readline("Digite o número da porta: "));
	}
	verbose("Iniciando sala na porta $_porta...\n");
	$context = stream_context_create();
	$server = stream_socket_server("tcp://0.0.0.0:$_porta", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
	if (!$server) {
		die("Falha ao iniciar a sala: $errstr ($errno)");
	}
	verbose("Sala iniciada na porta $_porta. Aguardando jogadores...\n");
	$clientes = array($server);
	$comm = new Comm();
	$porta = $_porta;
}
function checarConexoes() {
	global $clientes;
	$read = $clientes;
    $write = null;
    $except = null;

    if (stream_select($read, $write, $except, 0, 10) > 0) {
        verificarNovasConexoes($read);
		verbose("Heartbeats: ".implode(', ', $read)."\n");
        foreach ($read as $conn) {
            heartBeat($conn);
        }
    }
}
function verificarNovasConexoes($_read) {
	global $server, $clientes, $comm, $porta;
	if (in_array($server, $_read)) {
		$conn = stream_socket_accept($server);
		if ($conn) {
			$connection = new Conexao($conn);
			$clientes[] = $conn;

			// Perform WebSocket handshake
			$headers = fread($conn, 1024);
			perform_handshaking($headers, $conn, 'localhost', $porta);

			$comm->onOpen($connection);
		}
		unset($_read[array_search($server, $_read)]);
	}
}
function heartBeat($_conn) {
	global $comm, $clientes, $server;
	//verbose("Heartbeat ".$_conn."\n");
	if ($_conn === $server) {
		//verbose("Socket do servidor.\n");
		return;
	}
	$msg = fread($_conn, 1024);
	if ($msg === false || $msg === '') {
		$connection = new Conexao($_conn);
		$comm->onClose($connection);
		fclose($_conn);
		unset($clientes[array_search($_conn, $clientes)]);
	} else {
		$decoded_msg = unmask($msg);
		$connection = new Conexao($_conn);
		$comm->onMessage($connection, $decoded_msg);
	}
}
function checarRodada() {
	global $emExecucao, $Jogadores, $timerProntidao, $comm, $Deque, $encerrada, $jogadorDaVez, $atributoEscolhido, $timerInatividade;
	if (!$emExecucao) { //Estamos no Lobby ainda, aguardando todos os 2 ou mais jogadores ficarem prontos
		if ($encerrada) { return false; } //Se for encerrada no lobby por algum motivo (inatividade, por exemplo), faz a sala parar
		$numJogadores = count($Jogadores);
		$numProntos = count(array_filter($Jogadores, function($jogador) {
			return $jogador->pronto;
		}));
		if ($numJogadores > 1 && $numProntos == $numJogadores) {
			if ($timerProntidao == -1) {
				verbose("Todos os jogadores estão prontos.\n");
				$timerInatividade->resetar(10000);
				$timerProntidao = 3;
				verbose("Iniciando em $timerProntidao...\n");
				$comm->enviarMensagemTodos("Iniciando em $timerProntidao...");
				new Timer(function($_this){
					global $timerProntidao, $comm;
					$timerProntidao--;
					if ($timerProntidao > 0) {
						$_this->reexecutar();
						verbose("Iniciando em $timerProntidao...\n");
						$comm->enviarMensagemTodos("Iniciando em $timerProntidao...");
					}
				},1000);
			} elseif ($timerProntidao > 0) {
				//Faz nada. Espera os timers!
			} else {
				$comm->enviarMensagemTodos("Iniciando partida...");
				$timerInatividade->resetar(10000);
				$emExecucao = true;
				$timerProntidao = -1;
				$infoJogadores = [];
				foreach ($Jogadores as $jogador) {
					$novaInfo = [
						"resourceId"=>$jogador->conexao->resourceId,
						"nome"=>$jogador->nome
					];
					array_push($infoJogadores,$novaInfo);
				}
				foreach ($Jogadores as $jogador) {
					$comm->enviarComm($jogador->conexao,"deque",$Deque->json());
					$comm->enviarComm($jogador->conexao,"jogadores",$infoJogadores);
				}
				embaralharEDistribuirCartas();
				exibirCartasJogadores();
			}
		} else {
			if ($timerProntidao != -1) {
				verbose("Iniciativa cancelada. Aguardando todos os jogadores ficarem prontos...\n");
				$comm->enviarMensagemTodos("Iniciativa cancelada. Aguardando todos os jogadores ficarem prontos...");
				$timerProntidao = -1;
				$timerInatividade->resetar(10000);
			}
		}
		return true;
	} else {
		if (!$encerrada) { //O jogo tá acontecendo.
			if ($timerProntidao == -1) {
				//Envia os índices das cartas da vez para cada jogador
				foreach ($Jogadores as $jogador) {
					//Obtém o índice da carta no deque
					foreach ($Deque->cartas as $indice => $carta) {
						if ($carta === $jogador->cartaAtual()) {
							$comm->enviarComm($jogador->conexao,"carta",[
								"resourceId"=>$jogador->conexao->resourceId,
								"carta"=>$indice,
								"qtd"=>count($jogador->cartas)
							]);
							break;
						}
					}
				}
				//Informa o jogador da vez que é a vez dele de jogar
				$comm->enviarCommTodos("jogar",[
					"resourceId"=>$Jogadores[$jogadorDaVez]->conexao->resourceId,
				]);
				$timerProntidao = 30;
				new Timer(function($_this){
					global $timerProntidao;
					if ($timerProntidao > 0) {
						$timerProntidao--;
						if ($timerProntidao <= 5 && $timerProntidao > 0) {
							verbose("Vai ser escolhido um atributo em {$timerProntidao}...\n");
						}
						$_this->reexecutar();
					}
				},1000);
			} else {
				if ($timerProntidao <= 0) {
					if ($atributoEscolhido == -1) {
						$atributoEscolhido = rand(0, count($Deque->atributos) - 1);
					}
					$cartasJogadores = [];
					foreach ($Jogadores as $jogador) {
						$novaCartaJogadores = [];
						$novaCartaJogadores["jogador"] = $jogador->conexao->resourceId;
						foreach ($Deque->cartas as $indice => $carta) {
							if ($carta === $jogador->cartaAtual()) {
								$novaCartaJogadores["carta"] = $indice;
							}
						}
						array_push($cartasJogadores,$novaCartaJogadores);
					}
					$comm->enviarCommTodos("escolha",[
						"resourceId"=>$Jogadores[$jogadorDaVez]->conexao->resourceId,
						"atributo"=>$atributoEscolhido,
						"cartasJogadores"=>$cartasJogadores
					]);
					$encerrada = !girarRodada($atributoEscolhido);
					$timerProntidao = -1;
				}
			}
			return true;
		} else {
			return false;
		}
		
		//Teste: Envia uma carta aleatória a todos os jogadores a cada 10 segundos (através do timerProntidao)
		/*
		if ($timerProntidao == 0) {
			foreach ($Jogadores as $jogador) {
				$numCarta = rand(0, count($Deque->cartas)-1);
				$carta = $Deque->cartas[$numCarta];
				$comm->enviarComm($jogador->conexao,"carta",[
					"resourceId"=>$jogador->conexao->resourceId,
					"carta"=>$numCarta
				]);
			}
			$timerProntidao = 10;
		} else {
			$timerProntidao--;
		}
		*/
	}
}
function checarDesconexoes() {

}

function perform_handshaking($received_header, $client_conn, $host, $port) {
    $headers = array();
    $lines = preg_split("/\r\n/", $received_header);
    foreach ($lines as $line) {
        $line = chop($line);
        if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
            $headers[$matches[1]] = $matches[2];
        }
    }

    if (!isset($headers['Sec-WebSocket-Key'])) {
        return;
    }
    $secKey = $headers['Sec-WebSocket-Key'];
    $secAccept = base64_encode(sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

    //$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'wss' : 'ws';
    $protocol = 'wss';
    $location = "{$protocol}://$host:$port";

    $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
               "Upgrade: websocket\r\n" .
               "Connection: Upgrade\r\n" .
               "Sec-WebSocket-Accept: $secAccept\r\n\r\n";
    fwrite($client_conn, $upgrade);
}
function unmask($payload) {
    $length = ord($payload[1]) & 127;

    if ($length == 126) {
        $masks = substr($payload, 4, 4);
        $data = substr($payload, 8);
    } elseif ($length == 127) {
        $masks = substr($payload, 10, 4);
        $data = substr($payload, 14);
    } else {
        $masks = substr($payload, 2, 4);
        $data = substr($payload, 6);
    }

    $text = '';
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }
    return $text;
}
function encodeMessage($msg) {
    $b1 = 0x80 | (0x1 & 0x0f); // FIN + opcode (text)
    $length = strlen($msg);

    if ($length <= 125) {
        $header = pack('CC', $b1, $length);
    } elseif ($length <= 65535) {
        $header = pack('CCn', $b1, 126, $length);
    } else {
        // 64 bits: PHP não tem pack 'J' em todo sistema, então use gmp or split em 2x32 bits
        $header = pack('CCNN', $b1, 127, 0, $length); // Funciona para mensagens < 4GB
    }

    return $header . $msg;
}
?>
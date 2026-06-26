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
$pendingHandshakes = array();
$jogadoresMPEmEspera = [];
carregarConfigSSL();

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
					$this->enviarMensagemTodos("{$novoJogador->nome} entrou na sala.");
					registrarJogadoresProntos();
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
					registrarJogadoresProntos();
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
					registrarJogadoresProntos();
					break;
				case "ok":
					verbose("Jogador {$from->resourceId} confirma que seu jogo está carregado e pronto para iniciar.\n");
					//Remover o ID deste jogadores de jogadoresMPEmEspera
					global $jogadoresMPEmEspera, $Jogadores;
					foreach ($jogadoresMPEmEspera as $idx => $id) {
						if ($id === $from->resourceId) {
							unset($jogadoresMPEmEspera[$idx]);
							$jogadoresMPEmEspera = array_values($jogadoresMPEmEspera);
							break;
						}
					}
					$infoJogadores = [];
					foreach ($Jogadores as $jogador) {
						$novaInfo = [
							"resourceId"=>$jogador->conexao->resourceId,
							"nome"=>$jogador->nome,
							"qtdCartas"=>count($jogador->cartas)
						];
						array_push($infoJogadores,$novaInfo);
					}
					$this->enviarComm($from,"jogadores",$infoJogadores);
					break;
				case "escolha":
					verbose("Jogador {$from->resourceId} escolheu atributo {$args[0]}\n");
					global $Jogadores, $timerProntidao, $atributoEscolhido, $jogadorDaVez;
					if ($this->jogadorConn($from) === $Jogadores[$jogadorDaVez]) {
						$timerProntidao = 0;
						$atributoEscolhido = $args[0];
					}
					break;
				case "renomear":
					$novoNome = implode(" ",$args);
					verbose("Jogador {$from->resourceId} renomeou para {$novoNome}\n");
					$this->jogadorConn($from)->renomear($novoNome);
					registrarJogadoresProntos();
					break;
				case "msg":
					$texto = filtrarString(implode(" ",$args));
					verbose("Mensagem de {$from->resourceId}: ".$texto."\n");
						foreach ($this->clients as $client) {
							if ($from !== $client) {
								$client->send(json_encode([
									"tipo"=>"msg",
									"conteudo"=>[
										"remetente"=>$from->resourceId,
										"msg"=>$texto
									]
								]));
							}
						}
					break;
                default:
                    verbose("Comando desconhecido: $command\n");
                    break;
            }
        } else {
            // Senão, interpretar como gibberish, apenas logar
            verbose(sprintf('Conexão %d enviou gibberish: "%s"',
                $from->resourceId, $msg));
        }
    }

    public function onClose($conn) {
		$jogadorSaiu = $this->jogadorConn($conn);
		if ($jogadorSaiu !== null) {
			global $emExecucao, $timerInatividade;
			$timerInatividade->resetar(10000);
			if (!$emExecucao) {
				registrarJogadoresProntos();
			}
			verbose("Conexão ({$conn->resourceId}) fechada\n");
			$this->enviarMensagemTodos("{$jogadorSaiu->nome} saiu da sala.");
			$jogadorSaiu->quitar();
		}
        unset($this->clients[(int)$conn->resourceId]);
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
				"remetente"=>-1,
				"msg"=>$msg
			]
		]));
	}

	public function enviarMensagemTodos($msg) {
		foreach ($this->clients as $client) {
			$client->send(json_encode([
				"tipo"=>"msg",
				"conteudo"=>[
					"remetente"=>-1,
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
        fwrite($client_conn, "HTTP/1.1 400 Bad Request\r\nConnection: close\r\n\r\n");
        return false;
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
    return true;
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

function iniciarSala($_porta) {
	global $server, $clientes, $comm, $porta, $ssl, $sslCertFile, $sslKeyFile, $sslPassphrase;
	if ($_porta == 0) {
		$_porta = intval(readline("Digite o número da porta: "));
	}
	verbose("Iniciando sala na porta $_porta...\n");
	$context = null;
	if ($ssl) {
		if (!extension_loaded('openssl')) {
			die("Falha ao iniciar a sala SSL: extensão OpenSSL do PHP não está habilitada.\n");
		}
		if (!file_exists($sslCertFile)) {
			die("Falha ao iniciar a sala: certificado SSL não encontrado em $sslCertFile\n");
		}
		if (!file_exists($sslKeyFile)) {
			die("Falha ao iniciar a sala: chave privada SSL não encontrada em $sslKeyFile\n");
		}
		$context = stream_context_create(["ssl" => [
			"local_cert" => $sslCertFile,
			"local_pk" => $sslKeyFile,
			"passphrase" => $sslPassphrase,
			"allow_self_signed" => false,
			"verify_peer" => false,
			"verify_peer_name" => false,
		]]);
		$server = stream_socket_server("ssl://0.0.0.0:$_porta", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
		if (!$server) {
			die("Falha ao iniciar a sala SSL: $errstr ($errno)");
		}
	} else {
		$context = stream_context_create();
		$server = stream_socket_server("tcp://0.0.0.0:$_porta", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
		if (!$server) {
			die("Falha ao iniciar a sala: $errstr ($errno)");
		}
	}
	stream_set_blocking($server, false);
	//Registrar sala criada
	verbose("Sala iniciada na porta $_porta. Aguardando jogadores...\n");
	$porta = (int)$_porta;
	registrarSala();
	$clientes = array($server);
	$comm = new Comm();
}
function registrarSala() {
	global $path, $Deque, $porta, $nomeLobby, $maxJogadores, $senha, $pid;
	verbose("Registrando... ");
	if (file_exists($path."salas.json")) {
		$arquivo = fopen($path."salas.json","r+");
		if (flock($arquivo,LOCK_EX)) {
			$conteudo = stream_get_contents($arquivo);
			$listaSalas = json_decode($conteudo);
			$novaSala = [
				"porta"=>$porta,
				"pid"=>$pid,
				"nome"=>$nomeLobby,
				"deque"=>$Deque->id,
				"nomeDeque"=>$Deque->nome,
				"maxJogadores"=>$maxJogadores,
				"jogadores"=>0,
				"emExecucao"=>false,
				"senha"=>$senha
			];
			$listaSalas->salas[] = $novaSala;
			$listaSalas->numSalas++;
			$listaSalas->proximaSala++;
			if ($listaSalas->proximaSala > 15999) {
				$listaSalas->proximaSala = 15000;
			}
			rewind($arquivo);
			ftruncate($arquivo,0);
			fwrite($arquivo,json_encode($listaSalas,JSON_PRETTY_PRINT));
			flock($arquivo,LOCK_UN);
		}
		fclose($arquivo);
		verbose("OK\n");
		register_shutdown_function("desregistrarSala");
	} else {
		die("Arquivo salas.json não encontrado!");
	}
}
function desregistrarSala() {
	global $path, $pid;
	verbose("Desregistrando...");
	if (file_exists($path."salas.json")) {
		$arquivo = fopen($path."salas.json","r+");
		if (flock($arquivo,LOCK_EX)) {
			$conteudo = stream_get_contents($arquivo);
			$listaSalas = json_decode($conteudo);
			$indiceSala = null;
			$numSalas = count($listaSalas->salas);
			for ($i = 0; $i < $numSalas; $i++) {
				$salaAtual = $listaSalas->salas[$i];
				if ($salaAtual->pid == $pid) {
					$indiceSala = $i;
					break;
				}
			}
			if ($indiceSala !== null) {
				array_splice($listaSalas->salas, $indiceSala, 1);
			} else {
				verbose("ERRO: Não foi encontrado PID $pid registrado!");
			}
			rewind($arquivo);
			ftruncate($arquivo,0);
			fwrite($arquivo,json_encode($listaSalas,JSON_PRETTY_PRINT));
			flock($arquivo,LOCK_UN);
		}
		fclose($arquivo);
		verbose("OK\n");
	} else {
		die("Arquivo salas.json não encontrado!");
	}
}
function atualizarSala() {
	global $path, $porta, $pid, $nomeLobby, $Deque, $maxJogadores, $Jogadores, $emExecucao, $senha;
	if (file_exists($path."salas.json")) {
		$arquivo = fopen($path."salas.json","r+");
		if (flock($arquivo,LOCK_EX)) {
			$conteudo = stream_get_contents($arquivo);
			$listaSalas = json_decode($conteudo);
			$indiceSala = null;
			$numSalas = count($listaSalas->salas);
			for ($i = 0; $i < $numSalas; $i++) {
				$salaAtual = $listaSalas->salas[$i];
				if ($salaAtual->pid == $pid) {
					$indiceSala = $i;
					break;
				}
			}
			$listaSalas[$indiceSala]->nome = $nomeLobby;
			$listaSalas[$indiceSala]->deque = $Deque->id;
			$listaSalas[$indiceSala]->nomeDeque = $Deque->nome;
			$listaSalas[$indiceSala]->maxJogadores = $maxJogadores;
			$listaSalas[$indiceSala]->jogadores = count($Jogadores);
			$listaSalas[$indiceSala]->emExecucao = $emExecucao;
			rewind($arquivo);
			ftruncate($arquivo,0);
			fwrite($arquivo,json_encode($listaSalas,JSON_PRETTY_PRINT));
			flock($arquivo,LOCK_UN);
		}
		fclose($arquivo);
	} else {
		die("Arquivo salas.json não encontrado!");
	}
}

function checarConexoes() {
	global $clientes, $server, $pendingHandshakes, $comm, $porta;

	$read = array_merge($clientes, $pendingHandshakes);
	$write = null;
	$except = null;

	//verbose("checarConexoes: contador clientes=" . count($clientes) . " pendentes=" . count($pendingHandshakes) . "\n");
	$ready = @stream_select($read, $write, $except, 0, 100000);
	//verbose("stream_select retornou: " . var_export($ready, true) . "; count(read)=" . count($read) . "\n");

	if ($ready > 0) {
		//verbose("Nova conexão!\n");
		verificarNovasConexoes($read);

		// Tenta concluir handshakes pendentes que ficaram prontos
		foreach ($pendingHandshakes as $idx => $sock) {
			if (!in_array($sock, $read, true)) continue;
			$headers = fread($sock, 1024);
			if ($headers === false || $headers === '') {
				// ainda não há dados; aguarda próximas iterações
				continue;
			}
			if (perform_handshaking($headers, $sock, 'localhost', $porta)) {
				$connection = new Conexao($sock);
				$clientes[] = $sock;
				$comm->onOpen($connection);
				unset($pendingHandshakes[$idx]);
				$pendingHandshakes = array_values($pendingHandshakes);
			} else {
				verbose("Conexão rejeitada: headers não enviados ou handshake WebSocket inválido. Fechando socket.\n");
				fclose($sock);
				unset($pendingHandshakes[$idx]);
				$pendingHandshakes = array_values($pendingHandshakes);
			}
		}

		$read = array_filter($read, function($conn) use ($server) {
			return $conn !== $server && is_resource($conn);
		});
		//verbose("Read set após filtro do servidor: " . implode(', ', $read) . "\n");
		//verbose("Clientes ativos: " . implode(', ', $clientes) . "\n");
		if (count($read) > 0) {
			//verbose("Heartbeats: " . implode(', ', $read) . "\n");
			foreach ($read as $conn) {
				heartBeat($conn);
			}
		}
	}
}
function verificarNovasConexoes($_read) {
	global $server, $clientes, $comm, $porta, $pendingHandshakes;
	if (in_array($server, $_read, true)) {
		$conn = stream_socket_accept($server);
		if ($conn) {
			stream_set_blocking($conn, false);
			$headers = fread($conn, 1024);
			if ($headers === false) {
				$headers = '';
			}
			if ($headers === '') {
				// Nenhum dado imediato: armazena para tentativa posterior
				$pendingHandshakes[] = $conn;
				verbose("Conexão pendente: aguardando dados para handshake.\n");
			} else {
				if (perform_handshaking($headers, $conn, 'localhost', $porta)) {
					$connection = new Conexao($conn);
					$clientes[] = $conn;
					$comm->onOpen($connection);
				} else {
					verbose("Conexão rejeitada: handshake WebSocket inválido. Fechando socket.\n");
					fclose($conn);
				}
			}
		}
		$serverIndex = array_search($server, $_read, true);
		if ($serverIndex !== false) {
			unset($_read[$serverIndex]);
		}
	}
}
function heartBeat($_conn,$_tentativa = 0) {
	global $comm, $clientes, $server;
	//verbose("Heartbeat ".$_conn."\n");
	if (!is_resource($_conn)) {
		verbose("Heartbeat ignorado: recurso de socket inválido ou fechado.\n");
		return;
	}
	if ($_conn === $server) {
		//verbose("Socket do servidor.\n");
		return;
	}
	$msg = fread($_conn, 1024);
	if ($msg === false || $msg === '') {
		if ($_tentativa < 3) {
			verbose("(Heartbeat {$_tentativa})\n");
			usleep(100000); // Espera 100ms antes de tentar ler novamente
			heartBeat($_conn, $_tentativa + 1);
			return;
		}
		verbose("Conexão ({$_conn}) fechada por inatividade ou erro.\n");
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
	global $emExecucao, $Jogadores, $timerProntidao, $comm, $Deque, $encerrada, $jogadorDaVez, $atributoEscolhido, $timerInatividade, $jogadoresMPEmEspera;
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
					if ($timerProntidao > 0) {
						$timerProntidao--;
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
				embaralharEDistribuirCartas();
				exibirCartasJogadores();
				atualizarSala();
				foreach ($Jogadores as $jogador) {
					$comm->enviarComm($jogador->conexao,"deque",$Deque->json());
				}
				foreach ($Jogadores as $jogador) {
					$jogadoresMPEmEspera[] = $jogador->conexao->resourceId;
				}
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
			if (count($jogadoresMPEmEspera) > 0) {
				verbose("Aguardando confirmação de carregamento dos jogos pelos jogadores: " . implode(", ", $jogadoresMPEmEspera) . "\n");
				sleep(1);
				return true;
			}
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
					$autoEscolha = false;
					if ($atributoEscolhido == -1) {
						$atributoEscolhido = rand(0, count($Deque->atributos) - 1);
						$autoEscolha = true;
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
						"cartasJogadores"=>$cartasJogadores,
						"autoEscolha"=>$autoEscolha
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
function enviarInfoLobby() {
	global $Jogadores, $comm, $Deque, $nomeLobby;
	$infoJogadores = [];
	foreach ($Jogadores as $jogador) {
		$novaInfo = [
			"resourceId"=>$jogador->conexao->resourceId,
			"nome"=>$jogador->nome,
			"pronto"=>$jogador->pronto
		];
		array_push($infoJogadores,$novaInfo);
	}
	$infoDeque = [];
	$infoDeque["id"] = $Deque->id;
	$infoDeque["nome"] = $Deque->nome;
	$infoDeque["descricao"] = $Deque->descricao;
	$infoDeque["atributos"] = [];
	foreach ($Deque->atributos as $atributo) {
		$infoDeque["atributos"][] = $atributo->id;
	}
	$infoDeque["atributos"] = implode(",", $infoDeque["atributos"]);
	foreach ($Jogadores as $jogador) {
		$comm->enviarComm($jogador->conexao,"lobby",[
			"nome"=>$nomeLobby,
			"deque"=>$infoDeque,
			"jogadores"=>$infoJogadores
		]);
	}
	atualizarSala();
}
function registrarJogadoresProntos() {
	global $Jogadores, $timerInatividade;
	$timerInatividade->resetar(10000);
	$numJogadores = count($Jogadores);
	$numProntos = count(array_filter($Jogadores, function($jogador) {
		return $jogador->pronto;
	}));
	verbose("Jogadores prontos: {$numProntos} / {$numJogadores}\n");
	enviarInfoLobby();
}
?>
<?php
if (!isset($path)) {
	$path = "";
}
require_once $path."funcoes.php";

$server = null;
$clientes = array($server);
$comm = null;
$porta = 0;

class Comm {
	protected $clients;

    public function __construct() {
        $this->clients = array();
    }

    public function onOpen($conn) {
        $this->clients[(int)$conn->resourceId] = $conn;
		verbose("Nova conexão: ({$conn->resourceId})\n");
		$conn->send(json_encode([
			'tipo' => 'welcome',
			'conteudo' => [
				'resourceId' => $conn->resourceId
			]
		]));
		return true;
		/*
        global $tempoPlanejamento;
        $conn->send(json_encode([
            'tipo' => 'infoServer',
            'conteudo' => [
                'resourceId' => $conn->resourceId,
                'timerPlan' => $tempoPlanejamento
            ]
        ]));
        global $jogadores, $estadoPartida;
        if ($estadoPartida === EstadoPartida::LOBBY) {
            $jogadoresSemConexao = array_filter($jogadores, function($jogador) {
                return $jogador->usuario === null;
            });
            if (!empty($jogadoresSemConexao)) {
                $jogadorAleatorio = $jogadoresSemConexao[array_rand($jogadoresSemConexao)];
                atribuirConexaoAJogador($conn, $jogadorAleatorio->id);
            }
            $status = json_encode([
                'tipo' => 'status',
                'conteudo' => json_decode(obterStatusPartida())
            ]);
            foreach ($this->clients as $client) {
                $client->send($status);
                $client->send(json_encode([
                            "tipo"=>"msg",
                            "conteudo"=>[
                                "remetente"=>-1,
                                "msg"=>"{$jogadorAleatorio->nome} entrou na sala"]]));
            }
        }
		*/
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
        unset($this->clients[(int)$conn->resourceId]);
        verbose("Conexão ({$conn->resourceId}) fechada\n");
    }

    public function onError($conn, $e) {
        verbose("Erro: {$e->getMessage()}\n");
        $conn->close();
    }

    public function obterClientes() {
        return $this->clients;
    }
}
class Conexao {
    public $resourceId;
    public $socket;

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
	verbose("Sala iniciada na porta $_porta.\n");
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
function checarDesconexoes() {

}
function receberMensagem($cliente) {

}
function enviarMensagem($cliente, $mensagem) {

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
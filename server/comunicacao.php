<?php
if (!isset($path)) {
	$path = "";
}
require_once $path."funcoes.php";

$server = null;
$clientes = array();
$comm = null;

class Comm {
	protected $clients;

    public function __construct() {
        $this->clients = array();
    }

    public function onOpen($conn) {
        $this->clients[(int)$conn->resourceId] = $conn;
		verbose("Nova conexão: ({$conn->resourceId})");
        registrarNoLog("Nova conexão: ({$conn->resourceId})");
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
    }

    public function onMessage($from, $msg) {
        if (strpos($msg, '\\') === 0) {
            // Interpretar como comando ao servidor
            $parts = explode(' ', substr($msg, 1));
            $command = $parts[0];
            $args = array_slice($parts, 1);
            registrarNoLog(sprintf("Comando recebido de %d: $command com argumentos: %s", $from->resourceId, implode(' ', $args)));
            switch ($command) {
                case 'stop':
                    registrarNoLog("Servidor parando...");
                    exit();
                    break;
                case 'reset':
                    registrarNoLog("Reiniciando estados e jogadores...");
                    inicializarEstadosEJogadores();
                    break;
                case 'check':
                    $status = obterStatusPartida();
                    $from->send($status);
                    registrarNoLog($status);
                    break;
                case 'updateEstados':
                    $jsonEstados = obterJSONEstados();
                    $from->send(json_encode([
                        'tipo' => 'update',
                        'conteudo' => $jsonEstados])
                    );
                    registrarNoLog("Jogador {$from->resourceId} solicitou atualização dos estados");
                    break;
                case 'nextTurn':
                    avancarDataRodada();
                    break;
                case 'ping':
                    $from->send("pong");
                    break;
                case 'linkPlayer':
                    $jogadorId = $args[0];
                    atribuirConexaoAJogador($from, $jogadorId);
                    $status = json_encode([
                        'tipo' => 'status',
                        'conteudo' => json_decode(obterStatusPartida())
                    ]);
                    foreach ($this->clients as $client) {
                        $client->send($status);
                    }
                    break;
                case 'renamePlayer':
                    $novoNome = implode(' ', $args);
                    obterJogadorDeConexao($from)->nome = $novoNome;
                    registrarNoLog("Jogador {$from->resourceId} renomeado para {$novoNome}");
                    $status = json_encode([
                        'tipo' => 'status',
                        'conteudo' => json_decode(obterStatusPartida())
                    ]);
                    foreach ($this->clients as $client) {
                        $client->send($status);
                    }
                    break;
                case 'action':
                    global $estados;
                    $origemId = $args[0];
                    switch($args[1]) {
                        case 'ATQ':
                            $tipo = TipoAcao::ATAQUE;
                            break;
                        case 'DEF':
                            $tipo = TipoAcao::DEFESA;
                            break;
                        case 'REF':
                            $tipo = TipoAcao::REFORCO;
                            break;
                        default:
                            $from->send("Tipo de ação desconhecido");
                            return;
                    }
                    $destinoId = $args[2] ?? null;
                    $agua = $args[3] ?? false;
                    $origem = null;
                    $destino = null;
                    foreach ($estados as $estado) {
                        if ($estado->id === $origemId) {
                            $origem = $estado;
                        }
                        if ($estado->id === $destinoId) {
                            $destino = $estado;
                        }
                    }
                    if ($origem) {
                        criarAcao($origem, $tipo, $destino, $agua);
                        registrarNoLog("Ação criada: {$tipo->toString()} de {$origem->id}" . ($destino ? " para {$destino->id}" : ""));
                    } else {
                        registrarNoLog("Falha ao criar ação: origem não encontrada");
                        die();
                    }
                    break;
                case 'ready':
                    global $numJogadoresProntos;
                    global $jogadores;
                    $numJogadoresProntos++;
                    registrarNoLog("Jogador " . obterJogadorDeConexao($from)->id . " pronto");
                    $humanPlayers = array_filter($jogadores, function($jogador) {
                        return !$jogador->cpu;
                    });
                    $readyMessage = json_encode([
                        'tipo' => 'ready',
                        'conteudo' => $from->resourceId
                    ]);
                    foreach ($this->clients as $client) {
                        $client->send($readyMessage);
                    }
                    $remainingPlayers = count($humanPlayers) - $numJogadoresProntos;
                    registrarNoLog("Aguardando mais {$remainingPlayers} jogadores");
                    break;
                case 'notReady':
                    global $numJogadoresProntos;
                    global $jogadores;
                    $numJogadoresProntos--;
                    registrarNoLog("Jogador " . obterJogadorDeConexao($from)->id . " não está mais pronto");
                    $humanPlayers = array_filter($jogadores, function($jogador) {
                        return !$jogador->cpu;
                    });
                    $readyMessage = json_encode([
                        'tipo' => 'notReady',
                        'conteudo' => $from->resourceId
                    ]);
                    foreach ($this->clients as $client) {
                        $client->send($readyMessage);
                    }
                    $remainingPlayers = count($humanPlayers) - $numJogadoresProntos;
                    registrarNoLog("Aguardando mais {$remainingPlayers} jogadores");
                    break;
                default:
                    registrarNoLog("Comando desconhecido: $command");
                    break;
            }
        } else {
            // Interpretar como mensagem de chat
            $numRecv = count($this->clients) - 1;
            registrarNoLog(sprintf('Conexão %d enviou mensagem "%s" para %d outras conexões',
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
        $jogador = obterJogadorDeConexao($conn);
        if ($jogador) {
            $jogador->usuario = null;
            $jogador->cpu = true;
            registrarNoLog("Conexão ({$conn->resourceId}) desvinculada do jogador {$jogador->id}");
        }
        unset($this->clients[(int)$conn->resourceId]);
        registrarNoLog("Conexão ({$conn->resourceId}) fechada");
    }

    public function onError($conn, $e) {
        registrarNoLog("Erro: {$e->getMessage()}");
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
	global $server;
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
	$comm = new Comm();
}
function checarConexoes() {
	global $clientes;
	$read = $clientes;
    $write = null;
    $except = null;

    if (stream_select($read, $write, $except, 0, 10) > 0) {
        verificarNovasConexoes($read);
        foreach ($read as $conn) {
            heartBeat($conn);
        }
    }
}
function verificarNovasConexoes($_read) {
	if (in_array($server, $_read)) {
		$conn = stream_socket_accept($server);
		if ($conn) {
			$connection = new Conexao($conn);
			$clientes[] = $conn;

			// Perform WebSocket handshake
			$headers = fread($conn, 1024);
			perform_handshaking($headers, $conn, 'localhost', 12346);

			$chat->onOpen($connection);
		}
		unset($_read[array_search($server, $_read)]);
	}
}
function heartBeat($conn) {
	$msg = fread($conn, 1024);
	if ($msg === false || $msg === '') {
		$connection = new Connection($conn);
		$chat->onClose($connection);
		fclose($conn);
		unset($clientes[array_search($conn, $clientes)]);
	} else {
		$decoded_msg = unmask($msg);
		$connection = new Connection($conn);
		$chat->onMessage($connection, $decoded_msg);
	}
}
function checarDesconexoes() {

}
function receberMensagem($cliente) {

}
function enviarMensagem($cliente, $mensagem) {

}
?>
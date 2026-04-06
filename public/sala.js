const btnPronto = document.getElementById("btnPronto");
const pStatusDebug = document.getElementById("statusDebug");

var socket = null;
var pronto = false;
var meuId = 0;

function iniciarWebSocket(_porta,_endereco,_seguro) {
	let protocolo = (_seguro?"wss":"ws");
	let novoSocket = new WebSocket(`${protocolo}://${_endereco}:${_porta}`);
	novoSocket.onopen = () => {
		console.log("Conectado ao servidor");
	};
	novoSocket.onmessage = (evento) => {
		console.log("<== RECEBIDO\n", evento.data);
		jsonServer = JSON.parse(evento.data);
		switch (jsonServer.tipo) {
			case "welcome":
				meuId = jsonServer.conteudo.resourceId;
				pStatusDebug.innerHTML = "Conectado. ID de conexão: "+meuId;
				enviarMensagem("\\thnx");
				break;
			case "ready":
				if (jsonServer.conteudo.resourceId == meuId) {
					pronto = true;
					btnPronto.classList.add("ok");
				}
				break;
			case "notready":
				if (jsonServer.conteudo.resourceId == meuId) {
					pronto = false;
					btnPronto.classList.remove("ok");
				}
				break;
			case "deque":
				gerarDequeJSON(jsonServer.conteudo);
				break;
			case "carta":
				if (jsonServer.conteudo.resourceId == meuId) {
					exibirCarta(jsonServer.conteudo.carta);
				}
				break;
		}
	};
	novoSocket.onerror = (erro) => {
		console.error("Erro na conexão:", erro);
	};
	novoSocket.onclose = () => {
		console.log("Desconectado do servidor");
	};
	return novoSocket;
}

function conectarServidor(_porta,_endereco="localhost",_seguro=true) {
	try {
		socket = iniciarWebSocket(_porta,_endereco,_seguro);
	} catch(_erro) {
		console.error("Falha ao conectar no servidor:",_erro);
	}
}

function enviarMensagem(_texto) {
	if (socket.readyState === WebSocket.OPEN) {
		console.log("ENVIANDO ==>\n", _texto);
		socket.send(_texto);
	}
};

function enviarPronto() {
	if (pronto) {
		enviarMensagem("\\notready");
	} else {
		enviarMensagem("\\ready");
	}
}
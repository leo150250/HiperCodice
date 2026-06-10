var socket = null;
var pronto = false;
var meuId = 0;

class Socket {
	constructor(_servidor,_porta) {
		this.servidor = _servidor;
		this.porta = _porta;
		this.socket = new WebSocket(`wss://${this.servidor}:${this.porta}`);
		this.socket.onopen = () => {
			console.log("Conectado ao servidor");
			socket = this;
		};
		this.socket.onmessage = (_evento) => {
			this.respostaServidor(_evento);
		}
		this.socket.onerror = (_erro) => {
			console.error("Erro na conexão:", _erro);
		};
		this.socket.onclose = () => {
			console.log("Desconectado do servidor");
		};
	}
	respostaServidor(_evento) {
		console.log("SERVIDOR:",_evento);
	}
}

function iniciarJogoMP(_servidor,_porta) {
	divMenu.style.display="none";
	new Socket(_servidor,_porta);
}
var comm = null;
var pronto = false;
var meuId = 0;

class Comm {
	constructor(_servidor,_porta) {
		this.servidor = _servidor;
		this.porta = _porta;
		this.filaMensagem = [];
		this.processandoFila = false;
		this.filaEspera = 200;
		console.log(this.filaMensagem);

		console.log(`Conectando em ${this.servidor}:${this.porta}...`);
		this.socket = new WebSocket(`ws://${this.servidor}:${this.porta}`);
		this.socket.onopen = () => {
			console.log("Conectado!");
			comm = this;
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
		let resposta = JSON.parse(_evento.data);
		console.log("<== RECEBIDO:",resposta);
		switch (resposta.tipo) {
			case "welcome": {
				meuId = resposta.conteudo.resourceId;
				this.enviarMensagem(`\\thnx ${configNome}`);
				this.enviarMensagem(`\\ready`);
			}
		}
	}
	enviarMensagem(_mensagem) {
		this.filaMensagem.push(_mensagem);
		setTimeout(()=>{
			this.processarFilaMensagem();
		},10);
	}
	processarFilaMensagem() {
		if (this.processandoFila) {
			return;
		}
		if (this.filaMensagem.length > 0) {
			this.processandoFila = true;
			this._enviarProximaMensagem();
			if (this.filaMensagem.length == 0) {
				this.processandoFila = false;
			} else {
				setTimeout(()=>{
					this._enviarProximaMensagem();
				},this.filaEspera);
			}
		}
	}
	_enviarProximaMensagem() {
		if (this.socket.readyState === WebSocket.OPEN) {
			console.log("ENVIANDO ==>", this.filaMensagem[0]);
			this.socket.send(this.filaMensagem.shift());
		}
	}
}

function iniciarJogoMP(_servidor,_porta) {
	divMenu.style.display="none";
	new Comm(_servidor,_porta);
}
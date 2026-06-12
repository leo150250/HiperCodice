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
		this.pronto = false;
		console.log(this.filaMensagem);

		console.log(`Conectando em ${this.servidor}:${this.porta}...`);
		this.socket = new WebSocket(`ws://${this.servidor}:${this.porta}`);
		this.socket.onopen = () => {
			console.log("Conectado!");
			this.pronto = true;
			comm = this;
		};
		this.socket.onmessage = (_evento) => {
			this.respostaServidor(_evento);
		}
		this.socket.onerror = (_erro) => {
			console.error("Erro na conexão:", _erro);
			exibirMensagem(`O servidor não retornou resposta.`);
		};
		this.socket.onclose = (_ev) => {
			console.log("Desconectado do servidor", _ev);
			if (this.pronto) {
				exibirMensagem(`Você foi desconectado do servidor.`);
			}
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
			} break;
			case "goaway": {
				this.pronto = false;
				exibirMensagem(`Não foi possível conectar: ${resposta.conteudo.msg}`);
				this.socket.close();
			} break;
			case "deque": {
				gerarDequeJSON(resposta.conteudo);
				defineFundo(deque.id);
				esconderTodosDialogos();
				executarMusicaAleatoria();
			} break;
			case "jogadores": {				
				for (let i = 0; i < resposta.conteudo.length; i++) {
					criarNovoJogador(resposta.conteudo[i].nome,resposta.conteudo[i].resourceId);
				}
			} break;
			case "carta": {
				let cartaSelecionada = deque.cartas[resposta.conteudo.carta];
				jogadores.forEach(_jogador => {
					_jogador.cartas = [];
				});
				jogadores[idJogador].cartas[0] = cartaSelecionada;
				cartaJogadorDesenhada = exibirCarta(cartaSelecionada);
				divNumCartasJogador.textContent = `🃏${resposta.conteudo.qtd}`;
				divNumJogadores.textContent = `👥${jogadores.length}`;
			} break;
			case "jogar": {
				zerarElementosRodada();
				let jogadorAtual;
				jogadores.forEach(_jogador=>{
					if (_jogador.conexao == resposta.conteudo.resourceId) {
						jogadorAtual = destacarJogador(_jogador.id);
					}
				});
				if (jogadorAtual.conexao == meuId) {
					divMensagemJogador.textContent = "É a sua vez de jogar. Escolha um atributo:";
					minhaVez();
				} else {
					divMensagemJogador.textContent = `É a vez de ${jogadorAtual.nome}.`
					vezDeJogador(jogadorAtual);
				}
			} break;
			case "escolha": {
				divMensagemJogador.textContent = "Aguardando os outros jogadores...";
				atributoEscolhido = resposta.conteudo.atributo;
				console.log(`Atributo escolhido: ${deque.atributos[atributoEscolhido].nome}`);
				jogadores.forEach(_jogador=>{
					if (!_jogador.ativo) {
						return;
					}
					if (_jogador.cartas.length == 0) {
						for (let i = 0; i < resposta.conteudo.cartasJogadores.length; i++) {
							if (resposta.conteudo.cartasJogadores[i].jogador == _jogador.conexao) {
								_jogador.cartas[0] = deque.cartas[resposta.conteudo.cartasJogadores[i].carta];
							}
						}
					}
					if (_jogador !== jogadores[jogadorDaVez]) {
						setTimeout(()=>{
							new ElementoRodada(_jogador.cartaAtual(),_jogador);
						},1000 + (Math.random()*1000));
					}
				});
				if (!vezDeJogar) {
					divMensagemJogador.textContent = `${jogadores[jogadorDaVez].nome} escolheu ${deque.atributos[atributoEscolhido].nome}`;
					exibirElementosRodada();
					new ElementoRodada(jogadores[jogadorDaVez].cartaAtual(),jogadores[jogadorDaVez]);
					destacarAtributo(atributoEscolhido);
					executarSom("attrib.wav");
				}
				executarRodada();
			} break;
		}
	}
	enviarMensagem(_mensagem) {
		console.log("Enviando mensagem...");
		this.filaMensagem.push(_mensagem);
		setTimeout(()=>{
			this.processarFilaMensagem();
		},10);
	}
	processarFilaMensagem() {
		if (this.filaMensagem.length == 0) {
			this.processandoFila = false;
		}
		if (this.processandoFila) {
			console.log("Tô processando...",this.filaMensagem);
			return;
		}
		if (this.filaMensagem.length > 0) {
			this.processandoFila = true;
			this._enviarProximaMensagem();
		}
	}
	_enviarProximaMensagem() {
		if (this.socket.readyState === WebSocket.OPEN) {
			console.log("ENVIANDO ==>", this.filaMensagem[0]);
			this.socket.send(this.filaMensagem.shift());
			if (this.filaMensagem.length == 0) {
				this.processandoFila = false;
			} else {
				setTimeout(()=>{
					this._enviarProximaMensagem();
				},this.filaEspera);
			}
		}
	}
}

function iniciarJogoMP(_servidor,_porta) {
	divMenu.style.display="none";
	new Comm(_servidor,_porta);
}

function criarNovoJogador(_nome,_resourceId) {
	let novoJogador = new Jogador(_nome,false,_resourceId);
	if (novoJogador.conexao == meuId) {
		idJogador = novoJogador.id;
	}
	divAmbiente.appendChild(novoJogador.elemento);
	posicionarJogadores();
	return novoJogador;
}

function executarJogador(_jogador) {
	//Sem efeito. A "execução" do jogador no multiplayer é literalmente ESPERAR pela ação do jogador!
}

function executarEscolha(_id) {
	comm.enviarMensagem(`\\escolha ${_id}`);
}
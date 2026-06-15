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
				numRodadas = 0;
				dataHoraInicio = new Date();
				setInterval(()=>{
					divTimer.textContent = `⏱️${obterTempoDaPartida()}`;
				},1000);
				zerarElementosRodada();
				gerarDequeJSON(resposta.conteudo);
				defineFundo(deque.id);
				executarMusicaAleatoria();
				esconderTodosDialogos();
			} break;
			case "jogadores": {	
				for (let i = 0; i < resposta.conteudo.length; i++) {
					let novoJogador = criarNovoJogador(resposta.conteudo[i].nome,resposta.conteudo[i].resourceId);
					novoJogador.qtdCartas = resposta.conteudo[i].qtdCartas;
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
				setTimeout(executarRodada,3000);
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

function verificarVencedor(_jogadorVencedor,_tempoExecucao) {
	//Enviar as cartas dos perdedores para o vencedor
	let numJogadoresAtivos = 0;
	let cartasGanhas = [];
	jogadores.forEach(_jogador=>{
		if (!_jogador.ativo) {
			return;
		}
		let numDiferencaCartas = -_jogador.qtdCartas;
		numJogadoresAtivos++;
		if (_jogador !== _jogadorVencedor) {
			cartasGanhas.push(_jogador.cartaAtual());
			_jogadorVencedor.qtdCartas++;
			_jogador.qtdCartas--;
			if (_jogador.qtdCartas == 0) {
				_jogador.perder();
				console.log(`Jogador ${_jogador.nome} eliminado!`);
				numJogadoresAtivos--;
			}
		}
		numDiferencaCartas += _jogador.qtdCartas;
		setTimeout(()=>{
			if (_jogador == jogadores[idJogador]) {
				if (jogadores[idJogador] == _jogadorVencedor) {
					numDiferencaCartas = numJogadoresAtivos-1;
					for (let i = 0; i < cartasGanhas.length; i++) {
						let divCartaGanha = divCartaJogador.appendChild(cartasGanhas[i].desenhar());
						divCartaGanha.classList.add("adquirida");
						divCartaGanha.style.animationDelay = (i/4) + "s";
						setTimeout(()=>{
							executarSom("cardAdd.wav");
						},500 + ((i/4) * 1000));
						let posicaoCarta = (-(cartasGanhas.length - 1) / 2) + i;
						posicaoCarta *= 100;
						console.log(`Posição: ${posicaoCarta}`);
						divCartaGanha.style.transform = `rotate(${-5+(Math.random()*10)}deg) scale(0.8) translate(${posicaoCarta}%,0%)`;
					};
				}
				if (numDiferencaCartas>0) {
					divFxDifCartasJogador.textContent = "+";
					divFxDifCartasJogador.style.backgroundColor = "#26b32b";
				} else {
					divFxDifCartasJogador.textContent = "-";
					divFxDifCartasJogador.style.backgroundColor = "#b32626";
				}
				divFxDifCartasJogador.style.display = "block";
				setTimeout(()=>{
					divFxDifCartasJogador.style.display = null;
				},2500);
				divFxDifCartasJogador.textContent += Math.abs(numDiferencaCartas);
				divNumCartasJogador.textContent = `🃏${_jogador.qtdCartas}`;
			}
		},100);
	});
	_jogadorVencedor.adicionarCarta(_jogadorVencedor.removerCartaAtual());

	if (numJogadoresAtivos == 1) {
		console.log(`Jogador ${_jogadorVencedor.nome} venceu a partida!`);
		if (_jogadorVencedor == jogadores[idJogador]) {
			divMensagemJogador.textContent = `Você venceu!`;
			spanResultadoPartida.textContent = "venceu";
			spanResultadoDeque.textContent = `${deque.nome} (#${deque.id})`;
			spanResultadoRodadas.textContent = numRodadas;
			spanResultadoTempo.textContent = obterTempoDaPartida();
			exibirDialogo('dialogPartidaEncerrada');
		}
	} else {
		setTimeout(()=>{
			divNumJogadores.textContent = `👥${numJogadoresAtivos}`;
			if (jogadores[idJogador].ativo) {
				divMensagemJogador.textContent = "Aguardando o servidor...";
			} else {
				jogadores[idJogador].perder();
				divMensagemJogador.textContent = `Você perdeu!`;
				console.log("GAME OVER!! Você perdeu!");
				spanResultadoPartida.textContent = "perdeu";
				spanResultadoDeque.textContent = `${deque.nome} (#${deque.id})`;
				spanResultadoRodadas.textContent = numRodadas;
				spanResultadoTempo.textContent = obterTempoDaPartida();
				exibirDialogo('dialogPartidaEncerrada');
			}
		},_tempoExecucao);
	}
}
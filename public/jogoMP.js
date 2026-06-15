var comm = null;
var pronto = false;
var meuId = 0;

function iniciarJogoMP(_JSONdeque) {
	numRodadas = 0;
	dataHoraInicio = new Date();
	setInterval(()=>{
		divTimer.textContent = `⏱️${obterTempoDaPartida()}`;
	},1000);
	zerarElementosRodada();
	gerarDequeJSON(_JSONdeque);
	defineFundo(deque.id);
	executarMusicaAleatoria();
	esconderTodosDialogos();
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

function conectarServidor(_servidor,_porta) {
	divMenu.style.display="none";
	new Comm(_servidor,_porta);
}
function gerarJogadores(_jogadoresServidor) {
	for (let i = 0; i < _jogadoresServidor.length; i++) {
		let novoJogador = criarNovoJogador(_jogadoresServidor[i].nome,_jogadoresServidor[i].resourceId);
		novoJogador.qtdCartas = _jogadoresServidor[i].qtdCartas;
	}
}
function carregarCartas(_cartasServidor) {
	let cartaSelecionada = deque.cartas[_cartasServidor.carta];
	jogadores.forEach(_jogador => {
		_jogador.cartas = [];
	});
	jogadores[idJogador].cartas[0] = cartaSelecionada;
	cartaJogadorDesenhada = exibirCarta(cartaSelecionada);
	divNumCartasJogador.textContent = `🃏${_cartasServidor.qtd}`;
	divNumJogadores.textContent = `👥${jogadores.length}`;
}
function rodada(_conexaoIdDaVez) {
	zerarElementosRodada();
	let jogadorAtual;
	jogadores.forEach(_jogador=>{
		if (_jogador.conexao == _conexaoIdDaVez) {
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
}
function processarEscolha(_escolhaServidor) {
	divMensagemJogador.textContent = "Aguardando os outros jogadores...";
	atributoEscolhido = _escolhaServidor.atributo;
	console.log(`Atributo escolhido: ${deque.atributos[atributoEscolhido].nome}`);
	jogadores.forEach(_jogador=>{
		if (!_jogador.ativo) {
			return;
		}
		if (_jogador.cartas.length == 0) {
			for (let i = 0; i < _escolhaServidor.cartasJogadores.length; i++) {
				if (_escolhaServidor.cartasJogadores[i].jogador == _jogador.conexao) {
					_jogador.cartas[0] = deque.cartas[_escolhaServidor.cartasJogadores[i].carta];
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
}
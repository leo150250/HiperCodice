function iniciarJogoSP(_numCPUs=3, _deque=0, _atributos=[]) {
	divMenu.style.display="none";
	criarNovoJogador("Jogador",false);
	for (let i = 1; i <= _numCPUs; i++) {
		criarNovoJogador(`CPU ${i}`);
	}
	numRodadas = 0;
	dataHoraInicio = new Date();
	setInterval(()=>{
		divTimer.textContent = `⏱️${obterTempoDaPartida()}`;
	},1000);
	zerarElementosRodada();
	fetch('getDeque.php',{
		method: "POST",
		headers: { "Content-Type": "application/x-www-form-urlencoded" },
		body: new URLSearchParams({
			deque: _deque,
			atributos: _atributos.toString()
		})
	}).then(response => response.json())
		.then(data => {
			gerarDequeJSON(data);
			defineFundo(deque.id);
			embaralharEDistribuirCartas();
			exibirCartasJogadores();
			//jogadores[idJogador].cartaAtual().info();
			cartaJogadorDesenhada = exibirCarta(jogadores[idJogador].cartaAtual().id);
			divNumCartasJogador.textContent = `🃏${jogadores[idJogador].cartas.length}`;
			divNumJogadores.textContent = `👥${jogadores.length}`;
			//console.log(jogadores[idJogador].cartaAtual());
			destacarJogador(0);
			rodada();
			executarMusicaAleatoria();
			esconderTodosDialogos();
		})
		.catch(error => console.error('Erro ao obter o deque:', error));
}

function embaralharEDistribuirCartas() {
	if (deque.cartas.length < 32) {
		console.error("Há um problema com o deque.",deque.cartas);
		throw new Error("Interrompendo embaralhamento...");
	}
	let dequeEmbaralhado = deque.cartas;
	dequeEmbaralhado.sort(()=>Math.random() - 0.5);
	
	//Distribui as cartas do deque entre os jogadores
	console.log(`Embaralhando e distribuindo deque para ${jogadores.length} jogadores...`);
	dequeEmbaralhado.forEach((carta, index) => {
		jogadores[index % jogadores.length].adicionarCarta(carta);
	});
}

function criarNovoJogador(_nome,_cpu=true) {
	let novoJogador = new Jogador(_nome,_cpu);
	if (!_cpu) {
		idJogador = novoJogador.id;
	}
	divAmbiente.appendChild(novoJogador.elemento);
	posicionarJogadores();
	return novoJogador;
}

function exibirCartasJogadores() {
	console.log("Cartas dos jogadores:");
	jogadores.forEach(_jogador=>{
		console.log(`${_jogador.nome}: ${!_jogador.ativo?"Eliminado":_jogador.obterListagemCartas()}`);
	})
}

function rodada() {
	numRodadas++;
	let jogadorAtual = jogadores[jogadorDaVez];
	console.log(`É a vez de ${jogadorAtual.nome}!\n`);
	console.log(`Carta atual:`);
	jogadorAtual.cartaAtual().info(true);
	atributoEscolhido = -1;
	if (!jogadorAtual.cpu) {
		divMensagemJogador.textContent = "É a sua vez de jogar. Escolha um atributo:";
		minhaVez();
	} else {
		divMensagemJogador.textContent = `É a vez de ${jogadorAtual.nome}.`
		vezDeJogador(jogadorAtual);
		//atributoEscolhido = Math.floor(Math.random() * (deque.atributos.length - 1));
	}
	//girarRodada(atributoEscolhido);
}

function executarEscolha() {
	//Comportamento de jogo Single-Player
	//Faz cada CPU exibir os valores dos atributos de suas cartas atuais depois de algum tempo de espera
	divMensagemJogador.textContent = "Aguardando os outros jogadores...";
	jogadores.forEach(_jogador=>{
		if (!_jogador.ativo) {
			return;
		}
		if (_jogador.cpu) {
			setTimeout(()=>{
				new ElementoRodada(_jogador.cartaAtual(),_jogador);
			},1000 + (Math.random()*1000));
		}
	});
	//Executa a rodada após 2 segundos (com todo mundo exibindo)
	setTimeout(executarRodada,2500);
}

function executarJogador(_jogadorCPU) {
	//No caso do jogo SP, essa função é chamada só pra CPU então, vamos escolher um atributo aleatório e #xablau
	atributoEscolhido = Math.floor(Math.random()*deque.atributos.length);
	console.log(`Atributo escolhido: ${deque.atributos[atributoEscolhido].nome}`);
	//1,5 segundos pra exibir os atributos escolhidos
	setTimeout(()=>{
		divMensagemJogador.textContent = `${_jogadorCPU.nome} escolheu ${deque.atributos[atributoEscolhido].nome}`;
		exibirElementosRodada();
		new ElementoRodada(_jogadorCPU.cartaAtual(),_jogadorCPU);
		destacarAtributo(atributoEscolhido);
	},1500);
	//2,5~3,5 segundos pra exibir os elementos dos atributos de cada um
	jogadores.forEach(_jogador=>{
		if (!_jogador.ativo) {
			return;
		}
		if (_jogador !== _jogadorCPU) {
			setTimeout(()=>{
				new ElementoRodada(_jogador.cartaAtual(),_jogador);
			},2500 + (Math.random()*1000));
		}
	});
	//Executa a rodada após 4 segundos (com todo mundo exibindo)
	setTimeout(executarRodada,4000);
}

function verificarVencedor(_jogadorVencedor) {
	//Enviar as cartas dos perdedores para o vencedor
	let numJogadoresAtivos = 0;
	let cartasGanhas = [];
	jogadores.forEach(_jogador=>{
		if (!_jogador.ativo) {
			return;
		}
		let numDiferencaCartas = -_jogador.cartas.length;
		numJogadoresAtivos++;
		if (_jogador !== _jogadorVencedor) {
			cartasGanhas.push(_jogador.cartaAtual());
			jogadoresVencedores[0].adicionarCarta(_jogador.removerCartaAtual());
			if (_jogador.cartas.length == 0) {
				_jogador.perder();
				console.log(`Jogador ${_jogador.nome} eliminado!`);
				numJogadoresAtivos--;
			}
		}
		numDiferencaCartas += _jogador.cartas.length;
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
				divNumCartasJogador.textContent = `🃏${_jogador.cartas.length}`;
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
			exibirCartasJogadores();
			divNumJogadores.textContent = `👥${numJogadoresAtivos}`;
			if (jogadores[idJogador].ativo) {
				cartaJogadorDesenhada = exibirCarta(jogadores[idJogador].cartaAtual().id);
				rodada();
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
		},tempoExecucao);
	}
}
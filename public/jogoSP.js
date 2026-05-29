var jogadorDaVez = -1;
var emExecucao = false;
var encerrada = false;
var timerProntidao = -1;
var dataHoraInicio = null;

function defineFundo(_deck) {
	let corSelecionada = Math.floor(Math.random()*cores.length);
	divAmbiente.style.backgroundImage = `radial-gradient(${cores[corSelecionada][1]}, ${cores[corSelecionada][2]}), url("img/decks/${_deck}/default.jpg")`;
}

function iniciarJogoSP() {
	esconderDialogo("carregando");
	divMenu.style.display="none";
	defineFundo(1);
	criarNovoJogador("Jogador",false);
	criarNovoJogador("CPU 1");
	criarNovoJogador("CPU 2");
	criarNovoJogador("CPU 3");
	numRodadas = 0;
	dataHoraInicio = new Date();
	zerarElementosRodada();
	fetch('getDeque.php')
		.then(response => response.json())
		.then(data => {
			gerarDequeJSON(data);
			embaralharEDistribuirCartas();
			exibirCartasJogadores();
			//jogadores[idJogador].cartaAtual().info();
			exibirCarta(jogadores[idJogador].cartaAtual().id);
			//console.log(jogadores[idJogador].cartaAtual());
			jogadorDaVez = 0;
			rodada();
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

function posicionarJogadores() {
	let angulo = 270;
	let diferencaAngulo = 360 / jogadores.length;
	jogadores.forEach(_jogador=>{
		let posX = 50;
		let posY = 50;
		posX -= Math.cos(angulo * (Math.PI / 180)) * 25;
		posY -= Math.sin(angulo * (Math.PI / 180)) * 35;
		_jogador.definirPosicaoElementoPadrao(posX,posY);
		_jogador.posicionarElementoPadrao();
		angulo+=diferencaAngulo;
	});
}

function destacarJogador(_idProximoJogador) {
	if (jogadorDaVez >= 0) {
		jogadores[jogadorDaVez].restaurar();
	}
	jogadorDaVez = _idProximoJogador;
	jogadores[jogadorDaVez].destacar();
	return jogadores[jogadorDaVez];
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
		minhaVez();
	} else {
		vezDeJogador(jogadorAtual);
		//atributoEscolhido = Math.floor(Math.random() * (deque.atributos.length - 1));
	}
	//girarRodada(atributoEscolhido);
}

function executarEscolha() {
	//Comportamento de jogo Single-Player
	//Faz cada CPU exibir os valores dos atributos de suas cartas atuais depois de algum tempo de espera
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

function executarRodada() {
	let jogadorEspecial = null;
	let nomesVencedores = "";
	let jogadoresVencedores = [];
	let tempoExecucao = 5000;
	let tempoExibirCartaVencedor = 1;
	console.log("Valores dos jogadores:");
	jogadores.forEach(_jogador=>{
		if (!_jogador.ativo) {
			return;
		}
		console.log(` - ${_jogador.nome}: ${_jogador.cartaAtual().valores[atributoEscolhido]} (${_jogador.cartaAtual().obterCodCarta()}) ${_jogador.cartaAtual().especial?" [Especial]":""}`);
		if (_jogador.cartaAtual().especial) {
			jogadorEspecial = _jogador;
		}
	})
	if (jogadorEspecial != null) { //Tem alguém com carta especial?
		console.log(`Jogador ${jogadorEspecial.nome} tem a carta especial...`);
		elementosRodada.forEach(_elementoRodada=>{
			if (_elementoRodada.jogador == jogadorEspecial) {
				_elementoRodada.destacar();
			}
		})
		for (let i = 0; i < jogadores.length; i++) {
			if (jogadores[i] == jogadorEspecial) {
				destacarJogador(i);
				break;
			}
		}
		let jogadoresClasse1 = []
		jogadores.forEach(_jogador=>{
			if (!_jogador.ativo) {
				return;
			}
			if ((_jogador !== jogadorEspecial)
			&& (_jogador.cartaAtual().classe == 1)) {
				jogadoresClasse1.push(_jogador);
			}
		});
		if (jogadoresClasse1.length > 0) {
			nomesVencedores = "";
			jogadoresClasse1.forEach(_jogador=>{
				nomesVencedores += " " + _jogador.nome;
				jogadoresVencedores.push(_jogador);
			})
			tempoExecucao += 2000;
			divAmbiente.appendChild(jogadorEspecial.cartaAtual().desenhar());
			divElementosRodada.style.bottom = "20px";
			divElementosRodada.style.marginBottom = "0em";
			console.log("...mas os jogadores com carta de classe 1 vencem:" + nomesVencedores);
			tempoExibirCartaVencedor += 2000;
		} else {
			jogadoresVencedores.push(jogadorEspecial);
			console.log("...e vence a rodada!");
		}
	} else { //Jogada convencional, melhor valor vence
		let melhorValor = null;
		jogadores.forEach(_jogador=>{
			if (!_jogador.ativo) {
				return;
			}
			let valor = _jogador.cartaAtual().valores[atributoEscolhido];
			if ((melhorValor == null)
			|| (deque.atributos[atributoEscolhido].forma == 1 && valor > melhorValor)
			|| (deque.atributos[atributoEscolhido].forma == 0 && valor < melhorValor)) {
				melhorValor = valor;
				jogadoresVencedores = [_jogador];
			} else if (valor == melhorValor) {
				jogadoresVencedores.push(_jogador);
			}
		});
	}
	if (jogadoresVencedores.length > 1) { //Se houver mais de um vencedor, é um empate
		nomesVencedores = "";
		jogadoresVencedores.forEach(_jogador=>{
			nomesVencedores += " " + _jogador.nome;
		})
		console.log(`Empate entre:${nomesVencedores}`);
		let vencedor = null;
		jogadoresVencedores.forEach(_jogador=>{
			console.log(` - ${_jogador.nome}: ${_jogador.cartaAtual().obterCodCarta()}`);
			if ((vencedor == null)
				|| (_jogador.cartaAtual().classe < vencedor.cartaAtual().classe)
				|| (
					(_jogador.cartaAtual().classe == vencedor.cartaAtual().classe)
					&& (_jogador.cartaAtual().numero < vencedor.cartaAtual().numero)
				)
			) {
				vencedor = _jogador;
			}
		});
		jogadoresVencedores = [vencedor];
	}
	console.log(`Vencedor: ${jogadoresVencedores[0].nome}, com a seguinte carta:`);
	jogadoresVencedores[0].cartaAtual().info();
	let cartaVencedora = jogadoresVencedores[0].cartaAtual();
	setTimeout(()=>{
		elementosRodada.forEach(_elementoRodada=>{
			if (_elementoRodada.jogador == jogadoresVencedores[0]) {
				_elementoRodada.destacar();
			}
		});
		divAmbiente.appendChild(cartaVencedora.desenhar());
		divElementosRodada.style.bottom = "20px";
		divElementosRodada.style.marginBottom = "0em";
		for (let i = 0; i < jogadores.length; i++) {
			if (jogadores[i] == jogadoresVencedores[0]) {
				destacarJogador(i);
				break;
			}
		}
	},tempoExibirCartaVencedor);
	setTimeout(zerarElementosRodada,tempoExecucao);

	//Enviar as cartas dos perdedores para o vencedor
	let numJogadoresAtivos = 0;
	jogadores.forEach(_jogador=>{
		if (!_jogador.ativo) {
			return;
		}
		numJogadoresAtivos++;
		if (_jogador !== jogadoresVencedores[0]) {
			jogadoresVencedores[0].adicionarCarta(_jogador.removerCartaAtual());
			if (_jogador.cartas.length == 0) {
				_jogador.perder();
				console.log(`Jogador ${_jogador.nome} eliminado!`);
				numJogadoresAtivos--;
			}
		}
	})
	jogadoresVencedores[0].adicionarCarta(jogadoresVencedores[0].removerCartaAtual());
	
	if (numJogadoresAtivos == 1) {
		console.log(`Jogador ${jogadoresVencedores[0].nome} venceu a partida!`);
	} else {
		setTimeout(()=>{
			exibirCartasJogadores();
			if (jogadores[idJogador].ativo) {
				exibirCarta(jogadores[idJogador].cartaAtual().id);
				rodada();
			} else {
				jogadores[idJogador].perder();
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

function executarJogador(_jogadorCPU) {
	//No caso do jogo SP, essa função é chamada só pra CPU então, vamos escolher um atributo aleatório e #xablau
	atributoEscolhido = Math.floor(Math.random()*deque.atributos.length);
	console.log(`Atributo escolhido: ${deque.atributos[atributoEscolhido].nome}`);
	//1,5 segundos pra exibir os atributos escolhidos
	setTimeout(()=>{
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
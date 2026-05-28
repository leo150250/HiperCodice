var jogadorDaVez = -1;
var emExecucao = false;
var encerrada = false;
var timerProntidao = -1;
var idJogador = 0;

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
	jogadorDaVez = _idProximoJogador % jogadores.length;
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
	let jogadorAtual = destacarJogador(jogadorDaVez+1);
	console.log(`É a vez de ${jogadorAtual.nome}!\n`);
	console.log(`Carta atual:`);
	jogadorAtual.cartaAtual().info(true);
	atributoEscolhido = -1;
	if (!jogadorAtual.cpu) {
		minhaVez();
	} else {
		vezDeJogador(jogadorAtual.nome);
		//atributoEscolhido = Math.floor(Math.random() * (deque.atributos.length - 1));
	}
	//girarRodada(atributoEscolhido);
}

function executarEscolha() {
	//Comportamento de jogo Single-Player
	//Faz cada CPU exibir os valores dos atributos de suas cartas atuais depois de algum tempo de espera
	jogadores.forEach(_jogador=>{
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
	console.log("Valores dos jogadores:");
	jogadores.forEach(_jogador=>{
		if (!_jogador.ativo) {
			continue;
		}
		console.log(` - ${_jogador.nome}: ${_jogador.cartaAtual().valores[atributoEscolhido]} (${_jogador.cartaAtual().obterCodCarta()}) ${_jogador.cartaAtual().especial?" [Especial]":""}`);
		if (_jogador.cartaAtual().especial) {
			jogadorEspecial = _jogador;
		}
	})
	let jogadoresVencedores = [];
	if (jogadorEspecial != null) {
		console.log(`Jogador ${jogadorEspecial.nome} tem a carta especial...`);
		let jogadoresClasse1 = []
		jogadores.forEach(_jogador=>{
			if ((_jogador !== jogadorEspecial)
			&& (_jogador.cartaAtual().classe == 1)) {
				jogadoresClasse1.push(_jogador);
			}
		});
		if (jogadoresClasse1.length > 0) {
			let nomesVencedores = "";
			jogadoresClasse1.forEach(_jogador=>{
				nomesVencedores += " " + _jogador.nome;
				jogadoresVencedores.push(_jogador);
			})
			console.log("...mas os jogadores com carta de classe 1 vencem:" + nomesVencedores);
		} else {
			console.log("...e vence a rodada!");
			jogadoresVencedores.push(jogadorEspecial);
		}
	}
}
const divAmbiente = document.getElementById("ambiente");
const divCartaJogador = document.getElementById("cartaJogador");
const divInterfaceJogador = document.getElementById("interfaceJogador");
const divElementosRodada = document.getElementById("elementosRodada");

const spanResultadoPartida = document.getElementById("spanResultadoPartida");
const spanResultadoDeque = document.getElementById("spanResultadoDeque");
const spanResultadoRodadas = document.getElementById("spanResultadoRodadas");
const spanResultadoTempo = document.getElementById("spanResultadoTempo");

const divMensagemJogador = document.getElementById("mensagemJogador");
const divNumCartasJogador = document.getElementById("numCartasJogador");
const divNumJogadores = document.getElementById("numJogadores");
const divTimer = document.getElementById("timer");
const divFxDifCartasJogador = document.getElementById("fxDifCartasJogador");

var vezDeJogar = false;
var atributoEscolhido = -1;
var idJogador = 0;
var numRodadas = 0;
var cartaJogadorDesenhada = null;
var jogadorDaVez = -1;
var emExecucao = false;
var encerrada = false;
var timerProntidao = -1;
var dataHoraInicio = null;

var elementosRodada = [];
class ElementoRodada {
	constructor(_carta,_jogador) {
		this.carta = _carta;
		this.jogador = _jogador;
		this.elemento = document.createElement("div");
		this.elemento.classList.add("elementosRodada");
		this.elemento.textContent = this.jogador.nome + ": " + this.carta.obterTextoAtributo(atributoEscolhido);
		this.elemento.style.left = (divAmbiente.offsetWidth * (this.jogador.posXPadrao / 100)) - (divAmbiente.offsetWidth / 2) + "px";
		this.elemento.style.top = (divAmbiente.offsetHeight * (this.jogador.posYPadrao / 100)) - (divAmbiente.offsetHeight / 2) + "px";

		if (this.jogador == jogadores[idJogador]) {
			this.elemento.classList.add("jogador");
		}

		elementosRodada.push(this);
		divElementosRodada.appendChild(this.elemento);
		divElementosRodada.style.marginBottom = (-elementosRodada.length) + "em";
		executarSom("menu.wav");
		setTimeout(()=>{
			this.elemento.style.left = "0px";
			this.elemento.style.top = "0px";
		},100);
	}
	destacar() {
		this.elemento.classList.add("destaque");
		if (this.carta.especial) {
			this.elemento.classList.add("hiperCodice");
		}
	}
}

function defineFundo(_deck) {
	let corSelecionada = Math.floor(Math.random()*cores.length);
	divAmbiente.style.backgroundImage = `radial-gradient(${cores[corSelecionada][1]}, ${cores[corSelecionada][2]}), url("img/decks/${_deck}/default.jpg")`;
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
		console.log("Desativando jogador #"+jogadorDaVez);
		jogadores[jogadorDaVez].restaurar();
	}
	jogadorDaVez = _idProximoJogador;
	console.log("Ativando jogador #"+jogadorDaVez);
	jogadores[jogadorDaVez].destacar();
	console.log(jogadores[jogadorDaVez]);
	return jogadores[jogadorDaVez];
}

function exibirCarta(_id) {
	divCartaJogador.innerHTML="";
	if (typeof _id != "object") {
		for (let i = 0; i < deque.cartas.length; i++) {
			if (deque.cartas[i].id == _id) {
				_id = deque.cartas[i];
				break;
			}
		}
	}
	let cartaDesenhada = _id.desenhar(true);
	divCartaJogador.appendChild(cartaDesenhada);
	executarSom("gameStart.wav");
	return cartaDesenhada;
}

function minhaVez() {
	console.log("É A SUA VEZ DE JOGAR!");
	vezDeJogar = true;
	setTimeout(()=>{
		let divAtributos = document.getElementById("atributos");
		divAtributos.classList.add("selecionar");
	},100);
	atributoEscolhido = -1;
}

function vezDeJogador(_jogador) {
	console.log(`É A VEZ DE ${_jogador.nome} JOGAR`);
	vezDeJogar = false;
	executarJogador(_jogador);
}

function escolherAtributo(_id) {
	if (vezDeJogar && atributoEscolhido == -1) {
		console.log(`Atributo escolhido: ${deque.atributos[_id].nome}`);
		executarSom("attrib.wav");
		atributoEscolhido = _id;
		destacarAtributo(_id);
		executarEscolha(_id);
		exibirElementosRodada();
		let divAtributos = document.getElementById("atributos");
		divAtributos.classList.remove("selecionar");
		new ElementoRodada(jogadores[idJogador].cartaAtual(),jogadores[idJogador]);
	}
}

function destacarAtributo(_id) {
	let divAtributo = document.getElementById(`atributo${_id}`);
	divAtributo.classList.add("selecionado");
}

function zerarElementosRodada() {
	divElementosRodada.style.width = "0";
	divElementosRodada.style.height = "0";
	divElementosRodada.style.opacity = "0";
	divElementosRodada.style.bottom = null;
	divElementosRodada.style.marginBottom = null;
	setTimeout(()=>{
		elementosRodada.forEach(_elementoRodada=>{
			_elementoRodada.elemento.remove();
		});
		elementosRodada = [];
		tituloAtributoEscolhido.textContent = "";
	},1000);
}

function exibirElementosRodada() {
	tituloAtributoEscolhido.textContent = deque.atributos[atributoEscolhido].nome;
	divElementosRodada.style.width = "auto";
	divElementosRodada.style.height = "auto";
	divElementosRodada.style.opacity = "1";
}

function obterTempoDaPartida() {
	let difMs = Math.abs(new Date() - dataHoraInicio);
	let msSeg = 1000;
	let msMin = msSeg * 60;
	let msHor = msMin * 60;
	let horas = Math.floor(difMs / msHor);
	let minutos = Math.floor((difMs % msHor) / msMin);
	let segundos = Math.floor((difMs % msMin) / msSeg);
	return `${horas.toString()}:${minutos.toString().padStart(2,"0")}:${segundos.toString().padStart(2,"0")}`;
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
			let cartaAmbiente = jogadorEspecial.cartaAtual().desenhar();
			setTimeout(()=>{
				cartaAmbiente.remove();
			},7000);
			divAmbiente.appendChild(cartaAmbiente);
			divElementosRodada.style.bottom = "20px";
			divElementosRodada.style.marginBottom = "0em";
			divMensagemJogador.textContent = `${jogadorEspecial.nome} tem o HÍPER-CODICE!`;
			console.log("...mas os jogadores com carta de classe A vencem:" + nomesVencedores);
			tempoExibirCartaVencedor += 2000;
		} else {
			divMensagemJogador.textContent = `${jogadorEspecial.nome} tem o HÍPER-CODICE!`;
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
	let porEmpate = false;
	if (jogadoresVencedores.length > 1) { //Se houver mais de um vencedor, é um empate
		porEmpate = true;
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
		if (jogadorEspecial !== null) {
			if (jogadorEspecial !== jogadoresVencedores[0]) {
				divMensagemJogador.textContent = `...mas ${jogadoresVencedores[0].nome} tem uma carta classe A, e vence${porEmpate?" por empate!":"!"}`;
			} else {
				divMensagemJogador.textContent = `${jogadoresVencedores[0].nome} tem o HÍPER-CODICE!`;
			}
		} else {
			divMensagemJogador.textContent = `${jogadoresVencedores[0].nome} venceu${porEmpate?" por empate!":"!"}`;
		}
		elementosRodada.forEach(_elementoRodada=>{
			if (_elementoRodada.jogador == jogadoresVencedores[0]) {
				_elementoRodada.destacar();
			}
		});
		let cartaAmbienteVencedora = cartaVencedora.desenhar();
		setTimeout(()=>{
			cartaAmbienteVencedora.remove();
		},7000);
		divAmbiente.appendChild(cartaAmbienteVencedora);
		divElementosRodada.style.bottom = "20px";
		divElementosRodada.style.marginBottom = "0em";
		for (let i = 0; i < jogadores.length; i++) {
			if (jogadores[i] == jogadoresVencedores[0]) {
				destacarJogador(i);
				break;
			}
		}
		divNumCartasJogador.textContent = `🃏${jogadores[idJogador].cartas.length}`;
		if (jogadoresVencedores[0] == jogadores[idJogador]) {
			cartaJogadorDesenhada.classList.add("venceu");
		} else {
			executarSom("cardRem.wav");
			cartaJogadorDesenhada.classList.add("perdeu");
		}
	},tempoExibirCartaVencedor);
	setTimeout(zerarElementosRodada,tempoExecucao);
	
	verificarVencedor(jogadoresVencedores[0],tempoExecucao);
}
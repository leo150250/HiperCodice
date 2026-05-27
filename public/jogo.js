const divCartaJogador = document.getElementById("cartaJogador");
const divInterfaceJogador = document.getElementById("interfaceJogador");
const divElementosRodada = document.getElementById("elementosRodada");

var vezDeJogar = false;
var atributoEscolhido = -1;

var elementosRodada = [];
class ElementoRodada {
	constructor(_carta,_jogador) {
		this.carta = _carta;
		this.jogador = _jogador;
		this.elemento = document.createElement("div");
		this.elemento.classList.add("elementosRodada");

		this.elemento.textContent = `${this.carta.obterCodCarta()} | ${this.carta.valores[atributoEscolhido]}`;

		elementosRodada.push(this);
		divElementosRodada.appendChild(this.elemento);
	}
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
	divCartaJogador.appendChild(_id.desenhar());
}

function minhaVez() {
	console.log("É A SUA VEZ DE JOGAR!");
	vezDeJogar = true;
	let divAtributos = document.getElementById("atributos");
	divAtributos.classList.add("selecionar");
	atributoEscolhido = -1;
}

function vezDeJogador(_jogador) {
	console.log(`É A VEZ DE ${_jogador} JOGAR`);
	vezDeJogar = false;
}

function escolherAtributo(_id) {
	if (vezDeJogar && atributoEscolhido == -1) {
		console.log(`Atributo escolhido: ${deque.atributos[_id].nome}`);

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
	divElementosRodada.style.height = "0";
	setTimeout(()=>{
		divElementosRodada.style.opacity = "0";
		tituloAtributoEscolhido.textContent = "";
	},1000);
}

function exibirElementosRodada() {
	tituloAtributoEscolhido.textContent = deque.atributos[atributoEscolhido].nome;
	divElementosRodada.style.height = "auto";
	divElementosRodada.style.opacity = "1";
}
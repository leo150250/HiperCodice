const divCartaJogador = document.getElementById("cartaJogador");
const divInterfaceJogador = document.getElementById("interfaceJogador");

var vezDeJogar = false;
var atributoEscolhido = -1;

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
		let divAtributos = document.getElementById("atributos");
		divAtributos.classList.remove("selecionar");
	}
}

function destacarAtributo(_id) {
	let divAtributo = document.getElementById(`atributo${_id}`);
	divAtributo.classList.add("selecionado");
}
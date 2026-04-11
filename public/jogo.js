const divCartaJogador = document.getElementById("cartaJogador");

var vezDeJogar = false;

function exibirCarta(_id) {
	divCartaJogador.innerHTML="";
	divCartaJogador.appendChild(deque.cartas[_id].desenhar());
}

function minhaVez() {
	console.log("É A SUA VEZ DE JOGAR!");
	vezDeJogar = true;
	let divAtributos = document.getElementById("atributos");
	divAtributos.classList.add("selecionar");
}

function vezDeJogador(_jogador) {
	console.log(`É A VEZ DE ${_jogador} JOGAR`);
	vezDeJogar = false;
}

function escolherAtributo(_id) {
	if (vezDeJogar) {
		console.log(`Atributo escolhido: ${deque.atributos[_id].nome}`);
		destacarAtributo(_id);
		enviarEscolha(_id);
	}
}

function destacarAtributo(_id) {
	let divAtributo = document.getElementById(`atributo${_id}`);
	divAtributo.classList.add("selecionado");
}
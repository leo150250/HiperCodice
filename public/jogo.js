const divCartaJogador = document.getElementById("cartaJogador");

function exibirCarta(_id) {
	divCartaJogador.innerHTML="";
	divCartaJogador.appendChild(deque.cartas[_id].desenhar());
}
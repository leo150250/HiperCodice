const divAmbiente = document.getElementById("ambiente");

function defineFundo(_deck) {
	let corSelecionada = Math.floor(Math.random()*cores.length);
	divAmbiente.style.background = `radial-gradient(${cores[corSelecionada][1]}, ${cores[corSelecionada][2]})`;
}

function iniciarJogoSP() {
	esconderDialogo("carregando");
	divMenu.style.display="none";
	defineFundo(1);
	criarNovoJogador("Leandro",false);
	criarNovoJogador("Andréia");
	criarNovoJogador("Ana");
	criarNovoJogador("Elizabeth");
}

function posicionarJogadorPadrao(_jogador) {

}

function posicionarJogadores() {
	let angulo = 270;
	let diferencaAngulo = 360 / jogadores.length;
	jogadores.forEach(_jogador=>{
		let posX = 50;
		let posY = 50;
		posX += Math.cos(angulo * (Math.PI / 180)) * 25;
		posY -= Math.sin(angulo * (Math.PI / 180)) * 35;
		_jogador.elemento.style.top = posY + "%";
		_jogador.elemento.style.left = posX + "%";
		angulo+=diferencaAngulo;
	});
}

function criarNovoJogador(_nome,_cpu=true) {
	let novoJogador = new Jogador(_nome,_cpu);
	divAmbiente.appendChild(novoJogador.elemento);
	posicionarJogadores();
	return novoJogador;
}
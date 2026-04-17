const divMenu = document.getElementById("menu");
const divMenuInicio = document.getElementById("menuInicio");
const labelConfigSom = document.getElementById("labelConfigSom");
const labelConfigMusica = document.getElementById("labelConfigMusica");
const inputConfigSom = document.getElementById("inputConfigSom");
const inputConfigMusica = document.getElementById("inputConfigMusica");

var menuAberto = null;
var configSom = true;
var configMusica = true;
inputConfigSom.checked = configSom;
inputConfigMusica.checked = configMusica;
labelConfigSom.textContent = "Sons: "+(inputConfigSom.checked?"SIM":"NÃO");
labelConfigMusica.textContent = "Música: "+(inputConfigMusica.checked?"SIM":"NÃO");

function abrirMenu(_menu) {
	if (menuAberto!=null) {
		menuAberto.classList.remove("aberto");
		menuAberto = null;
	}
	menuAberto = document.getElementById("menu"+_menu);
	menuAberto.classList.add("aberto");
}
function alternarConfigSom(_valor) {
	configSom = _valor;
	labelConfigSom.textContent = "Sons: "+(configSom?"SIM":"NÃO");
}
function alternarConfigMusica(_valor) {
	configMusica = _valor;
	labelConfigMusica.textContent = "Música: "+(configMusica?"SIM":"NÃO");
}
function exibirDialogo(_id) {
	let dialogo = document.getElementById(_id);
	dialogo.showModal();
}
function esconderDialogo(_id) {
	let dialogo = document.getElementById(_id);
	dialogo.close();
}

//alternarConfigSom(true);
//alternarConfigMusica(true);
setTimeout(()=>{
	abrirMenu("Inicio");
},500);
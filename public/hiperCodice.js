const divMenu = document.getElementById("menu");
const divMenuInicio = document.getElementById("menuInicio");
const divMenuFundo = document.getElementById("menuFundo");
const labelConfigSom = document.getElementById("labelConfigSom");
const labelConfigMusica = document.getElementById("labelConfigMusica");
const inputConfigSom = document.getElementById("inputConfigSom");
const inputConfigMusica = document.getElementById("inputConfigMusica");

var cores = [
	["#F44336","#8b0000","#4f0000"],
	["#FF9800","#4f2d00","#211300"],
	["#4CAF50","#004700","#002500"],
	["#2196F3","#002c8b","#00204f"]
];
var menuAberto = null;
var configSom = true;
var configMusica = true;
inputConfigSom.checked = configSom;
inputConfigMusica.checked = configMusica;
labelConfigSom.textContent = "Sons: "+(inputConfigSom.checked?"SIM":"NÃO");
labelConfigMusica.textContent = "Música: "+(inputConfigMusica.checked?"SIM":"NÃO");
var imagensMenu = [];
var indiceImagemMenu = 0;
var atualizacaoImagemMenu = null;

function abrirMenu(_menu) {
	fecharMenu();
	menuAberto = document.getElementById("menu"+_menu);
	menuAberto.classList.add("aberto");
}
function fecharMenu() {
	if (menuAberto!=null) {
		menuAberto.classList.remove("aberto");
		menuAberto = null;
	}
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
function carregarImagensMenu() {
	fetch('getFundo.php')
		.then(response => response.json())
		.then(data => {
			imagensMenu = [];
			indiceImagemMenu = 0;
			for (let i = 0; i < data.length; i++) {
				let divImagem = document.createElement("div");
				divImagem.classList.add("imagemFundo");
				let imagemFundo = document.createElement("img");
				imagemFundo.src = "img/decks/"+data[i].idDeque+"/"+data[i].idCarta+".jpg";
				let imagemTexto = document.createElement("p");
				imagemTexto.innerHTML = data[i].nomeCarta+"<br>Deque \""+data[i].nomeDeque+"\"";
				divImagem.appendChild(imagemFundo);
				divImagem.appendChild(imagemTexto);
				imagensMenu.push(divImagem);
			}
			divMenuFundo.appendChild(imagensMenu[indiceImagemMenu]);
			atualizacaoImagemMenu = setInterval(()=>{
				imagensMenu[indiceImagemMenu].remove();
				indiceImagemMenu++;
				if (indiceImagemMenu==imagensMenu.length) {
					clearInterval(atualizacaoImagemMenu);
					carregarImagensMenu();
				} else {
					divMenuFundo.appendChild(imagensMenu[indiceImagemMenu]);
				}
			},10000);
		})
		.catch(error => console.error('Erro ao carregar imagens do menu:', error));
}
function carregarJogoSP() {
	fecharMenu();
	exibirDialogo("carregando");
    let novoScript = document.createElement("script");
    novoScript.src = "jogoSP.js";
    novoScript.onload = function() {
        // Aqui você pode chamar uma função do script carregado, por exemplo:
        console.log("Script jogoSP.js carregado com sucesso.");
		iniciarJogoSP();
    };
    novoScript.onerror = function() {
        console.error("Erro ao carregar o script jogoSP.js.");
    };
    document.body.appendChild(novoScript);
}
function paginaCarregada() {
	setTimeout(()=>{
		abrirMenu("Inicio");
	},2000);
	carregarJogoSP();
}

//Execução
//carregarImagensMenu();
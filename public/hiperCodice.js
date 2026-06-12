const divMenu = document.getElementById("menu");
const divMenuInicio = document.getElementById("menuInicio");
const divMenuFundo = document.getElementById("menuFundo");
const labelConfigSom = document.getElementById("labelConfigSom");
const labelConfigMusica = document.getElementById("labelConfigMusica");
const inputConfigSom = document.getElementById("inputConfigSom");
const inputConfigMusica = document.getElementById("inputConfigMusica");
const inputDequesPesquisa = document.getElementById("inputDequesPesquisa");
const divListagemDequesPesquisa = document.getElementById("listagemDequesPesquisa");
const divListagemAtributosDeque = document.getElementById("listagemAtributosDeque");
const inputNumCPUsSPPers = document.getElementById("inputNumCPUsSPPers");
const buttonIniciarSPPersonalizado = document.getElementById("buttonIniciarSPPersonalizado");
const divJogo = document.getElementById("jogo");
const pMensagemDialogMsg = document.getElementById("mensagemDialogMsg");

var cores = [
	["#F44336","#8b0000f8","#4f0000f8"],
	["#FF9800","#4f2d00f8","#211300f8"],
	["#4CAF50","#004700f8","#002500f8"],
	["#2196F3","#002c8bf8","#00204ff8"]
];
var nomesAleatorios = [
	"Gato","Cão","Rã","Tatu","Lobo","Urso","Leão","Tigre","Puma","Boi",
	"Vaca","Porco","Veado","Raposa","Foca","Baleia","Cobra","Corvo","Pato","Galo",
	"Ema","Puma","Mico","Zebu","Anta","Onça","Guaxinim","Coala","Cabra","Ovelha"
];
var menuAberto = null;
var timerDequePesquisa = null;
var divDequeSelecionado = null;
var dequeSelecionadoSPPers = 0;
var atributosSelecionados = [];

var sons = [];
var indSons = {};
var musicas = [];
var musicaEmExecucao = null;
var configSom = true;
var configMusica = true;
var configNome = "";

var cookies = decodeURIComponent(document.cookie).split(";");
cookies.forEach(_cookie=>{
	let cookieAtual = _cookie.split("=");
	cookieAtual[0] = cookieAtual[0].trim();
	console.log(cookieAtual);
	switch (cookieAtual[0]) {
		case "configSom": {
			if (parseInt(cookieAtual[1])==1) {
				configSom = true;
			} else {
				configSom = false;
			}
		} break;
		case "configMusica": {
			if (parseInt(cookieAtual[1])==1) {
				configMusica = true;
			} else {
				configMusica = false;
			}
		} break;
		case "configNome": {
			configNome = cookieAtual[1];
		}
	}
});

inputConfigSom.checked = configSom;
inputConfigMusica.checked = configMusica;
labelConfigSom.textContent = "Sons: "+(inputConfigSom.checked?"SIM":"NÃO");
labelConfigMusica.textContent = "Música: "+(inputConfigMusica.checked?"SIM":"NÃO");
document.cookie = `configSom=${configSom?1:0}`;
document.cookie = `configMusica=${configMusica?1:0}`;
if (configNome=="") {
	configNome = nomesAleatorios[Math.floor(Math.random()*(nomesAleatorios.length-1))] + "#" + Math.floor(Math.random()*999);
	console.log(`Olá, ${configNome}!`);
}
//document.cookie = `configNome=${encodeURIComponent(configNome)}`;

class Som {
	constructor(_arquivo,_musica=false) {
		this.arquivo = _arquivo;
		this.musica = _musica;
		sons.push(this);
		indSons[this.arquivo] = this;

		this.elemento = document.createElement("audio");
		this.elemento.preload = "auto";

		if (this.musica) {
			musicas.push(this);
			this.elemento.loop = true;
		}
		
		this.elementoSrc = document.createElement("source");
		this.elementoSrc.src = "sfx/" + this.arquivo;
		switch (this.arquivo.slice(this.arquivo.lastIndexOf(".") + 1)) {
			case "wav": this.elementoSrc.type = "audio/wav";
			case "mp3": this.elementoSrc.type = "audio/mpeg";
			case "ogg": this.elementoSrc.type = "audio/ogg";
		}
		this.elemento.appendChild(this.elementoSrc);
		
		document.body.appendChild(this.elemento);
		console.log(`Som ${this.arquivo} carregado`);
	}
	executar() {
		if (configSom && !this.musica) {
			this.elemento.currentTime = 0;
			this.elemento.play();
		}
		if (configMusica && this.musica) {
			if (musicaEmExecucao != null) {
				musicaEmExecucao.parar();
			}
			this.elemento.currentTime = 0;
			this.elemento.play();
			musicaEmExecucao = this;
		}
	}
	parar() {
		if (!this.elemento.paused) {
			this.elemento.pause();
			if (this.musica && musicaEmExecucao == this) {
				musicaEmExecucao = null;
			}
		}
	}
}

var imagensMenu = [];
var indiceImagemMenu = 0;
var atualizacaoImagemMenu = null;

function abrirMenu(_menu) {
	fecharMenu();
	menuAberto = document.getElementById("menu"+_menu);
	menuAberto.classList.add("aberto");
	executarSom('menu.wav');
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
	if (configSom) {
		executarSom("attrib.wav");
	}
	document.cookie = `configSom=${configSom?1:0}`;
}
function alternarConfigMusica(_valor) {
	configMusica = _valor;
	labelConfigMusica.textContent = "Música: "+(configMusica?"SIM":"NÃO");
	if (!configMusica && musicaEmExecucao != null) {
		musicaEmExecucao.parar();
	} else if (configMusica && musicaEmExecucao == null) {
		executarMusicaAleatoria();
	}
	if (!configMusica) {
		musicaEmExecucao = null;
	}
	document.cookie = `configMusica=${configMusica?1:0}`;
}
function exibirDialogo(_id) {
	let dialogo = document.getElementById(_id);
	executarSom('attrib.wav');
	dialogo.showModal();
}
function esconderDialogo(_id) {
	let dialogo = document.getElementById(_id);
	dialogo.close();
}
function esconderTodosDialogos() {
	let dialogos = document.getElementsByTagName("dialog");
	for (let i = 0; i < dialogos.length; i++) {
		if (dialogos[i].open) {
			dialogos[i].close();
		}
	}
}
function exibirMensagem(_mensagem) {
	esconderTodosDialogos();
	pMensagemDialogMsg.textContent = _mensagem;
	exibirDialogo("dialogMsg");
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
				imagemFundo.src = "img/img.php?d="+data[i].idDeque+"&c="+data[i].idCarta+"&f";
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
function carregarScript(_script,_callback=()=>{}) {
	let novoScript = document.createElement("script");
    novoScript.src = _script+".js";
    novoScript.onload = function() {
        // Aqui você pode chamar uma função do script carregado, por exemplo:
        console.log(`Script ${novoScript.src} carregado com sucesso.`);
		_callback();
    };
    novoScript.onerror = function() {
        console.error(`Erro ao carregar o script ${novoScript.src}`);
    };
    document.body.appendChild(novoScript);
}
function carregarJogoSP(_personalizado = false) {
	fecharMenu();
	exibirDialogo("carregando");
	carregarScript("jogo",()=>{
		carregarScript("jogoSP",()=>{
			divJogo.style.display=null;
			clearInterval(atualizacaoImagemMenu);
			if (_personalizado) {
				iniciarJogoSP(inputNumCPUsSPPers.value,dequeSelecionadoSPPers,atributosSelecionados);
			} else {
				iniciarJogoSP();
			}
		});
	});
}
function carregarJogoMP() {
	fecharMenu();
	exibirDialogo("carregando");
	carregarScript("jogo",()=>{
		carregarScript("jogoMP",()=>{
			divJogo.style.display=null;
			clearInterval(atualizacaoImagemMenu);
			iniciarJogoMP("127.0.0.1",15000);
		});
	});
}
function paginaCarregada() {
	executarMusicaAleatoria();
	setTimeout(()=>{
		abrirMenu("Inicio");
	},2000);
	carregarJogoMP();
}
function reiniciarJogo() {
	document.location.reload();
}
function executarSom(_nomeSom) {
	indSons[_nomeSom].executar();
}
function executarMusicaAleatoria() {
	//console.log(musicas);
	let musicasParaExecutar = [...musicas];
	if (musicaEmExecucao != null) {
		//console.log("Tem música executando...");
		for (let i = 0; i < musicasParaExecutar.length; i++) {
			if (musicaEmExecucao == musicasParaExecutar[i]) {
				musicasParaExecutar.splice(i,1);
				break;
			}
		}
	}
	//console.log(musicasParaExecutar);
	//console.log(musicas);
	let idMusicaAleatoria = Math.floor(Math.random()*musicasParaExecutar.length);
	musicasParaExecutar[idMusicaAleatoria].executar();
}
function gerarLoader(_elemento = null) {
	let novoLoader = document.createElement("div");
	novoLoader.classList.add("loader");
	novoLoader.textContent="HC";
	if (_elemento != null) {
		_elemento.appendChild(novoLoader);
	}
	return novoLoader;
}
function listarDequesPesquisa(_espera = true) {
	let loader = null;
	if (timerDequePesquisa != null) {
		clearTimeout(timerDequePesquisa);
		loader = divListagemDequesPesquisa.getElementsByClassName("loader")[0];
	} else {
		divListagemDequesPesquisa.innerHTML = "";
		loader = gerarLoader(divListagemDequesPesquisa);
		divListagemAtributosDeque.innerHTML = "Selecione um deque";
		divDequeSelecionado = null;
		dequeSelecionadoSPPers = 0;
	}
	timerDequePesquisa = setTimeout(()=>{
		fetch('getListaDeques.php', {
			method: "POST",
			headers: { "Content-Type": "application/x-www-form-urlencoded" },
			body: new URLSearchParams({
				pesquisaDeque: inputDequesPesquisa.value,
			})
		}).then(response => response.json())
			.then(data => {
				if (loader != null) {
					loader.remove();
				}
				if (data.length == 0) {
					divListagemDequesPesquisa.textContent = "Nenhum deque encontrado. Por favor, refine a busca acima.";
				}
				for (let i = 0; i < data.length; i++) {
					let dequePesquisa = data[i];
					let novoDeque = new Deque(dequePesquisa["id"],dequePesquisa["nome"],dequePesquisa["descricao"]);
					dequePesquisa["atributos"].forEach(_atributo=>{
						let novoAtributo = new Atributo(novoDeque,_atributo["id"]);
						novoAtributo.nome = _atributo["nome"];
						novoAtributo.medida = _atributo["medida"];
						novoDeque.atributos.push(novoAtributo);
					});
					let elementoDeque = novoDeque.desenhar();
					elementoDeque.onclick = ()=>{
						if (divDequeSelecionado != elementoDeque) {
							if (divDequeSelecionado != null) {
								divDequeSelecionado.classList.remove("selecionado");
							}
							divDequeSelecionado = elementoDeque;
							dequeSelecionadoSPPers = dequePesquisa["id"];
							divDequeSelecionado.classList.add("selecionado");
							listarAtributosDeque(novoDeque);
						}
					}
					divListagemDequesPesquisa.appendChild(elementoDeque);
				}
				timerDequePesquisa = null;
			})
			.catch(error => console.error('Erro ao carregar os deques:', error));
	},_espera?1000:0);
}
function listarAtributosDeque(_deque) {
	divListagemAtributosDeque.innerHTML = "";
	atributosSelecionados = [];
	let atributos = _deque.atributos;
	for (let i = 0; i < atributos.length; i++) {
		let atributo = atributos[i];
		let divNovoAtributo = document.createElement("div");
		divNovoAtributo.classList.add("atributo");
		let inputNovoAtributo = document.createElement("input");
		inputNovoAtributo.type = "checkbox";
		inputNovoAtributo.name = inputNovoAtributo.id = `atributoPers${atributo["id"]}`;
		inputNovoAtributo.onchange = ()=>{
			inputNovoAtributo.checked?atributosSelecionados.push(atributo["id"]):atributosSelecionados.splice(atributosSelecionados.indexOf(atributo["id"]),1);
			atributosSelecionados.length>=6?buttonIniciarSPPersonalizado.removeAttribute("disabled"):buttonIniciarSPPersonalizado.setAttribute("disabled",true);
		};
		divNovoAtributo.appendChild(inputNovoAtributo);
		let labelNovoAtributo = document.createElement("label");
		labelNovoAtributo.textContent = `${atributo["nome"]} (${atributo["medida"]})`;
		labelNovoAtributo.setAttribute("for",`atributoPers${atributo["id"]}`);
		divNovoAtributo.appendChild(labelNovoAtributo);
		divListagemAtributosDeque.appendChild(divNovoAtributo);
	}
}

//Execução
carregarImagensMenu();

new Som("attrib.wav");
new Som("cardAdd.wav");
new Som("cardRem.wav");
new Som("gameStart.wav");
new Som("hover.wav");
new Som("menu.wav");
new Som("Ether Disco.mp3",true);
new Som("Inspired.mp3",true);
new Som("Rising Tide.mp3",true);

divJogo.style.display="none";
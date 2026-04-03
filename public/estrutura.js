var deque = null;

class Deque {
	constructor(_id,_nome) {
		this.id = _id;
		this.nome = _nome;
		this.atributos = [];
		this.cartas = [];
	}
	info() {
		console.log(`Deque: ${this.nome} (ID ${this.id})`);
		console.log("Atributos:");
		this.atributos.forEach(atributo => {
			atributo.info();
		});
		console.log("Cartas:");
		this.cartas.forEach(carta => {
			carta.info();
		});
	}
	json() {
		return JSON.stringify(this);
	}
	organizarDeque() {
		//Organiza as cartas por classe e número, mantendo tudo 1-1, 1-2...1-8,2-1,2-2... e assim por diante
		this.cartas.sort((a, b) => {
			if (a.classe === b.classe) {
				return a.numero - b.numero;
			}
			return a.classe - b.classe;
		});
	}
}

class Atributo {
	constructor(_deque, _id) {
		this.deque = _deque;
		this.id = _id;
		this.nome = `Atributo ${this.id}`;
		this.medida = "un";
		this.forma = 1; // 1 = Maior vence, -1 = Menor vence
	}
	info() {
		console.log(`- ${this.nome} (#${this.id} - Medida: ${this.medida}, Forma: ${(this.forma == 1 ? "Maior vence" : "Menor vence")})`);
	}
	json() {
		return JSON.stringify(this);
	}
}

class Carta {
	constructor(_deque, _id, _classe) {
		this.deque = _deque;
		this.id = _id;
		this.classe = _classe;
		this.nome = `Carta ${this.id}`;
		this.categoria = "Testador";
		this.valores = [];
		this.numero = 0;
		this.especial = false;
	}
	obterCodCarta() {
		let texto = "";
		switch (this.classe) {
			case 1: texto = "A"; break;
			case 2: texto = "B"; break;
			case 3: texto = "C"; break;
			case 4: texto = "D"; break;
		}
		if (this.numero > 0) {
			texto += this.numero;
		}
		return texto;
	}
	desenhar() {
		let el = document.createElement("div");
		el.classList.add("Carta",`classe${this.classe}`);
		let elFundo = document.createElement("img");
		elFundo.classList.add("fundo");
		elFundo.src = `img/decks/${this.deque.id}/default.jpg`;
		elFundo.alt = "Fundo da Carta";
		el.appendChild(elFundo);

		let elCod = document.createElement("h1");
		elCod.textContent = this.obterCodCarta();
		el.appendChild(elCod);

		let elNome = document.createElement("h2");
		elNome.textContent = this.nome;
		el.appendChild(elNome);

		let elCategoria = document.createElement("h3");
		elCategoria.textContent = this.categoria;
		el.appendChild(elCategoria);

		let elImg = document.createElement("img");
		elImg.classList.add("imagem");
		elImg.src = `img/decks/${this.deque.id}/${this.id}.jpg`;
		elImg.alt = this.nome;
		el.appendChild(elImg);

		el.appendChild(document.createElement("br"));

		let elValores = document.createElement("div");
		elValores.classList.add("valoresCarta");
		for (let i = 0; i < this.deque.atributos.length; i++) {
			let atributo = this.deque.atributos[i];
			let valor = this.valores[i];
			let inverterMedida = false;
			if (atributo.medida.includes("$")) {
				inverterMedida = true;
				valor = valor.toLocaleString("pt-BR", {
					minimumFractionDigits: 2,
					maximumFractionDigits: 2
				});
			}
			let textoValor = "";
			if (inverterMedida) {
				textoValor = `${atributo.medida} ${valor}`;
			} else {
				textoValor = `${valor} ${atributo.medida}`;
			}
			let elDivCampo = document.createElement("div");
			let elDivNomeCampo = document.createElement("div");
			elDivNomeCampo.textContent = atributo.nome;
			let elDivValorCampo = document.createElement("div");
			elDivValorCampo.textContent = textoValor;
			elDivCampo.appendChild(elDivNomeCampo);
			elDivCampo.appendChild(elDivValorCampo);
			elValores.appendChild(elDivCampo);
		}
		el.appendChild(elValores);

		let elDesc = document.createElement("p");
		elDesc.textContent = this.descricao;
		el.appendChild(elDesc);

		return el;
	}
	info(_enumerar = false) {
		console.log(`- [${this.obterCodCarta()}] #${this.id} - ${this.nome} - ${this.categoria}`);
		if (this.especial) {
			console.log("#####===> HIPER CODICE! <=====#####");
		}
		for (let i = 0; i < this.deque.atributos.length; i++) {
			let atributo = this.deque.atributos[i];
			let valor = this.valores[i];
			console.log(`  ${_enumerar ? `[${i + 1}]` : "-"} ${atributo.nome}: ${valor} ${atributo.medida}`);
		}
	}
	json() {
		return JSON.stringify(this);
	}
}

function gerarDequeJSON(_json) {
	console.log(_json);
	
	deque = new Deque(_json.id,_json.nome);
	_json.atributos.forEach(_atributo => {
		let novoAtributo = new Atributo(deque,_atributo.id);
		novoAtributo.nome = _atributo.nome;
		novoAtributo.medida = _atributo.medida;
		novoAtributo.forma = parseInt(_atributo.forma);
		deque.atributos.push(novoAtributo);
	});
	_json.cartas.forEach(_carta => {
		let novaCarta = new Carta(deque,_carta.id,_carta.classe);
		novaCarta.categoria = _carta.categoria;
		novaCarta.descricao = _carta.descricao;
		novaCarta.especial = _carta.especial;
		novaCarta.nome = _carta.nome;
		novaCarta.valores = _carta.valores;
		deque.cartas.push(novaCarta);
	});

	//deque.info();
}
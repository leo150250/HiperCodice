create table Deques (
	id int auto_increment primary key,
	nome varchar(64) not null,
	descricao text not null
);

create table Atributos (
	id int auto_increment primary key,
	idDeque int not null,
	nome varchar(64) not null,
	forma tinyint not null default 1, #0 = melhor menor, 1 = melhor maior
	medida varchar(16) not null default "",
	descricao text not null,
	ativo boolean not null default false,
	foreign key (idDeque) references Deques(id) on delete cascade on update cascade
);

create table Cartas (
	id int auto_increment primary key,
	idDeque int not null,
	nome varchar(64) not null,
	categoria varchar(64) not null,
	classe int not null default 1,
	descricao text not null,
	foreign key (idDeque) references Deques(id) on delete cascade on update cascade
);

create table Valores (
	idCarta int not null,
	idAtributo int not null,
	valor decimal(10,2) not null default 0,
	constraint pk_valores primary key (idCarta, idAtributo),
	foreign key (idCarta) references Cartas(id) on delete cascade on update cascade,
	constraint fk_valores_atributos foreign key (idAtributo) references Atributos(id) on delete cascade on update cascade
);
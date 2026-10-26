CREATE DATABASE escritorio_de_projetos;

USE escritorio_de_projetos;

CREATE TABLE Empresas_Parceiras(
	empresa_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    CNPJ VARCHAR(45) NOT NULL,
    razão_social VARCHAR(150) NOT NULL,
    contato_nome VARCHAR(45),
    email_corporativo VARCHAR(45) NOT NULL,
    telefone VARCHAR(45) NOT NULL
);

CREATE TABLE Propostas_Externas(
	proposta_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    título VARCHAR(150) NOT NULL,
    descricão_problema TEXT NOT NULL,
    escopo LONGBLOB NOT NULL,
    status VARCHAR(45) NOT NULL DEFAULT 'Submetida',
    empresa_id INT,
    FOREIGN KEY (empresa_id) REFERENCES Empresas_Parceiras(empresa_id)
);

CREATE TABLE Convênios_Contratos(
	convênio_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    número_contrato INT NOT NULL,
    data_assinatura DATE NOT NULL,
    vigência_fim DATE NOT NULL,
    arquivo_contrato LONGBLOB NOT NULL,
    proposta_id INT,
    FOREIGN KEY (proposta_id) REFERENCES Propostas_Externas(proposta_id)
);

CREATE TABLE Documentos_Acompanhamento(
	documento_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    título VARCHAR(150) NOT NULL,
    arquivo_doc LONGBLOB NOT NULL,
    data_envio DATETIME(2) NOT NULL,
    proposta_id INT,
    FOREIGN KEY (proposta_id) REFERENCES Propostas_Externas(proposta_id)
);

CREATE TABLE Encaminhamentos_Projetos(
	encaminhamento_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    unidade_id INT NOT NULL,
    destino_cepe VARCHAR(45) NOT NULL,
    projeto_cepe_id INT NOT NULL,
    data_encaminhamento DATETIME(2) NOT NULL,
    proposta_id INT,
    FOREIGN KEY (proposta_id) REFERENCES Propostas_Externas(proposta_id)
);
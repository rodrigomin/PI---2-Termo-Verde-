CREATE DATABASE escritorio_de_projetos;

USE escritorio_de_projetos;

CREATE TABLE Empresas_Parceiras(
	empresa_id INT PRIMARY KEY AUTO_INCREMENT,
    CNPJ VARCHAR(45),
    razão_social VARCHAR(150),
    contato_nome VARCHAR(45),
    email_corporativo VARCHAR(45),
    telefone VARCHAR(45)
);

CREATE TABLE Propostas_Externas(
	proposta_id INT PRIMARY KEY AUTO_INCREMENT,
    título VARCHAR(150),
    descricão_problema TEXT,
    escopo TEXT,
    status VARCHAR(45),
    empresa_id INT,
    FOREIGN KEY (empresa_id) REFERENCES Empresas_Parceiras(empresa_id)
);

CREATE TABLE Convênios_Contratos(
	convênio_id INT PRIMARY KEY AUTO_INCREMENT,
    número_contrato INT,
    data_assinatura DATE,
    vigência_fim DATE,
    arquivo_contrato_path VARCHAR(150),
    proposta_id INT,
    FOREIGN KEY (proposta_id) REFERENCES Propostas_Externas(proposta_id)
);

CREATE TABLE Documentos_Acompanhamento(
	documento_id INT PRIMARY KEY AUTO_INCREMENT,
    título VARCHAR(150),
    arquivo_path VARCHAR(150),
    data_envio DATETIME(2),
    proposta_id INT
);

CREATE TABLE Encaminhamentos_Projetos(
	encaminhamento_id INT PRIMARY KEY AUTO_INCREMENT,
    unidade_id INT,
    destino_cepe VARCHAR(45),
    projeto_cepe_id INT,
    data_encaminhamento DATETIME(2),
    proposta_id INT,
    FOREIGN KEY (proposta_id) REFERENCES Propostas_Externas(proposta_id)
);
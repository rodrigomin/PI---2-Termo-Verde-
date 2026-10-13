-- Instruções ALTER são aplicadas somente quando a coluna estiver ausente.

ALTER TABLE `Empresas_Parceiras`
    ADD COLUMN `nome_fantasia` VARCHAR(150) NULL;

ALTER TABLE `Empresas_Parceiras`
    ADD COLUMN `descricao` TEXT NULL;

ALTER TABLE `Propostas_Externas`
    ADD COLUMN `data_limite` DATE NULL;

ALTER TABLE `Propostas_Externas`
    ADD COLUMN `data_cadastro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `Convênios_Contratos`
    ADD COLUMN `situacao` VARCHAR(45) NULL;

CREATE TABLE IF NOT EXISTS `Usuarios` (
    `usuario_id` INT PRIMARY KEY AUTO_INCREMENT,
    `empresa_id` INT NULL,
    `nome` VARCHAR(150) NOT NULL,
    `email` VARCHAR(190) NOT NULL UNIQUE,
    `telefone` VARCHAR(45) NULL,
    `senha_hash` VARCHAR(255) NOT NULL,
    `perfil` VARCHAR(30) NOT NULL DEFAULT 'representante',
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_usuario_empresa`
        FOREIGN KEY (`empresa_id`) REFERENCES `Empresas_Parceiras` (`empresa_id`)
);

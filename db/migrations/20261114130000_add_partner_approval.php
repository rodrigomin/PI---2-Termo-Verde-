<?php

use Phinx\Migration\AbstractMigration;

final class AddPartnerApproval extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
            ALTER TABLE `Empresas_Parceiras`
                ADD COLUMN `status_cadastro` VARCHAR(20)
                    NOT NULL DEFAULT 'aprovado',
                ADD COLUMN `solicitado_em` DATETIME
                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ADD COLUMN `revisado_por` INT NULL,
                ADD COLUMN `revisado_em` DATETIME NULL,
                ADD COLUMN `motivo_rejeicao` TEXT NULL,
                ADD CONSTRAINT `fk_empresa_revisado_por`
                    FOREIGN KEY (`revisado_por`)
                    REFERENCES `Usuarios` (`usuario_id`)
        ");
    }

    public function down(): void
    {
        $this->execute("
            ALTER TABLE `Empresas_Parceiras`
                DROP FOREIGN KEY `fk_empresa_revisado_por`,
                DROP COLUMN `motivo_rejeicao`,
                DROP COLUMN `revisado_em`,
                DROP COLUMN `revisado_por`,
                DROP COLUMN `solicitado_em`,
                DROP COLUMN `status_cadastro`
        ");
    }
}
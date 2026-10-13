<?php

/**
 * Cria o banco, caso não exista, e completa as tabelas/colunas ausentes.
 * Não remove nem substitui registros existentes.
 */
function ep_inicializar_banco(): bool
{
    static $inicializado = false;
    if ($inicializado) {
        return false;
    }

    $config = require __DIR__ . '/config.php';
    $nome = $config['banco'];
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $nome)) {
        throw new RuntimeException('Nome do banco de dados inválido na configuração.');
    }

    $servidor = new PDO(
        sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['host'], $config['porta']),
        $config['usuario'],
        $config['senha'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $consulta = $servidor->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?'
    );
    $consulta->execute([$nome]);
    $foiCriado = (int) $consulta->fetchColumn() === 0;

    $servidor->exec(
        'CREATE DATABASE IF NOT EXISTS `' . $nome . '` '
        . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    $servidor->exec('USE `' . $nome . '`');

    ep_executar_ddl($servidor, __DIR__ . '/../database/00_ddl_original.sql');
    ep_executar_ddl($servidor, __DIR__ . '/../database/01_complementos.sql');
    ep_verificar_estrutura($servidor);

    $inicializado = true;
    return $foiCriado;
}

function ep_coluna_existe(PDO $conexao, string $tabela, string $coluna): bool
{
    $consulta = $conexao->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS '
        . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $consulta->execute([$tabela, $coluna]);
    return (int) $consulta->fetchColumn() > 0;
}

/**
 * Executa o DDL conhecido deste projeto, sem repetir CREATE/ALTER existentes.
 * Não é um interpretador SQL genérico; os dois arquivos usam comandos simples.
 */
function ep_executar_ddl(PDO $conexao, string $arquivo): void
{
    $conteudo = file_get_contents($arquivo);
    if ($conteudo === false) {
        throw new RuntimeException('Não foi possível ler: ' . basename($arquivo));
    }

    $conteudo = preg_replace('/^\s*--[^\r\n]*$/m', '', $conteudo);
    $comandos = explode(';', $conteudo);

    foreach ($comandos as $comando) {
        $comando = trim($comando);
        if ($comando === '') {
            continue;
        }
        // Banco já selecionado, sem depender de CREATE DATABASE/USE do DDL original.
        if (preg_match('/^(CREATE\s+DATABASE|USE)\b/i', $comando)) {
            continue;
        }
        if (preg_match('/^CREATE\s+TABLE\b/i', $comando)) {
            $comando = preg_replace(
                '/^CREATE\s+TABLE\s+(?!IF\s+NOT\s+EXISTS\b)/i',
                'CREATE TABLE IF NOT EXISTS ',
                $comando
            );
        }
        if (preg_match(
            '/^ALTER\s+TABLE\s+`?([\p{L}\p{N}_]+)`?\s+ADD\s+COLUMN\s+`?([\p{L}\p{N}_]+)`?/iu',
            $comando,
            $correspondencia
        )) {
            if (ep_coluna_existe($conexao, $correspondencia[1], $correspondencia[2])) {
                continue;
            }
        }
        $conexao->exec($comando);
    }
}


/** Confirma que o DDL original e seus complementos estão acessíveis. */
function ep_verificar_estrutura(PDO $conexao): void
{
    $tabelas = [
        'Empresas_Parceiras' => ['empresa_id', 'CNPJ', 'razão_social', 'nome_fantasia', 'descricao'],
        'Propostas_Externas' => ['proposta_id', 'título', 'descricão_problema', 'escopo', 'status', 'empresa_id', 'data_limite', 'data_cadastro'],
        'Convênios_Contratos' => ['convênio_id', 'número_contrato', 'data_assinatura', 'vigência_fim', 'situacao'],
        'Documentos_Acompanhamento' => ['documento_id', 'título', 'arquivo_path', 'data_envio', 'proposta_id'],
        'Encaminhamentos_Projetos' => ['encaminhamento_id', 'proposta_id', 'data_encaminhamento'],
        'Usuarios' => ['usuario_id', 'empresa_id', 'email', 'senha_hash', 'perfil', 'ativo'],
    ];

    foreach ($tabelas as $tabela => $colunas) {
        foreach ($colunas as $coluna) {
            if (!ep_coluna_existe($conexao, $tabela, $coluna)) {
                throw new RuntimeException(
                    'Estrutura incompleta: ' . $tabela . '.' . $coluna
                );
            }
        }
    }
}

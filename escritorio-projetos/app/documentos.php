<?php
/** Consultas legadas por proposta, mantidas para compatibilidade. */
function ep_listar_convenios(int $propostaId): array
{
    $consulta = ep_db()->prepare(
        'SELECT `convênio_id`, `número_contrato`, `data_assinatura`,
                `vigência_fim`, `arquivo_contrato_path`, `situacao`
         FROM `Convênios_Contratos`
         WHERE `proposta_id` = :id ORDER BY `data_assinatura` DESC'
    );
    $consulta->execute(['id' => $propostaId]);
    return $consulta->fetchAll();
}

function ep_listar_documentos(int $propostaId): array
{
    $consulta = ep_db()->prepare(
        'SELECT `documento_id`, `título`, `arquivo_path`, `data_envio`
         FROM `Documentos_Acompanhamento`
         WHERE `proposta_id` = :id ORDER BY `data_envio` DESC'
    );
    $consulta->execute(['id' => $propostaId]);
    return $consulta->fetchAll();
}

/** Confirma que a proposta pertence ao parceiro solicitado. */
function ep_buscar_proposta_parceiro(int $empresaId, int $propostaId): ?array
{
    $consulta = ep_db()->prepare(
        'SELECT `proposta_id`, `título`
         FROM `Propostas_Externas`
         WHERE `empresa_id` = :empresa_id AND `proposta_id` = :proposta_id
         LIMIT 1'
    );
    $consulta->execute([
        'empresa_id' => $empresaId,
        'proposta_id' => $propostaId,
    ]);
    return $consulta->fetch() ?: null;
}

/** Convênios de uma empresa, opcionalmente restritos a uma proposta dela. */
function ep_listar_convenios_parceiro(int $empresaId, ?int $propostaId = null): array
{
    $sql = 'SELECT c.`convênio_id`, c.`número_contrato`, c.`data_assinatura`,
                   c.`vigência_fim`, c.`arquivo_contrato_path`, c.`situacao`
            FROM `Convênios_Contratos` c
            INNER JOIN `Propostas_Externas` p ON p.`proposta_id` = c.`proposta_id`
            WHERE p.`empresa_id` = :empresa_id';
    $parametros = ['empresa_id' => $empresaId];
    if ($propostaId !== null) {
        $sql .= ' AND p.`proposta_id` = :proposta_id';
        $parametros['proposta_id'] = $propostaId;
    }
    $sql .= ' ORDER BY c.`data_assinatura` DESC, c.`convênio_id` DESC';

    $consulta = ep_db()->prepare($sql);
    $consulta->execute($parametros);
    return $consulta->fetchAll();
}

/** Cláusula compartilhada pela contagem e paginação de acompanhamento. */
function ep_filtros_documentos_parceiro(
    int $empresaId,
    ?int $propostaId,
    string $pesquisa
): array {
    $sql = ' FROM `Documentos_Acompanhamento` d
             INNER JOIN `Propostas_Externas` p ON p.`proposta_id` = d.`proposta_id`
             WHERE p.`empresa_id` = :empresa_id';
    $parametros = ['empresa_id' => $empresaId];

    if ($propostaId !== null) {
        $sql .= ' AND p.`proposta_id` = :proposta_id';
        $parametros['proposta_id'] = $propostaId;
    }
    if ($pesquisa !== '') {
        $sql .= ' AND LOCATE(:pesquisa, COALESCE(d.`título`, \'\')) > 0';
        $parametros['pesquisa'] = $pesquisa;
    }
    return [$sql, $parametros];
}

function ep_total_documentos_parceiro(
    int $empresaId,
    ?int $propostaId = null,
    string $pesquisa = ''
): int {
    [$sql, $parametros] = ep_filtros_documentos_parceiro($empresaId, $propostaId, $pesquisa);
    $consulta = ep_db()->prepare('SELECT COUNT(*)' . $sql);
    $consulta->execute($parametros);
    return (int) $consulta->fetchColumn();
}

/** Listagem paginada, com pesquisa e ordenação permitidas pelo formulário. */
function ep_listar_documentos_parceiro(
    int $empresaId,
    ?int $propostaId = null,
    string $pesquisa = '',
    string $ordenacao = 'recentes',
    int $pagina = 1,
    int $porPagina = 2
): array {
    if (!in_array($ordenacao, ['recentes', 'antigos'], true)) {
        throw new InvalidArgumentException('Ordenação inválida.');
    }
    if ($pagina < 1 || $porPagina < 1 || $porPagina > 100) {
        throw new InvalidArgumentException('Paginação inválida.');
    }

    [$sql, $parametros] = ep_filtros_documentos_parceiro($empresaId, $propostaId, $pesquisa);
    $sentido = $ordenacao === 'recentes' ? 'DESC' : 'ASC';
    $sql = 'SELECT d.`documento_id`, d.`título`, d.`arquivo_path`, d.`data_envio`'
        . $sql
        . ' ORDER BY d.`data_envio` ' . $sentido . ', d.`documento_id` ' . $sentido
        . ' LIMIT :limite OFFSET :inicio';

    $consulta = ep_db()->prepare($sql);
    foreach ($parametros as $nome => $valor) {
        $consulta->bindValue(':' . $nome, $valor, PDO::PARAM_STR);
    }
    $consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $consulta->bindValue(':inicio', ($pagina - 1) * $porPagina, PDO::PARAM_INT);
    $consulta->execute();
    return $consulta->fetchAll();
}

/** Retorna metadados do arquivo e empresa proprietária para autorizar cada download. */
function ep_buscar_documento_para_abertura(string $tipo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    if ($tipo === 'convenio') {
        $sql = 'SELECT c.`convênio_id` AS id, c.`arquivo_contrato_path` AS caminho,
                       p.`empresa_id`
                FROM `Convênios_Contratos` c
                INNER JOIN `Propostas_Externas` p ON p.`proposta_id` = c.`proposta_id`
                WHERE c.`convênio_id` = :id LIMIT 1';
    } elseif ($tipo === 'acompanhamento') {
        $sql = 'SELECT d.`documento_id` AS id, d.`arquivo_path` AS caminho,
                       p.`empresa_id`
                FROM `Documentos_Acompanhamento` d
                INNER JOIN `Propostas_Externas` p ON p.`proposta_id` = d.`proposta_id`
                WHERE d.`documento_id` = :id LIMIT 1';
    } else {
        return null;
    }

    $consulta = ep_db()->prepare($sql);
    $consulta->execute(['id' => $id]);
    return $consulta->fetch() ?: null;
}

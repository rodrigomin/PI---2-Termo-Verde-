<?php
function ep_buscar_demanda(int $propostaId): ?array
{
    $consulta = ep_db()->prepare(
        'SELECT p.`proposta_id`, p.`título`, p.`descricão_problema`,
                p.`escopo`, p.`status`, p.`empresa_id`, p.`data_limite`,
                p.`data_cadastro`, e.`nome_fantasia`
         FROM `Propostas_Externas` p
         LEFT JOIN `Empresas_Parceiras` e ON e.`empresa_id` = p.`empresa_id`
         WHERE p.`proposta_id` = :id LIMIT 1'
    );
    $consulta->execute(['id' => $propostaId]);
    return $consulta->fetch() ?: null;
}

function ep_listar_demandas(string $pesquisa = '', string $status = '', ?int $empresaId = null): array
{
    $sql = 'SELECT p.`proposta_id`, p.`título`, p.`descricão_problema`,
                   p.`escopo`, p.`status`, p.`empresa_id`, p.`data_limite`,
                   p.`data_cadastro`, e.`nome_fantasia`
            FROM `Propostas_Externas` p
            LEFT JOIN `Empresas_Parceiras` e ON e.`empresa_id` = p.`empresa_id`
            WHERE p.`título` LIKE :pesquisa';
    $parametros = ['pesquisa' => '%' . $pesquisa . '%'];
    if ($status !== '') {
        $sql .= ' AND p.`status` = :status';
        $parametros['status'] = $status;
    }
    if ($empresaId !== null) {
        $sql .= ' AND p.`empresa_id` = :empresa_id';
        $parametros['empresa_id'] = $empresaId;
    }
    $sql .= ' ORDER BY p.`data_cadastro` DESC, p.`proposta_id` DESC';
    $consulta = ep_db()->prepare($sql);
    $consulta->execute($parametros);
    return $consulta->fetchAll();
}

function ep_total_demandas(?string $status = null): int
{
    if ($status === null) {
        return (int) ep_db()->query('SELECT COUNT(*) FROM `Propostas_Externas`')->fetchColumn();
    }
    $consulta = ep_db()->prepare('SELECT COUNT(*) FROM `Propostas_Externas` WHERE `status` = ?');
    $consulta->execute([$status]);
    return (int) $consulta->fetchColumn();
}

/** Retorna os totais do painel; representantes veem apenas suas demandas. */
function ep_indicadores_visao_geral(?int $empresaId): array
{
    $sql = 'SELECT COUNT(*) AS total,
                   COALESCE(SUM(CASE WHEN `status` = \'Em execução\' THEN 1 ELSE 0 END), 0) AS execucao,
                   COALESCE(SUM(CASE WHEN `status` = \'Em triagem\' THEN 1 ELSE 0 END), 0) AS triagem,
                   COALESCE(SUM(CASE WHEN `status` = \'Submetido\' THEN 1 ELSE 0 END), 0) AS submetidas
            FROM `Propostas_Externas`';
    $parametros = [];
    if ($empresaId !== null) {
        $sql .= ' WHERE `empresa_id` = :empresa_id';
        $parametros['empresa_id'] = $empresaId;
    }
    $consulta = ep_db()->prepare($sql);
    $consulta->execute($parametros);
    $dados = $consulta->fetch();

    $total = (int) $dados['total'];
    $execucao = (int) $dados['execucao'];
    return [
        'total' => $total,
        'execucao' => $execucao,
        'triagem' => (int) $dados['triagem'],
        'submetidas' => (int) $dados['submetidas'],
        'percentual_execucao' => $total > 0 ? round($execucao * 100 / $total, 1) : 0.0,
    ];
}

/** Retorna a distribuição real por status de demandas cadastradas nos últimos 30 dias. */
function ep_status_demandas_ultimo_mes(?int $empresaId): array
{
    $sql = "SELECT COALESCE(NULLIF(TRIM(`status`), ''), 'Sem status') AS nome,
                   COUNT(*) AS quantidade
            FROM `Propostas_Externas`
            WHERE `data_cadastro` >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              AND `data_cadastro` <= NOW()";
    $parametros = [];
    if ($empresaId !== null) {
        $sql .= ' AND `empresa_id` = :empresa_id';
        $parametros['empresa_id'] = $empresaId;
    }
    $sql .= ' GROUP BY nome ORDER BY quantidade DESC, nome ASC';
    $consulta = ep_db()->prepare($sql);
    $consulta->execute($parametros);
    $dados = $consulta->fetchAll();
    $total = array_sum(array_map(static function ($linha) {
        return (int) $linha['quantidade'];
    }, $dados));
    foreach ($dados as &$linha) {
        $linha['quantidade'] = (int) $linha['quantidade'];
        $linha['percentual'] = $total > 0
            ? round($linha['quantidade'] * 100 / $total, 1)
            : 0.0;
    }
    unset($linha);
    return $dados;
}

/** Lista apenas os status encontrados no escopo do usuário. */
function ep_status_demandas_disponiveis(?int $empresaId): array
{
    $sql = "SELECT DISTINCT TRIM(`status`) AS nome
            FROM `Propostas_Externas`
            WHERE `status` IS NOT NULL AND TRIM(`status`) <> ''";
    $parametros = [];
    if ($empresaId !== null) {
        $sql .= ' AND `empresa_id` = :empresa_id';
        $parametros['empresa_id'] = $empresaId;
    }
    $sql .= ' ORDER BY nome';
    $consulta = ep_db()->prepare($sql);
    $consulta->execute($parametros);
    return array_column($consulta->fetchAll(), 'nome');
}

/** Monta condições comuns para consulta e contagem, com parâmetros vinculados. */
function ep_filtros_visao_geral(string $pesquisa, string $status, ?int $empresaId): array
{
    $condicoes = [];
    $parametros = [];
    if ($pesquisa !== '') {
        $condicoes[] = 'LOCATE(:pesquisa, p.`título`) > 0';
        $parametros['pesquisa'] = $pesquisa;
    }
    if ($status !== '') {
        $condicoes[] = 'p.`status` = :status';
        $parametros['status'] = $status;
    }
    if ($empresaId !== null) {
        $condicoes[] = 'p.`empresa_id` = :empresa_id';
        $parametros['empresa_id'] = $empresaId;
    }
    return [
        $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '',
        $parametros,
    ];
}

function ep_total_demandas_visao_geral(string $pesquisa, string $status, ?int $empresaId): int
{
    [$onde, $parametros] = ep_filtros_visao_geral($pesquisa, $status, $empresaId);
    $consulta = ep_db()->prepare('SELECT COUNT(*) FROM `Propostas_Externas` p' . $onde);
    $consulta->execute($parametros);
    return (int) $consulta->fetchColumn();
}

function ep_listar_demandas_visao_geral(
    string $pesquisa,
    string $status,
    ?int $empresaId,
    int $pagina = 1,
    int $porPagina = 8
): array {
    $pagina = max(1, $pagina);
    $porPagina = max(1, min(100, $porPagina));
    $inicio = ($pagina - 1) * $porPagina;
    [$onde, $parametros] = ep_filtros_visao_geral($pesquisa, $status, $empresaId);
    $sql = 'SELECT p.`proposta_id`, p.`título`, p.`descricão_problema`,
                   p.`status`, p.`data_cadastro`, e.`razão_social`, e.`nome_fantasia`
            FROM `Propostas_Externas` p
            LEFT JOIN `Empresas_Parceiras` e ON e.`empresa_id` = p.`empresa_id`'
        . $onde
        . ' ORDER BY p.`data_cadastro` DESC, p.`proposta_id` DESC'
        . ' LIMIT :limite OFFSET :inicio';
    $consulta = ep_db()->prepare($sql);
    foreach ($parametros as $chave => $valor) {
        $consulta->bindValue(':' . $chave, $valor);
    }
    $consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $consulta->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $consulta->execute();
    return $consulta->fetchAll();
}


/** Cadastra a demanda para a empresa associada ao usuário autenticado. */
function ep_cadastrar_demanda(array $dados, array $arquivo): int
{
    $usuario = ep_usuario_atual();
    if ($usuario === null) {
        throw new RuntimeException('É necessário estar autenticado para cadastrar demandas.');
    }

    $empresaId = (int) ($usuario['empresa_id'] ?? 0);
    if ($empresaId <= 0 || !ep_pode_acessar_empresa($empresaId)) {
        throw new InvalidArgumentException('Seu usuário precisa estar vinculado a uma empresa para cadastrar demandas.');
    }

    $titulo = ep_texto($dados, 'titulo');
    $descricao = ep_texto($dados, 'descricao');
    $dataLimite = ep_texto($dados, 'data_limite');

    if ($titulo === '' || strlen($titulo) > 150) {
        throw new InvalidArgumentException('Informe o título da demanda (até 150 caracteres).');
    }
    if ($descricao === '' || strlen($descricao) > 60000) {
        throw new InvalidArgumentException('Informe uma descrição de até 60.000 bytes.');
    }
    if (!ep_validar_data($dataLimite)) {
        throw new InvalidArgumentException('Informe uma data limite válida.');
    }

    // A validação do arquivo é feita antes de qualquer gravação no banco.
    ep_validar_arquivo_escopo($arquivo);

    $caminho = '';
    $db = ep_db();
    try {
        $caminho = ep_salvar_arquivo_escopo($arquivo);
        $db->beginTransaction();

        $consulta = $db->prepare(
            'INSERT INTO `Propostas_Externas`
                (`título`, `descricão_problema`, `escopo`, `status`, `empresa_id`, `data_limite`)
             VALUES (:titulo, :descricao, :escopo, :status, :empresa_id, :data_limite)'
        );
        $consulta->execute([
            'titulo' => $titulo,
            'descricao' => $descricao,
            'escopo' => $caminho,
            'status' => 'Em triagem',
            'empresa_id' => $empresaId,
            'data_limite' => $dataLimite,
        ]);

        $propostaId = (int) $db->lastInsertId();
        $db->commit();
        return $propostaId;
    } catch (Throwable $erro) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($caminho !== '') {
            ep_remover_arquivo_escopo($caminho);
        }
        throw $erro;
    }
}


/** Total de propostas cadastradas para um parceiro específico. */
function ep_total_propostas_parceiro(int $empresaId): int
{
    $consulta = ep_db()->prepare(
        'SELECT COUNT(*) FROM `Propostas_Externas` WHERE `empresa_id` = :empresa_id'
    );
    $consulta->execute(['empresa_id' => $empresaId]);
    return (int) $consulta->fetchColumn();
}

/** Status reais associados às propostas de um único parceiro. */
function ep_status_propostas_parceiro(int $empresaId): array
{
    $consulta = ep_db()->prepare(
        "SELECT DISTINCT COALESCE(NULLIF(TRIM(`status`), ''), 'Sem status') AS nome
         FROM `Propostas_Externas`
         WHERE `empresa_id` = :empresa_id
         ORDER BY nome"
    );
    $consulta->execute(['empresa_id' => $empresaId]);
    return array_column($consulta->fetchAll(), 'nome');
}

/** Pesquisa propostas de uma única empresa, com filtro opcional de status. */
function ep_listar_propostas_parceiro(
    int $empresaId,
    string $pesquisa = '',
    string $status = ''
): array {
    $sql = "SELECT p.`proposta_id`, p.`título`, p.`descricão_problema`,
                   p.`escopo`, p.`status`, p.`data_cadastro`
            FROM `Propostas_Externas` p
            WHERE p.`empresa_id` = :empresa_id";
    $parametros = ['empresa_id' => $empresaId];

    if ($pesquisa !== '') {
        $sql .= " AND LOCATE(:pesquisa, COALESCE(p.`título`, '')) > 0";
        $parametros['pesquisa'] = $pesquisa;
    }
    if ($status !== '') {
        $sql .= " AND COALESCE(NULLIF(TRIM(p.`status`), ''), 'Sem status') = :status";
        $parametros['status'] = $status;
    }

    $sql .= ' ORDER BY p.`data_cadastro` DESC, p.`proposta_id` DESC';
    $consulta = ep_db()->prepare($sql);
    $consulta->execute($parametros);
    return $consulta->fetchAll();
}

<?php
function ep_buscar_empresa(int $empresaId): ?array
{
    $consulta = ep_db()->prepare(
        'SELECT `empresa_id`, `nome_fantasia`, `CNPJ`, `razão_social`,
                `contato_nome`, `email_corporativo`, `telefone`, `descricao`
         FROM `Empresas_Parceiras` WHERE `empresa_id` = :id LIMIT 1'
    );
    $consulta->execute(['id' => $empresaId]);
    return $consulta->fetch() ?: null;
}

// Compartilha as mesmas restrições entre a listagem e a contagem de parceiros. 
function ep_filtros_empresas(string $pesquisa, ?int $empresaId): array
{
    $condicoes = [];
    $parametros = [];

    if ($pesquisa !== '') {
        $condicoes[] = '(
            LOCATE(:busca_nome, COALESCE(e.`nome_fantasia`, \'\')) > 0
            OR LOCATE(:busca_razao, COALESCE(e.`razão_social`, \'\')) > 0
        )';
        $parametros['busca_nome'] = $pesquisa;
        $parametros['busca_razao'] = $pesquisa;
    }

    if ($empresaId !== null) {
        $condicoes[] = 'e.`empresa_id` = :empresa_id';
        $parametros['empresa_id'] = $empresaId;
    }

    return [
        $condicoes ? ' WHERE ' . implode(' AND ', $condicoes) : '',
        $parametros,
    ];
}

//adms podem consultar todas as empresas; demais perfis recebem o ID permitido.
function ep_listar_empresas(
    string $pesquisa = '',
    int $pagina = 1,
    int $porPagina = 4,
    ?int $empresaId = null
): array {
    $pagina = max(1, $pagina);
    $porPagina = max(1, min(100, $porPagina));
    $inicio = ($pagina - 1) * $porPagina;

    [$onde, $parametros] = ep_filtros_empresas($pesquisa, $empresaId);
    $sql = 'SELECT e.`empresa_id`, e.`nome_fantasia`, e.`CNPJ`,
                   e.`razão_social`, e.`contato_nome`, e.`descricao`
            FROM `Empresas_Parceiras` e'
        . $onde
        . ' ORDER BY e.`empresa_id` DESC LIMIT :limite OFFSET :inicio';

    $consulta = ep_db()->prepare($sql);
    foreach ($parametros as $chave => $valor) {
        $consulta->bindValue(':' . $chave, $valor, PDO::PARAM_STR);
    }
    $consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $consulta->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $consulta->execute();
    return $consulta->fetchAll();
}

// Retorna o total de resultados da mesma pesquisa e do mesmo escopo da listagem.
function ep_total_empresas_filtradas(string $pesquisa = '', ?int $empresaId = null): int
{
    [$onde, $parametros] = ep_filtros_empresas($pesquisa, $empresaId);
    $consulta = ep_db()->prepare('SELECT COUNT(*) FROM `Empresas_Parceiras` e' . $onde);
    $consulta->execute($parametros);
    return (int) $consulta->fetchColumn();
}

function ep_total_empresas(): int
{
    return (int) ep_db()->query('SELECT COUNT(*) FROM `Empresas_Parceiras`')->fetchColumn();
}

// Somente administradores podem cadastrar novas empresas parceiras. 
function ep_pode_cadastrar_parceiros(?array $usuario = null): bool
{
    $usuario = $usuario ?? ep_usuario_atual();
    return $usuario !== null && ($usuario['perfil'] ?? '') === 'administrador';
}

// Valida e cadastra uma empresa, evitando CNPJs repetidos no fluxo da aplicação. 
function ep_cadastrar_parceiro(array $dados): int
{
    if (!ep_pode_cadastrar_parceiros()) {
        throw new DomainException('Seu usuário não possui permissão para cadastrar parceiros.');
    }

    $nomeEmpresa = ep_texto($dados, 'nome_empresa');
    $cnpj = ep_normalizar_cnpj(ep_texto($dados, 'cnpj'));
    $razaoSocial = ep_texto($dados, 'razao_social');
    $nomeContato = ep_texto($dados, 'nome_contato');
    $email = strtolower(ep_texto($dados, 'email'));
    $telefone = ep_texto($dados, 'telefone');

    if ($nomeEmpresa === '' || ep_comprimento_utf8($nomeEmpresa) > 150) {
        throw new InvalidArgumentException('Informe o nome da empresa (até 150 caracteres).');
    }
    if (!ep_validar_cnpj($cnpj)) {
        throw new InvalidArgumentException('Informe um CNPJ válido, com dígitos verificadores corretos.');
    }
    if ($razaoSocial === '' || ep_comprimento_utf8($razaoSocial) > 150) {
        throw new InvalidArgumentException('Informe a razão social (até 150 caracteres).');
    }
    if ($nomeContato === '' || ep_comprimento_utf8($nomeContato) > 45) {
        throw new InvalidArgumentException('Informe o nome do contato (até 45 caracteres).');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 45) {
        throw new InvalidArgumentException('Informe um e-mail corporativo válido (até 45 caracteres).');
    }
    if (!ep_validar_telefone($telefone) || strlen($telefone) > 45) {
        throw new InvalidArgumentException('Informe um telefone com DDD (10 ou 11 dígitos).');
    }

    $db = ep_db();
    $chaveLock = 'ep_parceiro_cnpj_' . $cnpj;
    $lock = $db->prepare('SELECT GET_LOCK(:chave, 10)');
    $lock->execute(['chave' => $chaveLock]);
    if ((int) $lock->fetchColumn() !== 1) {
        throw new RuntimeException('Não foi possível iniciar o cadastro agora. Tente novamente.');
    }

    try {
        $db->beginTransaction();
        try {
            // Compara o CNPJ normalizado mesmo quando registros antigos possuem máscara.
            $consulta = $db->prepare(
                'SELECT `empresa_id` FROM `Empresas_Parceiras`
                 WHERE REPLACE(REPLACE(REPLACE(REPLACE(
                     `CNPJ`, \'.\', \'\'), \'/\', \'\'), \'-\', \'\'), \' \', \'\') = :cnpj
                 LIMIT 1'
            );
            $consulta->execute(['cnpj' => $cnpj]);
            if ($consulta->fetchColumn() !== false) {
                throw new InvalidArgumentException('Já existe um parceiro cadastrado com esse CNPJ.');
            }

            $consulta = $db->prepare(
                'INSERT INTO `Empresas_Parceiras`
                 (`nome_fantasia`, `CNPJ`, `razão_social`,
                  `contato_nome`, `email_corporativo`, `telefone`)
                 VALUES (:nome, :cnpj, :razao, :contato, :email, :telefone)'
            );
            $consulta->execute([
                'nome' => $nomeEmpresa,
                'cnpj' => $cnpj,
                'razao' => $razaoSocial,
                'contato' => $nomeContato,
                'email' => $email,
                'telefone' => $telefone,
            ]);
            $id = (int) $db->lastInsertId();
            $db->commit();
            return $id;
        } catch (Throwable $erro) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $erro;
        }
    } finally {
        // GET_LOCK pertence à conexão, por isso precisa ser liberado após o COMMIT/ROLLBACK.
        try {
            $liberar = $db->prepare('SELECT RELEASE_LOCK(:chave)');
            $liberar->execute(['chave' => $chaveLock]);
        } catch (Throwable $erro) {
            error_log('Não foi possível liberar o bloqueio do CNPJ: ' . $erro->getMessage());
        }
    }
}


// Consulta um parceiro somente se estiver dentro do escopo do usuário autenticado. 
function ep_buscar_empresa_autorizada(int $empresaId, array $usuario): ?array
{
    if ($empresaId <= 0) {
        return null;
    }

    $perfil = (string) ($usuario['perfil'] ?? '');
    $empresaUsuario = (int) ($usuario['empresa_id'] ?? 0);
    if ($perfil !== 'administrador' &&
        ($perfil !== 'representante' || $empresaUsuario !== $empresaId)) {
        return null;
    }

    return ep_buscar_empresa($empresaId);
}

// Edita os dados cadastrais de um parceiro dentro do escopo do usuário autenticado. 
function ep_atualizar_parceiro(int $empresaId, array $dados, array $usuario): void
{
    if ($empresaId <= 0 || ep_buscar_empresa_autorizada($empresaId, $usuario) === null) {
        throw new DomainException('Parceiro não encontrado ou sem permissão de edição.');
    }

    $nomeEmpresa = ep_texto($dados, 'nome_empresa');
    $cnpj = ep_normalizar_cnpj(ep_texto($dados, 'cnpj'));
    $razaoSocial = ep_texto($dados, 'razao_social');
    $nomeContato = ep_texto($dados, 'nome_contato');
    $email = strtolower(ep_texto($dados, 'email'));
    $telefone = ep_texto($dados, 'telefone');

    if ($nomeEmpresa === '' || ep_comprimento_utf8($nomeEmpresa) > 150) {
        throw new InvalidArgumentException('Informe o nome da empresa (até 150 caracteres).');
    }
    if (!ep_validar_cnpj($cnpj)) {
        throw new InvalidArgumentException('Informe um CNPJ válido, com dígitos verificadores corretos.');
    }
    if ($razaoSocial === '' || ep_comprimento_utf8($razaoSocial) > 150) {
        throw new InvalidArgumentException('Informe a razão social (até 150 caracteres).');
    }
    if ($nomeContato === '' || ep_comprimento_utf8($nomeContato) > 45) {
        throw new InvalidArgumentException('Informe o nome do contato (até 45 caracteres).');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 45) {
        throw new InvalidArgumentException('Informe um e-mail corporativo válido (até 45 caracteres).');
    }
    if (!ep_validar_telefone($telefone) || strlen($telefone) > 45) {
        throw new InvalidArgumentException('Informe um telefone com DDD (10 ou 11 dígitos).');
    }

    $db = ep_db();
    // Usa o mesmo bloqueio por CNPJ do cadastro, evitando atualizações simultâneas duplicadas.
    $chaveLock = 'ep_parceiro_cnpj_' . $cnpj;
    $bloqueio = $db->prepare('SELECT GET_LOCK(:chave, 10)');
    $bloqueio->execute(['chave' => $chaveLock]);
    if ((int) $bloqueio->fetchColumn() !== 1) {
        throw new RuntimeException('Não foi possível iniciar a edição agora. Tente novamente.');
    }

    try {
        $db->beginTransaction();
        try {
            $consulta = $db->prepare(
                'SELECT `empresa_id` FROM `Empresas_Parceiras`
                 WHERE `empresa_id` = :id LIMIT 1 FOR UPDATE'
            );
            $consulta->execute(['id' => $empresaId]);
            if ($consulta->fetch() === false) {
                throw new DomainException('Parceiro não encontrado.');
            }

            $consulta = $db->prepare(
                'SELECT `empresa_id` FROM `Empresas_Parceiras`
                 WHERE REPLACE(REPLACE(REPLACE(REPLACE(
                     `CNPJ`, \'.\', \'\'), \'/\', \'\'), \'-\', \'\'), \' \', \'\') = :cnpj
                   AND `empresa_id` <> :id
                 LIMIT 1'
            );
            $consulta->execute(['cnpj' => $cnpj, 'id' => $empresaId]);
            if ($consulta->fetchColumn() !== false) {
                throw new InvalidArgumentException('Já existe outro parceiro cadastrado com esse CNPJ.');
            }

            $consulta = $db->prepare(
                'UPDATE `Empresas_Parceiras`
                 SET `nome_fantasia` = :nome,
                     `CNPJ` = :cnpj,
                     `razão_social` = :razao,
                     `contato_nome` = :contato,
                     `email_corporativo` = :email,
                     `telefone` = :telefone
                 WHERE `empresa_id` = :id'
            );
            $consulta->execute([
                'nome' => $nomeEmpresa,
                'cnpj' => $cnpj,
                'razao' => $razaoSocial,
                'contato' => $nomeContato,
                'email' => $email,
                'telefone' => $telefone,
                'id' => $empresaId,
            ]);
            $db->commit();
        } catch (Throwable $erro) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $erro;
        }
    } finally {
        try {
            $liberar = $db->prepare('SELECT RELEASE_LOCK(:chave)');
            $liberar->execute(['chave' => $chaveLock]);
        } catch (Throwable $erro) {
            error_log('Erro ao liberar bloqueio do CNPJ: ' . $erro->getMessage());
        }
    }
}

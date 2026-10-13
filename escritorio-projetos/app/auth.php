<?php
function ep_cadastrar_representante(array $dados): int
{
    $empresa = ep_texto($dados, 'empresa');
    $nome = ep_texto($dados, 'nome');
    $email = strtolower(ep_texto($dados, 'email'));
    $telefone = ep_texto($dados, 'telefone');
    // Não remover espaços da senha: o cadastro deve usar exatamente o que foi digitado.
    $senha = $dados['senha'] ?? null;
    $confirmacao = $dados['confirmar_senha'] ?? null;

    if ($empresa === '' || strlen($empresa) > 150) {
        throw new InvalidArgumentException('Informe o nome da empresa (até 150 caracteres).');
    }
    if ($nome === '' || strlen($nome) > 45) {
        throw new InvalidArgumentException('Informe o nome do representante (até 45 caracteres).');
    }
    if (strlen($email) > 45 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Informe um e-mail corporativo válido (até 45 caracteres).');
    }
    if (strlen($telefone) > 45 || !ep_validar_telefone($telefone)) {
        throw new InvalidArgumentException('Informe um telefone com DDD (10 ou 11 dígitos).');
    }
    if (!is_string($senha) || !is_string($confirmacao)
        || strlen($senha) < 8 || strlen($senha) > 4096 || $senha !== $confirmacao) {
        throw new InvalidArgumentException('As senhas devem coincidir e ter pelo menos 8 caracteres.');
    }

    $db = ep_db();
    $db->beginTransaction();
    try {
        $consulta = $db->prepare('SELECT `usuario_id` FROM `Usuarios` WHERE `email` = :email LIMIT 1');
        $consulta->execute(['email' => $email]);
        if ($consulta->fetch()) {
            throw new InvalidArgumentException('Já existe um usuário cadastrado com esse e-mail.');
        }

        $consulta = $db->prepare(
            'INSERT INTO `Empresas_Parceiras`
             (`nome_fantasia`, `contato_nome`, `email_corporativo`, `telefone`)
             VALUES (:empresa, :contato, :email, :telefone)'
        );
        $consulta->execute([
            'empresa' => $empresa,
            'contato' => $nome,
            'email' => $email,
            'telefone' => $telefone,
        ]);
        $empresaId = (int) $db->lastInsertId();

        $consulta = $db->prepare(
            'INSERT INTO `Usuarios`
             (`empresa_id`, `nome`, `email`, `telefone`, `senha_hash`, `perfil`)
             VALUES (:empresa_id, :nome, :email, :telefone, :hash, :perfil)'
        );
        $consulta->execute([
            'empresa_id' => $empresaId,
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'hash' => password_hash($senha, PASSWORD_DEFAULT),
            'perfil' => 'representante',
        ]);
        $usuarioId = (int) $db->lastInsertId();
        $db->commit();
        return $usuarioId;
    } catch (Throwable $erro) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($erro instanceof PDOException && (int) ($erro->errorInfo[1] ?? 0) === 1062) {
            throw new InvalidArgumentException('Já existe um usuário cadastrado com esse e-mail.', 0, $erro);
        }
        throw $erro;
    }
}

function ep_login(string $email, string $senha): bool
{
    $stmt = ep_db()->prepare(
        'SELECT `usuario_id`, `senha_hash` FROM `Usuarios`
         WHERE `email` = :email AND `ativo` = 1 LIMIT 1'
    );
    $stmt->execute(['email' => strtolower(trim($email))]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        return false;
    }

    ep_iniciar_sessao();
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['usuario_id'];
    return true;
}

function ep_usuario_atual(): ?array
{
    ep_iniciar_sessao();
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    $stmt = ep_db()->prepare(
        'SELECT `usuario_id`, `empresa_id`, `nome`, `email`, `perfil`
         FROM `Usuarios` WHERE `usuario_id` = :id AND `ativo` = 1 LIMIT 1'
    );
    $stmt->execute(['id' => (int) $_SESSION['usuario_id']]);
    return $stmt->fetch() ?: null;
}

function ep_exigir_login(): array
{
    $usuario = ep_usuario_atual();
    if (!$usuario) {
        ep_redirecionar('index.php');
    }
    return $usuario;
}

function ep_pode_acessar_empresa(int $empresaId): bool
{
    $usuario = ep_usuario_atual();
    return $usuario !== null && (
        $usuario['perfil'] === 'administrador'
        || (int) $usuario['empresa_id'] === $empresaId
    );
}

function ep_logout(): void
{
    ep_iniciar_sessao();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

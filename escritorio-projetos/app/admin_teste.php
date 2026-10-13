<?php
/**
 * Cria uma conta administradora somente para testes.
 * Devolve as credenciais apenas quando a conta acaba de ser criada.
 */
function ep_criar_admin_teste_se_necessario(): ?array
{
    $config = require __DIR__ . '/admin_teste_config.php';

    if (($config['habilitado'] ?? false) !== true || PHP_SAPI === 'cli') {
        return null;
    }

    $remoto = $_SERVER['REMOTE_ADDR'] ?? '';
    $hostInformado = $_SERVER['HTTP_HOST'] ?? '';
    $host = is_string($hostInformado)
        ? trim(strtolower((string) parse_url('http://' . $hostInformado, PHP_URL_HOST)), '[]')
        : '';

    $enderecosLocais = ['localhost', '127.0.0.1', '::1'];
    if (!in_array($remoto, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true)
        || !in_array($host, $enderecosLocais, true)) {
        return null;
    }

    $email = 'admin.teste@localhost.test';
    $db = ep_db();
    $consulta = $db->prepare(
        'SELECT `usuario_id` FROM `Usuarios` WHERE `email` = :email LIMIT 1'
    );
    $consulta->execute(['email' => $email]);
    if ($consulta->fetch()) {
        return null;
    }

    $senha = bin2hex(random_bytes(16));
    $inserir = $db->prepare(
        'INSERT INTO `Usuarios`
            (`empresa_id`, `nome`, `email`, `telefone`, `senha_hash`, `perfil`, `ativo`)
         VALUES
            (NULL, :nome, :email, NULL, :hash, :perfil, 1)'
    );

    try {
        $inserir->execute([
            'nome' => 'Administrador de Teste',
            'email' => $email,
            'hash' => password_hash($senha, PASSWORD_DEFAULT),
            'perfil' => 'administrador',
        ]);
    } catch (PDOException $erro) {
        // Duas requisições simultâneas podem tentar a criação. isso aqui ta evitando que isso aconteça.
        if ((int) ($erro->errorInfo[1] ?? 0) === 1062) {
            return null;
        }
        throw $erro;
    }

    return ['email' => $email, 'senha' => $senha];
}

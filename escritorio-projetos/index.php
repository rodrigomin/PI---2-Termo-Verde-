<?php
require_once __DIR__ . '/app/bootstrap.php';

$erroBanco = '';
$erroLogin = '';
$email = '';
$tokenCsrf = '';
$credenciaisAdminTeste = null;

try {
    // Mantém a inicialização automática do banco ao abrir a página inicial.
    ep_inicializar_banco();
    ep_iniciar_sessao();

    if (ep_usuario_atual() !== null) {
        ep_redirecionar('visao_geral.php');
    }

    // Cria somente no primeiro acesso local e não altera um usuário existente.
    $credenciaisAdminTeste = ep_criar_admin_teste_se_necessario();
    if ($credenciaisAdminTeste !== null) {
        header('Cache-Control: no-store');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = ep_texto($_POST, 'email');
        $senha = $_POST['senha'] ?? null;
        $tokenEnviado = $_POST['csrf_token'] ?? null;

        if (!ep_validar_csrf($tokenEnviado)) {
            $erroLogin = 'Não foi possível validar o formulário. Atualize a página e tente novamente.';
        } elseif (
            $email === '' ||
            strlen($email) > 190 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !is_string($senha) ||
            $senha === ''
        ) {
            $erroLogin = 'Preencha o e-mail e a senha corretamente.';
        } elseif (ep_login($email, $senha)) {
            ep_redirecionar('visao_geral.php');
        } else {
            $erroLogin = 'E-mail ou senha inválidos.';
        }
    }

    $tokenCsrf = ep_token_csrf();
} catch (Throwable $erro) {
    error_log('Erro no login ou banco de dados: ' . $erro->getMessage());
    http_response_code(503);
    $erroBanco = 'Erro real: ' . $erro->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escritório de Projetos - Login</title>
    <link rel="stylesheet" href="./styles/styles.css?v=<?php echo filemtime(__DIR__ . '/styles/styles.css'); ?>"/>
    <link rel="stylesheet" href="./styles/index.css?v=<?php echo filemtime(__DIR__ . '/styles/index.css'); ?>"/>


</head>
<body style='max-width: 100dvw; max-height: 100dvh;'>
    <main>
        <section class='flex flex-row w-full h-full'>

            <div class='flex flex-1 flex-col justify-center items-center w-full h-full'>
                <?php include_once __DIR__ . '/components/hero.php'; ?>
            </div>

            <div class='flex flex-1 flex-col justify-center items-center w-full text-center' style='height: 100dvh;'>
                <h2>Fazer Login</h2>

                <?php if ($erroBanco !== ''): ?>
                    <p role="alert"><?= ep_e($erroBanco) ?></p>
                <?php else: ?>
                    <?php if ($credenciaisAdminTeste !== null): ?>
                        <p role="status">Administrador de teste criado para este banco local.</p>
                        <p>E-mail: <strong><?= ep_e($credenciaisAdminTeste['email']) ?></strong></p>
                        <p>Senha temporária: <strong><?= ep_e($credenciaisAdminTeste['senha']) ?></strong></p>
                        <p>Anote a senha agora: ela não será exibida novamente.</p>
                    <?php endif; ?>

                    <?php if ($erroLogin !== ''): ?>
                        <p role="alert"><?= ep_e($erroLogin) ?></p>
                    <?php endif; ?>

                    <form action="index.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= ep_e($tokenCsrf) ?>">

                        <div>
                            <label for="email">E-mail</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="Digite seu e-mail"
                                value="<?= ep_e($email) ?>"
                                autocomplete="username"
                                required
                            >
                        </div>

                        <div>
                            <label for="senha">Senha</label>
                            <input
                                type="password"
                                id="senha"
                                name="senha"
                                placeholder="Digite sua senha"
                                autocomplete="current-password"
                                required
                            >
                        </div>

                        <p><a href="#">Esqueci a senha</a></p>
                        <button type="submit">Fazer Login</button>
                    </form>
                <?php endif; ?>

                <p><a href="cadastro.php">Cadastre-se aqui</a></p>
            </div>
        </section>
    </main>

    
</body>
</html>

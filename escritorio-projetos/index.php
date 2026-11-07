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
    <title>Login - Escritório de Projetos</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" />
    <link rel="stylesheet" href="./styles/styles.css?v=<?php echo filemtime(__DIR__ . '/styles/styles.css'); ?>"/>
    <link rel="stylesheet" href="./styles/index.css?v=<?php echo filemtime(__DIR__ . '/styles/index.css'); ?>"/>
    <script type="module" src="./js/index.js?v=<?php echo filemtime(__DIR__ . '/js/index.js'); ?>"></script>
</head>
<body style="max-width: 100dvw; max-height: 100dvh; overflow: hidden; margin: 0;">
    <main class="login-container" style="display: flex; width: 100vw; height: 100vh;">
        
        <!-- Lado Esquerdo (Vermelho) -->
        <section class="lado-esquerdo" style="flex: 1; background-color: #b30000; color: #ffffff; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 40px; box-sizing: border-box;">
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px; max-width: 500px; width: 100%;">
                
            
                
                <?php 
                    if (file_exists(__DIR__ . '/components/hero.php')) {
                        echo '<div style="width: 100%; display: flex; justify-content: center; align-items: center; padding-left: 20px;">';
                        include_once __DIR__ . '/components/hero.php';
                        echo '</div>';
                    } else {
                        echo '<img class="logo-principal" src="./img/cps-branco.png" alt="Centro Paula Souza" style="max-width: 220px; width: 100%; height: auto; object-fit: contain;" />';
                    }
                ?>
            </div>
        </section>

        <!-- Lado Direito (Formulário) -->
        <section class="lado-direito" style="flex: 1; background-color: #e5e5e5; display: flex; flex-direction: column; justify-content: center; align-items: center; position: relative; padding: 40px; box-sizing: border-box;">
            <div class="formulario-wrapper" style="width: 100%; max-width: 400px; display: flex; flex-direction: column; gap: 16px; margin-bottom: 50px;">
                <h2 style="font-size: 1.8rem; font-weight: 600; color: #222; text-align: center; margin-bottom: 5px;">Fazer Login</h2>

                <?php if ($erroBanco !== ''): ?>
                    <p role="alert" style="color: #b30000; font-weight: 500; text-align: center; font-size: 0.9rem;"><?= ep_e($erroBanco) ?></p>
                <?php else: ?>
                    <?php if ($credenciaisAdminTeste !== null): ?>
                        <div role="status" style="background: #fff; padding: 12px; border-radius: 6px; font-size: 0.85rem; color: #333; text-align: left;">
                            <p>Administrador de teste criado para este banco local.</p>
                            <p>E-mail: <strong><?= ep_e($credenciaisAdminTeste['email']) ?></strong></p>
                            <p>Senha temporária: <strong><?= ep_e($credenciaisAdminTeste['senha']) ?></strong></p>
                            <p>Anote a senha agora: ela não será exibida novamente.</p>
                        </div>
                    <?php endif; ?>

                    <?php if ($erroLogin !== ''): ?>
                        <p role="alert" style="color: #b30000; font-weight: 500; text-align: center; font-size: 0.9rem;"><?= ep_e($erroLogin) ?></p>
                    <?php endif; ?>

                    <form action="index.php" method="post" style="display: flex; flex-direction: column; gap: 14px; width: 100%;">
                        <input type="hidden" name="csrf_token" value="<?= ep_e($tokenCsrf) ?>">

                        <div style="display: flex; flex-direction: column; gap: 4px; text-align: left;">
                            <label for="email" style="font-size: 0.95rem; font-weight: 500; color: #222;">E-mail</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="Seu e-mail"
                                value="<?= ep_e($email) ?>"
                                autocomplete="username"
                                required
                                style="width: 100%; padding: 12px 14px; background-color: #ffffff; border: 1px solid #cccccc; border-radius: 4px; font-size: 0.95rem; outline: none;"
                            >
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 4px; text-align: left;">
                            <label for="senha" style="font-size: 0.95rem; font-weight: 500; color: #222;">Senha</label>
                            <input
                                type="password"
                                id="senha"
                                name="senha"
                                placeholder="Senha"
                                autocomplete="current-password"
                                required
                                style="width: 100%; padding: 12px 14px; background-color: #ffffff; border: 1px solid #cccccc; border-radius: 4px; font-size: 0.95rem; outline: none;"
                            >
                        </div>

                        <a href="#" style="align-self: flex-start; font-size: 0.85rem; color: #0000ee; text-decoration: underline;">Esqueci a senha</a>
                        
                        <button type="submit" style="width: 100%; padding: 12px; background-color: #b30000; color: #ffffff; border: none; border-radius: 4px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background-color 0.2s;">Fazer Login</button>
                    </form>
                <?php endif; ?>

            </div>

            <!-- Rodapé ajustado com o "Cadastre-se aqui" logo acima e as novas logos -->
            <div class="logos-rodape" style="position: absolute; bottom: 20px; display: flex; flex-direction: column; align-items: center; gap: 15px; width: 100%; padding: 0 20px; box-sizing: border-box;">
                <a href="cadastro.php" style="font-size: 0.95rem; color: #b30000; text-decoration: underline; font-weight: 600;">Cadastre-se aqui</a>
                
                <div style="display: flex; justify-content: center; align-items: center; gap: 30px; width: 100%;">
                    <img src="./src/public/assets/./img/Brasão_do_estado_de_São_Paulo.jpg alt="Brasão do Estado de São Paulo style="height: 60px; width: auto; object-fit: contain;" />
                    <img style="-webkit-filter: invert(100%); filter: invert(100%);"src="./src/public/assets/cps-logo-branco.png" alt="Logo do Escritório de Projetos" width="100" height="70">
                    <img style="-webkit-filter: invert(0%); filter: invert(0%);" src="./src/public/assets/fundacao-logo.png" alt="Logo da Fundação Shunji Nishimura" width="200">
                </div>
            </div>
        </section>
    </main>
</body>
</html>
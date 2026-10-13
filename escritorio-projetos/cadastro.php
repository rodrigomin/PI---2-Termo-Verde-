<?php
require_once __DIR__ . '/app/bootstrap.php';

$erroBanco = '';
$erroCadastro = '';
$mensagemSucesso = '';
$tokenCsrf = '';

$empresa = '';
$nome = '';
$email = '';
$telefone = '';

try {
    // Permite iniciar o projeto diretamente pela tela de cadastro.
    ep_inicializar_banco();
    ep_iniciar_sessao();

    if (ep_usuario_atual() !== null) {
        ep_redirecionar('visao_geral.php');
    }

    if (isset($_SESSION['cadastro_sucesso'])) {
        $mensagemSucesso = (string) $_SESSION['cadastro_sucesso'];
        unset($_SESSION['cadastro_sucesso']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $empresa = ep_texto($_POST, 'empresa');
        $nome = ep_texto($_POST, 'nome');
        $email = ep_texto($_POST, 'email');
        $telefone = ep_texto($_POST, 'telefone');

        if (!ep_validar_csrf($_POST['csrf_token'] ?? null)) {
            $erroCadastro = 'Não foi possível validar o formulário. Recarregue a página e tente novamente.';
        } else {
            try {
                ep_cadastrar_representante($_POST);
                $_SESSION['cadastro_sucesso'] = 'Cadastro realizado com sucesso. Agora você pode fazer login.';
                ep_redirecionar('cadastro.php');
            } catch (InvalidArgumentException $erro) {
                $erroCadastro = $erro->getMessage();
            }
        }
    }

    $tokenCsrf = ep_token_csrf();
} catch (Throwable $erro) {
    error_log('Erro ao cadastrar representante: ' . $erro->getMessage());
    http_response_code(503);
    $erroBanco = 'Não foi possível acessar o banco de dados. Verifique o MySQL e app/config.php.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Representante - Escritório de Projetos</title>
</head>
<body>
    <main>
        <h1>Cadastro de Representante da Empresa</h1>

        <?php if ($erroBanco !== ''): ?>
            <p role="alert"><?= ep_e($erroBanco) ?></p>
        <?php else: ?>
            <?php if ($mensagemSucesso !== ''): ?>
                <p role="status"><?= ep_e($mensagemSucesso) ?></p>
                <p><a href="index.php">Fazer Login</a></p>
            <?php else: ?>
                <?php if ($erroCadastro !== ''): ?>
                    <p role="alert"><?= ep_e($erroCadastro) ?></p>
                <?php endif; ?>

                <form action="cadastro.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= ep_e($tokenCsrf) ?>">

                    <div>
                        <label for="empresa">Nome da empresa</label>
                        <input type="text" id="empresa" name="empresa"
                            placeholder="Nome da empresa que representa"
                            maxlength="150" value="<?= ep_e($empresa) ?>" required>
                    </div>

                    <div>
                        <label for="nome">Seu nome</label>
                        <input type="text" id="nome" name="nome"
                            placeholder="Seu nome" maxlength="45"
                            value="<?= ep_e($nome) ?>" required>
                    </div>

                    <div>
                        <label for="email">E-mail corporativo</label>
                        <input type="email" id="email" name="email"
                            placeholder="Email corporativo" maxlength="45"
                            autocomplete="email" value="<?= ep_e($email) ?>" required>
                    </div>

                    <div>
                        <label for="telefone">Telefone</label>
                        <input type="tel" id="telefone" name="telefone"
                            placeholder="Seu telefone" maxlength="45"
                            autocomplete="tel" value="<?= ep_e($telefone) ?>" required>
                    </div>

                    <div>
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha"
                            placeholder="Senha" minlength="8"
                            autocomplete="new-password" required>
                    </div>

                    <div>
                        <label for="confirmar_senha">Confirmar Senha</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha"
                            placeholder="Senha" minlength="8"
                            autocomplete="new-password" required>
                    </div>

                    <button type="submit">Cadastre-se</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <p><a href="index.php">Voltar para o login</a></p>
    </main>
</body>
</html>

<?php
require_once __DIR__ . '/app/bootstrap.php';

$erroPagina = '';
$erroCadastro = '';
$mensagemSucesso = '';
$tokenCsrf = '';
$podeCadastrar = false;

$nomeEmpresa = '';
$cnpj = '';
$razaoSocial = '';
$nomeContato = '';
$email = '';
$telefone = '';

try {
    $usuario = ep_exigir_login();
    $podeCadastrar = ep_pode_cadastrar_parceiros($usuario);
    ep_iniciar_sessao();

    if (isset($_SESSION['parceiro_sucesso'])) {
        $mensagemSucesso = (string) $_SESSION['parceiro_sucesso'];
        unset($_SESSION['parceiro_sucesso']);
    }

    if (!$podeCadastrar) {
        http_response_code(403);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nomeEmpresa = ep_texto($_POST, 'nome_empresa');
        $cnpj = ep_texto($_POST, 'cnpj');
        $razaoSocial = ep_texto($_POST, 'razao_social');
        $nomeContato = ep_texto($_POST, 'nome_contato');
        $email = ep_texto($_POST, 'email');
        $telefone = ep_texto($_POST, 'telefone');

        if (!ep_validar_csrf($_POST['csrf_token'] ?? null)) {
            $erroCadastro = 'Não foi possível validar o formulário. Atualize a página e tente novamente.';
        } else {
            try {
                $id = ep_cadastrar_parceiro($_POST);
                $_SESSION['parceiro_sucesso'] = 'Parceiro cadastrado com sucesso (nº ' . $id . ').';
                ep_redirecionar('cadastro_parceiro.php');
            } catch (InvalidArgumentException | DomainException $erro) {
                $erroCadastro = $erro->getMessage();
            }
        }
    }

    if ($podeCadastrar) {
        $tokenCsrf = ep_token_csrf();
    }
} catch (Throwable $erro) {
    error_log('Erro no Cadastro de Parceiros: ' . $erro->getMessage());
    http_response_code(503);
    $erroPagina = 'Não foi possível acessar o cadastro de parceiros. Verifique o MySQL no XAMPP.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Parceiros - Escritório de Projetos</title>
</head>
<body>
    <header>
        <h1>Escritório de Projetos</h1>
    </header>

    <nav aria-label="Menu principal">
        <ul>
            <li><a href="visao_geral.php">Visão Geral</a></li>
            <li><a href="cadastro_demanda.php" aria-current="page">Cadastro</a></li>
            <li><a href="parceiros.php">Parceiros</a></li>
            <li><a href="envio_demandas.php">Envio Demandas</a></li>
        </ul>
        <p><a href="#">Conta</a></p>
    </nav>

    <main>
        <section>
            <h2>Cadastro</h2>
            <nav aria-label="Tipos de cadastro">
                <ul>
                    <li><a href="cadastro_demanda.php">Demandas</a></li>
                    <li><a href="cadastro_parceiro.php" aria-current="page">Parceiros</a></li>
                </ul>
            </nav>
        </section>

        <section>
            <?php if ($erroPagina !== ''): ?>
                <p role="alert"><?= ep_e($erroPagina) ?></p>
            <?php elseif (!$podeCadastrar): ?>
                <p role="alert">Seu usuário não possui permissão para cadastrar novos parceiros.</p>
            <?php else: ?>
                <?php if ($mensagemSucesso !== ''): ?>
                    <p role="status"><?= ep_e($mensagemSucesso) ?></p>
                    <p><a href="parceiros.php">Consultar o Banco de Parceiros</a></p>
                <?php endif; ?>
                <?php if ($erroCadastro !== ''): ?>
                    <p role="alert"><?= ep_e($erroCadastro) ?></p>
                <?php endif; ?>

                <form action="cadastro_parceiro.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= ep_e($tokenCsrf) ?>">

                    <div>
                        <label for="nome_empresa">Nome da Empresa</label>
                        <input type="text" id="nome_empresa" name="nome_empresa"
                            value="<?= ep_e($nomeEmpresa) ?>" maxlength="150" required>
                    </div>

                    <div>
                        <label for="cnpj">CNPJ</label>
                        <input type="text" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00"
                            value="<?= ep_e($cnpj) ?>" maxlength="18" required>
                    </div>

                    <div>
                        <label for="razao_social">Razão Social</label>
                        <input type="text" id="razao_social" name="razao_social"
                            value="<?= ep_e($razaoSocial) ?>" maxlength="150" required>
                    </div>

                    <div>
                        <label for="nome_contato">Nome do Contato</label>
                        <input type="text" id="nome_contato" name="nome_contato"
                            value="<?= ep_e($nomeContato) ?>" maxlength="45" required>
                    </div>

                    <div>
                        <label for="email">Email Corporativo</label>
                        <input type="email" id="email" name="email"
                            value="<?= ep_e($email) ?>" maxlength="45" required>
                    </div>

                    <div>
                        <label for="telefone">Telefone</label>
                        <input type="tel" id="telefone" name="telefone" placeholder="DDD + Número"
                            value="<?= ep_e($telefone) ?>" maxlength="45" required>
                    </div>

                    <button type="submit">Cadastrar Parceiro</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

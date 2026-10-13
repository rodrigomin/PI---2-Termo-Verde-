<?php
require_once __DIR__ . '/app/bootstrap.php';

$erroPagina = '';
$erroCadastro = '';
$mensagemSucesso = '';
$tokenCsrf = '';
$usuario = null;
$podeCadastrar = false;

$titulo = '';
$descricao = '';
$dataLimite = '';

try {
    $usuario = ep_exigir_login();
    $podeCadastrar = (int) ($usuario['empresa_id'] ?? 0) > 0;
    ep_iniciar_sessao();

    if (isset($_SESSION['demanda_sucesso'])) {
        $mensagemSucesso = (string) $_SESSION['demanda_sucesso'];
        unset($_SESSION['demanda_sucesso']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $podeCadastrar) {
        $titulo = ep_texto($_POST, 'titulo');
        $descricao = ep_texto($_POST, 'descricao');
        $dataLimite = ep_texto($_POST, 'data_limite');

        if (!ep_validar_csrf($_POST['csrf_token'] ?? null)) {
            $erroCadastro = 'Não foi possível validar o formulário. Atualize a página e tente novamente.';
        } else {
            try {
                $id = ep_cadastrar_demanda($_POST, $_FILES['escopo'] ?? []);
                $_SESSION['demanda_sucesso'] = 'Demanda cadastrada com sucesso (nº ' . $id . ').';
                ep_redirecionar('cadastro_demanda.php');
            } catch (InvalidArgumentException $erro) {
                $erroCadastro = $erro->getMessage();
            }
        }
    }

    $tokenCsrf = ep_token_csrf();
} catch (Throwable $erro) {
    error_log('Erro no Cadastro de Demanda: ' . $erro->getMessage());
    http_response_code(503);
    $erroPagina = 'Não foi possível acessar o cadastro de demandas. Verifique o MySQL e as permissões da pasta de arquivos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Demanda - Escritório de Projetos</title>
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
                    <li><a href="cadastro_demanda.php" aria-current="page">Demandas</a></li>
                    <li><a href="cadastro_parceiro.php">Parceiros</a></li>
                </ul>
            </nav>
        </section>

        <section>
            <?php if ($erroPagina !== ''): ?>
                <p role="alert"><?= ep_e($erroPagina) ?></p>
            <?php elseif (!$podeCadastrar): ?>
                <p role="alert">Seu usuário precisa estar vinculado a uma empresa para cadastrar demandas.</p>
            <?php else: ?>
                <?php if ($mensagemSucesso !== ''): ?>
                    <p role="status"><?= ep_e($mensagemSucesso) ?></p>
                    <p><a href="visao_geral.php">Ver demandas na Visão Geral</a></p>
                <?php endif; ?>

                <?php if ($erroCadastro !== ''): ?>
                    <p role="alert"><?= ep_e($erroCadastro) ?></p>
                <?php endif; ?>

                <form action="cadastro_demanda.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= ep_e($tokenCsrf) ?>">

                    <div>
                        <label for="titulo">Título da demanda</label>
                        <input
                            type="text"
                            id="titulo"
                            name="titulo"
                            placeholder="EX: Grupo Jacto"
                            maxlength="150"
                            value="<?= ep_e($titulo) ?>"
                            required
                        >
                    </div>

                    <div>
                        <label for="descricao">Descrição</label>
                        <textarea
                            id="descricao"
                            name="descricao"
                            rows="6"
                            required
                        ><?= ep_e($descricao) ?></textarea>
                    </div>

                    <div>
                        <label for="escopo">Escopo</label>
                        <input
                            type="file"
                            id="escopo"
                            name="escopo"
                            accept=".pdf,.doc,.docx"
                            required
                        >
                        <p>Formatos aceitos: PDF, DOC ou DOCX. Tamanho máximo: 8 MB.</p>
                    </div>

                    <div>
                        <label for="data_limite">Data Limite</label>
                        <input
                            type="date"
                            id="data_limite"
                            name="data_limite"
                            value="<?= ep_e($dataLimite) ?>"
                            required
                        >
                    </div>

                    <button type="submit">Cadastrar Demanda</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

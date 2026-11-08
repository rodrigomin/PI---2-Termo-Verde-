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
    $usuario = ['empresa_id' => 1];
$podeCadastrar = true;
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
            } catch (Throwable $erro) {
                error_log('Erro ao cadastrar demanda: ' . $erro->getMessage());
                $erroCadastro = 'Não foi possível salvar a demanda agora. Tente novamente.';
            }
        }
    }

    $tokenCsrf = ep_token_csrf();
} catch (Throwable $erro) {
    error_log('Erro no Cadastro de Demanda: ' . $erro->getMessage());
    http_response_code(503);
    $erroPagina = 'Não foi possível acessar o cadastro de demandas. Verifique o banco de dados e as permissões da pasta de arquivos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro de Demanda</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@200;400;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #fff; color: #000; min-height: 100vh; }

        .sidebar { position: fixed; inset: 0 auto 0 0; width: 250px; background: #fff; z-index: 2; }
        .sidebar #nav { display: flex; flex-direction: column; align-items: center; width: 100%; height: 100dvh; padding: 32px 0 24px 15px !important; overflow: hidden; }
        .sidebar #logo { display: block; width: 100px; height: auto; margin: 0 15px 20px 0 !important; object-fit: contain; }
        .sidebar #nav hr { width: calc(100% - 15px); margin: 0 15px 0 0; border: 0; border-top: 2px solid #D9D9D9; }
        .sidebar #optns { display: flex; flex: 1; flex-direction: column; gap: 12px !important; width: 100%; min-height: 0; margin: 20px 0 16px; padding: 0; list-style: none; }
        .sidebar #optns li { display: block; width: 100%; padding: 12px 10px; border-left: 4px solid transparent; }
        .sidebar #optns li.selected { background: #F7F7F7; border-left-color: #B71111; }
        .sidebar #optns li > div, .sidebar #nav button { display: flex; align-items: center; gap: 15px !important; min-width: 0; }
        .sidebar .icon { display: block; flex: 0 0 24px; width: 24px; height: 24px; object-fit: contain; }
        .sidebar .optn { color: #B71111; font-size: 15px; font-weight: 600; text-decoration: none; white-space: nowrap; }
        .sidebar #nav button { width: 100%; padding: 10px 0; border: 0; background: transparent; text-align: left; }
        .conteudo { min-width: 0; margin-left: 250px; }
        .topbar { height: 83px; border-bottom: 2px solid #D9D9D9; background: #fff; }
        main { width: min(100%, 1100px); padding: 56px clamp(20px, 5vw, 60px) 60px; }
        main h2 { margin: 0 0 24px; font-size: clamp(40px, 5vw, 64px); font-weight: 600; line-height: 1.1; }

        .tabs ul { list-style: none; margin: 0 0 20px; padding: 0; display: flex; }
        .tabs a {
            display: flex; align-items: center; justify-content: center;
            width: 247px; height: 55px; font-size: 18px; font-weight: 600;
            text-decoration: none; border-radius: 8px;
            background: #F7F7F7; color: #B71111;
        }
        .tabs a[aria-current="page"] { background: #B71111; color: #fff; }

        .card { border: 2px solid #D9D9D9; border-radius: 8px; padding: 40px 41px; background: #fff; }
        .card [role="alert"] { color: #B71111; font-weight: 600; }
        .card [role="status"] { color: #1a7f37; font-weight: 600; }

        form > div { margin-bottom: 36px; }
        label { display: block; margin-bottom: 14px; font-size: 32px; font-weight: 600; }
        input[type="text"], input[type="date"], input[type="file"], textarea {
            width: 100%; min-height: 50px; padding: 0 25px;
            border: 2px solid #D9D9D9; border-radius: 8px; background: #fff;
            font-family: inherit; font-size: 21px; font-weight: 200; color: #1D1B20;
        }
        input[type="file"] { padding: 10px 25px; }
        input::placeholder, textarea::placeholder { color: #D9D9D9; }
        textarea { padding: 14px 25px; min-height: 117px; resize: vertical; }
        input:focus, textarea:focus { outline: 2px solid #B71111; outline-offset: 1px; }
        form p { margin: 8px 0 0; font-size: 14px; color: #777; }

        button[type="submit"] {
            display: block; width: 500px; max-width: 100%; height: 60px; margin: 0 auto;
            border: 0; border-radius: 17px; background: #B71111; color: #fff;
            font-family: inherit; font-size: 21px; font-weight: 600; cursor: pointer;
        }
        button[type="submit"]:hover { filter: brightness(1.1); }

        @media (max-width: 800px) {
            .sidebar { position: static; width: 100%; border-bottom: 2px solid #D9D9D9; }
            .sidebar #nav { height: auto; min-height: 0; padding: 14px 12px !important; border-right: 0 !important; }
            .sidebar #logo { width: 72px; margin: 0 0 12px !important; }
            .sidebar #nav hr { width: 100%; margin: 0; }
            .sidebar #optns { flex: 0 0 auto; flex-direction: row; gap: 4px !important; margin: 12px 0; overflow-x: auto; }
            .sidebar #optns li { flex: 0 0 auto; width: auto; padding: 10px 8px; border-left: 0; border-bottom: 3px solid transparent; }
            .sidebar #optns li.selected { border-bottom-color: #B71111; }
            .sidebar #optns li > div { gap: 7px !important; }
            .sidebar .icon { flex-basis: 20px; width: 20px; height: 20px; }
            .sidebar .optn { font-size: 13px; }
            .sidebar #nav button { width: auto; align-self: flex-start; }
            .sidebar #nav button .icon { display: none; }
            .conteudo { margin-left: 0; }
            main { padding: 30px 16px; }
            main h2 { font-size: 40px; }
            .tabs ul { gap: 8px; }
            .tabs li { flex: 1; min-width: 0; }
            .tabs a { width: 100%; }
            .card { padding: 24px 18px; }
            label { font-size: 24px; }
            input[type="text"], input[type="date"], input[type="file"], textarea { padding-right: 12px; padding-left: 12px; font-size: 16px; }
        }
    </style>
</head>
<body>

    <header class="sidebar">
        <?php include_once __DIR__ . '/components/nav.php'; ?>
    </header>

    <div class="conteudo">
        <div class="topbar"></div>

        <main>
            <h2>Cadastro</h2>

            <nav class="tabs" aria-label="Tipos de cadastro">
                <ul>
                    <li><a href="cadastro_demanda.php" aria-current="page">Demandas</a></li>
                    <li><a href="cadastro_parceiro.php">Parceiros</a></li>
                </ul>
            </nav>

            <section class="card">
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
                            <input type="text" id="titulo" name="titulo" placeholder="EX: Grupo Jacto"
                                   maxlength="150" value="<?= ep_e($titulo) ?>" required>
                        </div>

                        <div>
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao" rows="6" required><?= ep_e($descricao) ?></textarea>
                        </div>

                        <div>
                            <label for="escopo">Escopo</label>
                            <input type="file" id="escopo" name="escopo" accept=".pdf,.doc,.docx" required>
                            <p>Formatos aceitos: PDF, DOC ou DOCX. Tamanho máximo: 8 MB.</p>
                        </div>

                        <div>
                            <label for="data_limite">Data Limite</label>
                            <input type="date" id="data_limite" name="data_limite"
                                   value="<?= ep_e($dataLimite) ?>" required>
                        </div>

                        <button type="submit">Cadastrar Demanda</button>
                    </form>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
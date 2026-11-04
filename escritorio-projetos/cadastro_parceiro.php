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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro de Parceiro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@200;400;600&family=Poppins:wght@600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #fff; color: #1E1E1E; min-height: 100vh; }

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
        main h2 { margin: 0 0 24px; font-size: clamp(40px, 5vw, 64px); font-weight: 600; line-height: 1.1; color: #000; }

        .tabs ul { list-style: none; margin: 0 0 20px; padding: 0; display: flex; }
        .tabs a {
            display: flex; align-items: center; justify-content: center;
            width: 247px; height: 55px; font-size: 18px; font-weight: 600;
            text-decoration: none; border-radius: 8px;
            background: #F7F7F7; color: #B71111;
        }
        .tabs a[aria-current="page"] { background: #B71111; color: #F7F7F7; }

        .card { border: 2px solid #D9D9D9; border-radius: 8px; padding: 30px 21px 50px; background: #fff; }
        .card [role="alert"] { color: #B71111; font-weight: 600; }
        .card [role="status"] { color: #1a7f37; font-weight: 600; }

        form > div { margin-bottom: 18px; }
        label { display: block; margin-bottom: 4px; font-size: 32px; font-weight: 400; line-height: 1.4; }
        input[type="text"], input[type="email"], input[type="tel"] {
            width: 100%; max-width: 696px; height: 43px; padding: 0 20px;
            border: 2px solid #D9D9D9; border-radius: 8px; background: #fff;
            font-family: inherit; font-size: 18px; font-weight: 200; color: #1D1B20;
        }
        input::placeholder { color: #D9D9D9; }
        input:focus { outline: 2px solid #B71111; outline-offset: 1px; }

        .acoes { display: flex; justify-content: center; margin-top: 40px; }
        button[type="submit"] {
            width: 290px; height: 66px; border: 0; border-radius: 10px;
            background: #B71111;
            box-shadow: 0 4px 16px rgba(0,0,0,.25), inset 0 -3px 2px -1px #E51E33;
            color: #fff; font-family: 'Poppins', sans-serif; font-size: 21px; font-weight: 600;
            cursor: pointer;
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
            label { font-size: 22px; }
            .tabs ul { gap: 8px; }
            .tabs li { flex: 1; min-width: 0; }
            .tabs a { width: 100%; }
            .card { padding: 24px 18px; }
            input[type="text"], input[type="email"], input[type="tel"] { padding-right: 12px; padding-left: 12px; font-size: 16px; }
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
                    <li><a href="cadastro_demanda.php">Demandas</a></li>
                    <li><a href="cadastro_parceiro.php" aria-current="page">Parceiros</a></li>
                </ul>
            </nav>

            <section class="card">
                <form action="cadastro_parceiro.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= ep_e($tokenCsrf ?? '') ?>">

                    <div>
                        <label for="nome_empresa">Nome da Empresa</label>
                        <input type="text" id="nome_empresa" name="nome_empresa" maxlength="150" required>
                    </div>
                    <div>
                        <label for="cnpj">CNPJ</label>
                        <input type="text" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00" maxlength="18" inputmode="numeric" required>
                    </div>
                    <div>
                        <label for="razao_social">Razão Social</label>
                        <input type="text" id="razao_social" name="razao_social" maxlength="150" required>
                    </div>
                    <div>
                        <label for="nome_contato">Nome do Contato</label>
                        <input type="text" id="nome_contato" name="nome_contato" maxlength="150" required>
                    </div>
                    <div>
                        <label for="email">Email Corporativo</label>
                        <input type="email" id="email" name="email" maxlength="150" required>
                    </div>
                    <div>
                        <label for="telefone">Telefone</label>
                        <input type="tel" id="telefone" name="telefone" placeholder="(00) 00000-0000" maxlength="20" required>
                    </div>

                    <div class="acoes">
                        <button type="submit">Cadastrar Parceiro</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
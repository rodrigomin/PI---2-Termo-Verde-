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
            $id = ep_cadastrar_parceiro($_POST);
            try {
                ep_aprovacao_notificar_gestor([
                    'nome_fantasia' => $nomeEmpresa,
                    'contato_nome' => $nomeContato,
                    'email_corporativo' => $email,
                ]);
                $_SESSION['parceiro_sucesso'] = 'Solicitação cadastrada e enviada para análise do gestor (nº ' . $id . ').';
            } catch (Throwable $erroEmail) {
                error_log('Falha ao notificar gestor sobre parceiro: ' . $erroEmail->getMessage());

                $_SESSION['parceiro_sucesso'] = 'Solicitação cadastrada como pendente, mas o aviso ao gestor falhou (nº ' . $id . ').';
            }
            ep_redirecionar('cadastro_parceiro.php');
        }
    }

    if ($podeCadastrar) {
        $tokenCsrf = ep_token_csrf();
    }
} catch (Throwable $erro) {
    error_log('Erro no Cadastro de Parceiros: ' . $erro->getMessage());
    http_response_code(503);
    $erroPagina = $erro->getMessage(); //'Não foi possível acessar o cadastro de parceiros. Verifique o MySQL no XAMPP.';
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
    <script type="module" src='./js/index.js?v=<?php echo filemtime(__DIR__ . '/js/index.js'); ?>'> </script>
    <link rel="stylesheet" href="./styles/styles.css?v=<?php echo filemtime(__DIR__ . '/styles/styles.css'); ?>"/>
    <link rel="stylesheet" href="./styles/cadastro_parceiro.css?v=<?php echo filemtime(__DIR__ . '/styles/cadastro_parceiro.css'); ?>"/>
</head>
<body class="flex flex-row w-full">

    <?php include_once __DIR__ . '/components/nav.php'; ?>
    <div class="w-full">
        <?php include_once __DIR__ . '/components/header.php'; ?>
        <main class="p-common">
            <h2 class="title">Cadastro</h2>
            <p class="subtitle text-secondary">Cadastre novo parceiro para o Escritório de Projetos.</p>

            
            <div class="card-wrapper">
                <nav class="tabs" aria-label="Tipos de cadastro">
                    <ul>
                        <li><a href="cadastro_demanda.php">Demandas</a></li>
                        <li><a href="cadastro_parceiro.php" aria-current="page">Parceiros</a></li>
                    </ul>
                </nav>
            
                <section class="card">

                    <?php if ($erroPagina !== ''): ?>
                        <div class="error"><p role="alert"><?= ep_e($erroPagina) ?></p></div>
                    <?php elseif (!$podeCadastrar): ?>
                        <div class="error"><p role="alert">Seu usuário deve ter cargo de administrador para adicionar novos parceiros.</p></div>
                    <?php else: ?>
                        <?php if ($mensagemSucesso !== ''): ?>
                            <div class="success"><p role="status"><?= ep_e($mensagemSucesso) ?></p></div>
                        <?php endif; ?>
                        
                        <?php if ($erroCadastro !== ''): ?>
                            <div class="error"><p role="alert"><?= ep_e($erroCadastro) ?></p></div>
                        <?php endif; ?>
                    <?php endif; ?>
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

                        <div class="flex flex-row justify-between items-center w-full" style="gap: 20px">
                            <div class="flex flex-col w-full">
                                <label for="email">Email Corporativo</label>
                                <input type="email" id="email" name="email" maxlength="150" required>
                            </div>
                            <div class="flex flex-col w-full">
                                <label for="telefone">Telefone</label>
                                <input type="tel" id="telefone" name="telefone" placeholder="(00) 00000-0000" maxlength="20" required>
                            </div>
                        </div>

                        <div class="acoes">
                            <button class="interactive" type="submit">Cadastrar Parceiro</button>
                        </div>
                    </form>
                    
                </section>
            </div>
        </main>
    </div>
</body>
</html>
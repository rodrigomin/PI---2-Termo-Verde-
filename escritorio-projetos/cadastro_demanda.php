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
    <title>Banco de Demandas - Escritório de Projetos</title>
    <link rel="stylesheet" href="./styles/styles.css?v=<?php echo filemtime(__DIR__ . '/styles/styles.css'); ?>"/>
    <link rel="stylesheet" href="./styles/cadastro_demanda.css?v=<?php echo filemtime(__DIR__ . '/styles/cadastro_demanda.css'); ?>"/>
    <script type="module" src='./js/index.js?v=<?php echo filemtime(__DIR__ . '/js/index.js'); ?>'></script>
</head>
<body class="m-0 p-0 overflow-x-hidden bg-gray-50">
    <!-- Container principal (Sidebar + Conteúdo) -->
    <div class="flex flex-row w-full min-h-screen">
        
        <!-- Barra Lateral Fixa -->
        <aside class="flex flex-col justify-start items-start shrink-0 h-screen sticky top-0 bg-white shadow-sm z-10">
            <?php include_once __DIR__ . '/components/nav.php'; ?>
        </aside>

        <!-- Área de Conteúdo à Direita -->
        <div class="flex flex-col flex-1 w-full">
            <?php include_once __DIR__ . '/components/header.php'; ?>
            
            <main class="p-6 flex flex-col" style="width: 90%">
                <!-- Seção de navegação interna (Demandas / Parceiros) -->
                <div class="mb-6 block w-full">
                    <h2 class="text-2xl font-bold mb-2">Cadastro</h2>
                    <nav aria-label="Tipos de cadastro">
                        <ul class="flex gap-4">
                            <li><a href="cadastro_demanda.php" aria-current="page" class="text-purple-700 font-semibold">Demandas</a></li>
                            <li><a href="cadastro_parceiro.php" class="text-blue-600">Parceiros</a></li>
                        </ul>
                    </nav>
                </div>

                <!-- Seção do Formulário / Alertas -->
                <div class="w-full block">
                    <?php if ($erroPagina !== ''): ?>
                        <p role="alert" class="text-red-600 font-medium mb-4"><?= ep_e($erroPagina) ?></p>
                    <?php elseif (!$podeCadastrar): ?>
                        <p role="alert" class="text-red-600 font-medium mb-4">Seu usuário precisa estar vinculado a uma empresa para cadastrar demandas.</p>
                    <?php else: ?>
                        <?php if ($mensagemSucesso !== ''): ?>
                            <p role="status" class="text-green-600 font-medium mb-2"><?= ep_e($mensagemSucesso) ?></p>
                            <p class="mb-4"><a href="visao_geral.php" class="text-blue-600 underline">Ver demandas na Visão Geral</a></p>
                        <?php endif; ?>

                        <?php if ($erroCadastro !== ''): ?>
                            <p role="alert" class="text-red-600 font-medium mb-4"><?= ep_e($erroCadastro) ?></p>
                        <?php endif; ?>

                        <form action="cadastro_demanda.php" method="post" enctype="multipart/form-data" class="flex flex-col gap-4 w-full max-w-2xl">
                            <input type="hidden" name="csrf_token" value="<?= ep_e($tokenCsrf) ?>">

                            <div class="flex flex-col gap-1">
                                <label for="titulo" class="font-medium text-gray-700">Título da demanda</label>
                                <input
                                    type="text"
                                    id="titulo"
                                    name="titulo"
                                    placeholder="EX: Grupo Jacto"
                                    maxlength="150"
                                    value="<?= ep_e($titulo) ?>"
                                    required
                                    class="border border-gray-300 rounded p-2"
                                >
                            </div>

                            <div class="flex flex-col gap-1">
                                <label for="descricao" class="font-medium text-gray-700">Descrição</label>
                                <textarea
                                    id="descricao"
                                    name="descricao"
                                    rows="6"
                                    required
                                    class="border border-gray-300 rounded p-2"
                                ><?= ep_e($descricao) ?></textarea>
                            </div>

                            <div class="flex flex-col gap-1">
                                <label for="escopo" class="font-medium text-gray-700">Escopo</label>
                                <input
                                    type="file"
                                    id="escopo"
                                    name="escopo"
                                    accept=".pdf,.doc,.docx"
                                    required
                                    class="border border-gray-300 rounded p-2 bg-white"
                                >
                                <p class="text-sm text-gray-500">Formatos aceitos: PDF, DOC ou DOCX. Tamanho máximo: 8 MB.</p>
                            </div>

                            <div class="flex flex-col gap-1">
                                <label for="data_limite" class="font-medium text-gray-700">Data Limite</label>
                                <input
                                    type="date"
                                    id="data_limite"
                                    name="data_limite"
                                    value="<?= ep_e($dataLimite) ?>"
                                    required
                                    class="border border-gray-300 rounded p-2"
                                >
                            </div>
                
                            <button type="submit" class="bg-red-700 text-white font-medium py-2 px-4 rounded hover:bg-red-800 transition-colors cursor-pointer w-fit">
                                Cadastrar Demanda
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </main>
        </div>

    </div>
</body>
</html>

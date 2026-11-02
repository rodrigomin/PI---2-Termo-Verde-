<?php
require_once __DIR__ . '/app/bootstrap.php';

$erroPagina = '';
$erroEdicao = '';
$mensagemSucesso = '';
$parceiro = null;
$empresaId = null;
$tokenCsrf = '';
$nomeEmpresa = '';
$cnpj = '';
$razaoSocial = '';
$nomeContato = '';
$email = '';
$telefone = '';

try {
    $usuario = ep_exigir_login();
    $empresaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$empresaId || $empresaId < 1) {
        http_response_code(400);
        $erroPagina = 'Selecione um parceiro no Banco de Parceiros.';
    } else {
        $parceiro = ep_buscar_empresa_autorizada($empresaId, $usuario);
        if ($parceiro === null) {
            http_response_code(404);
            $erroPagina = 'Parceiro não encontrado ou sem permissão de edição.';
        } else {
            ep_iniciar_sessao();

            if (isset($_SESSION['parceiro_edicao_sucesso'])) {
                $retorno = $_SESSION['parceiro_edicao_sucesso'];
                unset($_SESSION['parceiro_edicao_sucesso']);
                if (is_array($retorno) && (int) ($retorno['id'] ?? 0) === $empresaId) {
                    $mensagemSucesso = 'Informações do parceiro atualizadas com sucesso.';
                }
            }

            $nomeEmpresa = (string) ($parceiro['nome_fantasia'] ?? '');
            $cnpj = (string) ($parceiro['CNPJ'] ?? '');
            $razaoSocial = (string) ($parceiro['razão_social'] ?? '');
            $nomeContato = (string) ($parceiro['contato_nome'] ?? '');
            $email = (string) ($parceiro['email_corporativo'] ?? '');
            $telefone = (string) ($parceiro['telefone'] ?? '');

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nomeEmpresa = ep_texto($_POST, 'nome_empresa');
                $cnpj = ep_texto($_POST, 'cnpj');
                $razaoSocial = ep_texto($_POST, 'razao_social');
                $nomeContato = ep_texto($_POST, 'nome_contato');
                $email = ep_texto($_POST, 'email');
                $telefone = ep_texto($_POST, 'telefone');

                if (!ep_validar_csrf($_POST['csrf_token'] ?? null)) {
                    $erroEdicao = 'Não foi possível validar o formulário. Atualize a página e tente novamente.';
                } else {
                    try {
                        ep_atualizar_parceiro($empresaId, $_POST, $usuario);
                        $_SESSION['parceiro_edicao_sucesso'] = ['id' => $empresaId];
                        ep_redirecionar('editar_parceiro.php?id=' . $empresaId);
                    } catch (InvalidArgumentException $erro) {
                        $erroEdicao = $erro->getMessage();
                    } catch (DomainException $erro) {
                        http_response_code(404);
                        $erroPagina = 'Parceiro não encontrado ou sem permissão de edição.';
                    }
                }
            }

            $tokenCsrf = ep_token_csrf();
        }
    }
} catch (Throwable $erro) {
    error_log('Erro ao editar parceiro: ' . $erro->getMessage());
    http_response_code(503);
    $erroPagina = 'Não foi possível editar o parceiro. Verifique o MySQL no XAMPP.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Parceiro - Escritório de Projetos</title>
    <script type="module" src='./js/index.js?v=<?php echo filemtime(__DIR__ . '/js/index.js'); ?>'> </script>
    <script type="module" src='./js/documentos.js?v=<?php echo filemtime(__DIR__ . '/js/documentos.js'); ?>'> </script>
    <link rel="stylesheet" href="./styles/styles.css?v=<?php echo filemtime(__DIR__ . '/styles/styles.css'); ?>"/>
    <link rel="stylesheet" href="./styles/documentos_parceiro.css?v=<?php echo filemtime(__DIR__ . '/styles/documentos_parceiro.css'); ?>"/>
</head>
<body>
    <section class='flex flex-row w-full'>    
        <div class='flex flex-col justify-start items-start' style='height: 100dvh; position: sticky; top: 0'>
            <?php include_once __DIR__ . '/components/nav.php'; ?>
        </div>
        <div class="flex w-full flex-col">
            <?php include_once __DIR__ . '/components/header.php'; ?>
            <main class="p-common">
                <section>
                    <h2 class="title">Editar Parceiro</h2>

                    <?php if ($erroPagina !== ''): ?>
                        <p role="alert"><?= ep_e($erroPagina) ?></p>
                    <?php else: ?>
                        <?php if ($mensagemSucesso !== ''): ?>
                            <p role="status"><?= ep_e($mensagemSucesso) ?></p>
                            <p><a href="sobre_parceiro.php?id=<?= (int) $empresaId ?>">Ver dados do parceiro</a></p>
                        <?php endif; ?>
                        <?php if ($erroEdicao !== ''): ?>
                            <p role="alert"><?= ep_e($erroEdicao) ?></p>
                        <?php endif; ?>
                    <div style="border: solid 3px #D9D9D9; padding: 0 35px; margin-top: 25px; box-sizing: border-box" class="rounded-lg relative">
                        <form action="editar_parceiro.php?id=<?= (int) $empresaId ?>" method="post" class="flex flex-col" style="margin: 50px 0; gap: 15px">
                            <input type="hidden" name="csrf_token" value="<?= ep_e($tokenCsrf) ?>">
                        
                            <div class="flex flex-col w-full">
                                <label class="bold" for="nome_empresa">Nome da Empresa</label>
                                <input type="text" id="nome_empresa" name="nome_empresa"
                                    placeholder="Nome da empresa" value="<?= ep_e($nomeEmpresa) ?>"
                                    maxlength="150" required>
                            </div>
                        
                            <div class="flex flex-col w-full">
                                <label class="bold" for="cnpj">CNPJ</label>
                                <input type="text" id="cnpj" name="cnpj"
                                    placeholder="00.000.000/0000-00" value="<?= ep_e($cnpj) ?>"
                                    maxlength="18" required>
                            </div>
                        
                            <div class="flex flex-col w-full">
                                <label class="bold" for="razao_social">Razão Social</label>
                                <input id="razao_social" name="razao_social" type="text" required value="<?= ep_e($razaoSocial) ?>" />
                            </div>
                        
                            <div class="flex flex-col w-full">
                                <label class="bold" for="nome_contato">Nome do Contato</label>
                                <input type="text" id="nome_contato" name="nome_contato"
                                    placeholder="Nome do representante" value="<?= ep_e($nomeContato) ?>"
                                    maxlength="45" required>
                            </div>
                        
                            <div class="flex flex-row w-full justify-between items-center" style="gap: 20px">
                                <div class="flex flex-col w-full">
                                    <label class="bold" for="email">Email Corporativo</label>
                                    <input type="email" id="email" name="email"
                                        placeholder="email@empresa.com" value="<?= ep_e($email) ?>"
                                        maxlength="45" required>
                                </div>

                                <div class="flex flex-col" style="width: 50%">
                                    <label class="bold" for="telefone">Telefone</label>
                                    <div class="flex flex-row items-center" style="gap: 5px">
                                        <span>+55</span>
                                        <input type="tel" id="telefone" name="telefone" class="w-full"
                                            placeholder="DDD + Número" value="<?= ep_e($telefone) ?>"
                                            maxlength="45" required>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col justify-center items-center" style="margin-top: 35px">
                                <div class="flex flex-col w-full" style="gap: 10px; width: 35%">   
                                    <button class="button interactive w-full" type="submit" style="padding: 20px 100px">Submeter Edição</button>
                                    <button class="button2 interactive w-full">
                                        <a style="text-decoration: none; color: #B71111" href="parceiros.php">Voltar ao Banco de Parceiros</a>
                                    </button>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </section>
</body>
</html>

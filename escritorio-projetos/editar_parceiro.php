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
</head>
<body>
    <header>
        <h1>Escritório de Projetos</h1>
    </header>

    <nav aria-label="Menu principal">
        <ul>
            <li><a href="visao_geral.php">Visão Geral</a></li>
            <li><a href="cadastro_demanda.php">Cadastro</a></li>
            <li><a href="parceiros.php" aria-current="page">Parceiros</a></li>
            <li><a href="envio_demandas.php">Envio Demandas</a></li>
        </ul>
        <p><a href="#">Conta</a></p>
    </nav>

    <main>
        <section>
            <h2>Editar Parceiro</h2>

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

                <form action="editar_parceiro.php?id=<?= (int) $empresaId ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= ep_e($tokenCsrf) ?>">

                    <div>
                        <label for="nome_empresa">Nome da Empresa</label>
                        <input type="text" id="nome_empresa" name="nome_empresa"
                            placeholder="Nome da empresa" value="<?= ep_e($nomeEmpresa) ?>"
                            maxlength="150" required>
                    </div>

                    <div>
                        <label for="cnpj">CNPJ</label>
                        <input type="text" id="cnpj" name="cnpj"
                            placeholder="00.000.000/0000-00" value="<?= ep_e($cnpj) ?>"
                            maxlength="18" required>
                    </div>

                    <div>
                        <label for="razao_social">Razão Social</label>
                        <textarea id="razao_social" name="razao_social"
                            rows="5" maxlength="150" required><?= ep_e($razaoSocial) ?></textarea>
                    </div>

                    <div>
                        <label for="nome_contato">Nome do Contato</label>
                        <input type="text" id="nome_contato" name="nome_contato"
                            placeholder="Nome do representante" value="<?= ep_e($nomeContato) ?>"
                            maxlength="45" required>
                    </div>

                    <div>
                        <label for="email">Email Corporativo</label>
                        <input type="email" id="email" name="email"
                            placeholder="email@empresa.com" value="<?= ep_e($email) ?>"
                            maxlength="45" required>
                    </div>

                    <div>
                        <label for="telefone">Telefone</label>
                        <span>+55</span>
                        <input type="tel" id="telefone" name="telefone"
                            placeholder="DDD + Número" value="<?= ep_e($telefone) ?>"
                            maxlength="45" required>
                    </div>

                    <button type="submit">Submeter Edição</button>
                </form>
            <?php endif; ?>

            <p><a href="parceiros.php">Voltar ao Banco de Parceiros</a></p>
        </section>
    </main>
</body>
</html>

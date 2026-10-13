<?php
require_once __DIR__ . '/app/bootstrap.php';

$erroPagina = '';
$erroEnvio = '';
$erroFiltro = '';
$mensagem = '';
$pesquisa = '';
$ordenacao = 'antigos';
$demandas = [];
$usuario = null;

try {
    $usuario = ep_exigir_login();
    ep_iniciar_sessao();

    if (isset($_SESSION['sucesso_encaminhamento'])) {
        $mensagem = (string) $_SESSION['sucesso_encaminhamento'];
        unset($_SESSION['sucesso_encaminhamento']);
    }

    $origem = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    $pesquisa = ep_texto($origem, 'pesquisa');
    if (strlen($pesquisa) > 150) {
        $pesquisa = '';
        $erroFiltro = 'A pesquisa deve ter no máximo 150 caracteres.';
    }

    $ordenacaoInformada = ep_texto($origem, 'ordenacao');
    if (in_array($ordenacaoInformada, ['antigos', 'recentes'], true)) {
        $ordenacao = $ordenacaoInformada;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $propostaId = filter_input(INPUT_POST, 'id_demanda', FILTER_VALIDATE_INT);

        try {
            if (!ep_validar_csrf($_POST['csrf_token'] ?? null)) {
                throw new DomainException('O formulário expirou. Atualize a página e tente novamente.');
            }
            if (!$propostaId || $propostaId < 1) {
                throw new InvalidArgumentException('Selecione uma demanda válida.');
            }

            ep_encaminhar_demanda($propostaId, $usuario);
            $_SESSION['sucesso_encaminhamento'] = 'Demanda encaminhada ao CEPE e marcada como Submetido.';
            ep_redirecionar('envio_demandas.php?' . http_build_query([
                'pesquisa' => $pesquisa,
                'ordenacao' => $ordenacao,
            ], '', '&', PHP_QUERY_RFC3986));
        } catch (InvalidArgumentException | DomainException $erro) {
            $erroEnvio = $erro->getMessage();
        }
    }

    $empresaId = $usuario['perfil'] === 'administrador'
        ? null
        : (int) ($usuario['empresa_id'] ?? 0);

    $demandas = ep_listar_demandas_para_envio($pesquisa, $ordenacao, $empresaId);
    $tokenCsrf = ep_token_csrf();
} catch (Throwable $erro) {
    error_log('Erro no Envio de Demandas: ' . $erro->getMessage());
    http_response_code(503);
    $erroPagina = 'Não foi possível carregar as demandas. Verifique o MySQL no XAMPP.';
}

function ep_data_envio_tabela($data): string
{
    if (!is_string($data) || $data === '') {
        return '—';
    }
    $dataObj = DateTimeImmutable::createFromFormat('!Y-m-d', substr($data, 0, 10));
    return $dataObj ? $dataObj->format('d/m/Y') : '—';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envio de Demandas - Escritório de Projetos</title>
</head>
<body>
    <header>
        <h1>Escritório de Projetos</h1>
    </header>

    <nav aria-label="Menu principal">
        <ul>
            <li><a href="visao_geral.php">Visão Geral</a></li>
            <li><a href="cadastro_demanda.php">Cadastro</a></li>
            <li><a href="parceiros.php">Parceiros</a></li>
            <li><a href="envio_demandas.php" aria-current="page">Envio Demandas</a></li>
        </ul>
        <p><a href="#">Conta</a></p>
    </nav>

    <main>
        <h2>Envio de Demandas</h2>

        <?php if ($erroPagina !== ''): ?>
            <p role="alert"><?= ep_e($erroPagina) ?></p>
        <?php else: ?>
            <?php if ($mensagem !== ''): ?>
                <p role="status"><?= ep_e($mensagem) ?></p>
            <?php endif; ?>
            <?php if ($erroEnvio !== ''): ?>
                <p role="alert"><?= ep_e($erroEnvio) ?></p>
            <?php endif; ?>
            <?php if ($erroFiltro !== ''): ?>
                <p role="alert"><?= ep_e($erroFiltro) ?></p>
            <?php endif; ?>

            <section>
                <form action="envio_demandas.php" method="get">
                    <div>
                        <label for="pesquisa">Pesquisar Demanda por Nome</label>
                        <input
                            type="search"
                            id="pesquisa"
                            name="pesquisa"
                            placeholder="EX: Projeto Jacto"
                            maxlength="150"
                            value="<?= ep_e($pesquisa) ?>"
                        >
                    </div>
                    <div>
                        <label for="ordenacao">Filtrar por</label>
                        <select id="ordenacao" name="ordenacao">
                            <option value="antigos" <?= $ordenacao === 'antigos' ? 'selected' : '' ?>>
                                Mais Antigos
                            </option>
                            <option value="recentes" <?= $ordenacao === 'recentes' ? 'selected' : '' ?>>
                                Mais Recentes
                            </option>
                        </select>
                    </div>
                    <button type="submit">Pesquisar</button>
                </form>
            </section>

            <section aria-label="Demandas disponíveis para envio">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Empresa</th>
                            <th scope="col">Título e Descrição</th>
                            <th scope="col">Data</th>
                            <th scope="col">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($demandas)): ?>
                            <tr>
                                <td colspan="4">Nenhuma demanda disponível para envio.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($demandas as $demanda): ?>
                                <tr>
                                    <td><?= ep_e($demanda['nome_fantasia'] ?? 'Empresa não informada') ?></td>
                                    <td>
                                        <strong><?= ep_e($demanda['título']) ?></strong>
                                        <p><?= ep_e($demanda['descricão_problema']) ?></p>
                                    </td>
                                    <td><?= ep_e(ep_data_envio_tabela($demanda['data_cadastro'])) ?></td>
                                    <td>
                                        <form action="envio_demandas.php" method="post">
                                            <input type="hidden" name="csrf_token" value="<?= ep_e($tokenCsrf) ?>">
                                            <input type="hidden" name="id_demanda" value="<?= (int) $demanda['proposta_id'] ?>">
                                            <input type="hidden" name="pesquisa" value="<?= ep_e($pesquisa) ?>">
                                            <input type="hidden" name="ordenacao" value="<?= ep_e($ordenacao) ?>">
                                            <button type="submit">Enviar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>

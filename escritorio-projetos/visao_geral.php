<?php
require_once __DIR__ . '/app/bootstrap.php';

$erroPagina = '';
$erroFiltro = '';
$usuario = null;
$indicadores = [];
$statusUltimoMes = [];
$statusDisponiveis = [];
$demandas = [];
$pesquisa = '';
$filtroStatus = '';
$paginaAtual = 1;
$totalPaginas = 0;
$totalResultados = 0;
$empresasCadastradas = 0;

try {
    $usuario = ep_exigir_login();

    // adms visualizam o conjunto; representantes veem apenas a empresa vinculada.
    $empresaId = $usuario['perfil'] === 'administrador'
        ? null
        : (int) ($usuario['empresa_id'] ?? 0);

    $pesquisa = ep_texto($_GET, 'pesquisa');
    if (strlen($pesquisa) > 150) {
        $pesquisa = '';
        $erroFiltro = 'O texto da pesquisa deve ter no máximo 150 caracteres.';
    }

    $filtroStatus = ep_texto($_GET, 'status');
    $statusDisponiveis = ep_status_demandas_disponiveis($empresaId);
    if ($filtroStatus !== '' && !in_array($filtroStatus, $statusDisponiveis, true)) {
        $filtroStatus = '';
    }

    $paginaInformada = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);
    if ($paginaInformada !== false && $paginaInformada !== null && $paginaInformada > 0) {
        $paginaAtual = $paginaInformada;
    }

    $indicadores = ep_indicadores_visao_geral($empresaId);
    $statusUltimoMes = ep_status_demandas_ultimo_mes($empresaId);
    $empresasCadastradas = $empresaId === null
        ? ep_total_empresas()
        : ($empresaId > 0 ? 1 : 0);

    $totalResultados = ep_total_demandas_visao_geral(
        $pesquisa,
        $filtroStatus,
        $empresaId
    );
    $porPagina = 8;
    $totalPaginas = (int) ceil($totalResultados / $porPagina);
    if ($totalPaginas > 0) {
        $paginaAtual = min($paginaAtual, $totalPaginas);
    } else {
        $paginaAtual = 1;
    }

    $demandas = ep_listar_demandas_visao_geral(
        $pesquisa,
        $filtroStatus,
        $empresaId,
        $paginaAtual,
        $porPagina
    );
} catch (Throwable $erro) {
    error_log('Erro na Visão Geral: ' . $erro->getMessage());
    http_response_code(503);
    $erroPagina = 'Não foi possível carregar a Visão Geral. Verifique o MySQL no XAMPP.';
}

function ep_url_visao_geral(int $pagina, string $pesquisa, string $status): string
{
    return 'visao_geral.php?' . http_build_query([
        'pesquisa' => $pesquisa,
        'status' => $status,
        'pagina' => $pagina,
    ], '', '&', PHP_QUERY_RFC3986);
}

function ep_numero_painel($numero): string
{
    return number_format((int) $numero, 0, ',', '.');
}

function ep_percentual_painel($numero): string
{
    return number_format((float) $numero, 1, ',', '.') . '%';
}

function ep_data_painel($data): string
{
    if (!is_string($data) || $data === '') {
        return '—';
    }
    $objeto = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data);
    return $objeto ? $objeto->format('d/m/Y') : '—';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visão Geral - Escritório de Projetos</title>
</head>
<body>

    <header>
        <h1>Escritório de Projetos</h1>
    </header>

    <nav aria-label="Menu principal">
        <ul>
            <li><a href="visao_geral.php" aria-current="page">Visão Geral</a></li>
            <li><a href="cadastro_demanda.php">Cadastro</a></li>
            <li><a href="parceiros.php">Parceiros</a></li>
            <li><a href="envio_demandas.php">Envio Demandas</a></li>
        </ul>
        <p><a href="#">Conta</a></p>
    </nav>

    <main>
        <?php if ($erroPagina !== ''): ?>
            <p role="alert"><?= ep_e($erroPagina) ?></p>
        <?php else: ?>
            <section>
                <h2>Olá, <?= ep_e($usuario['nome']) ?>!</h2>
                <p>Seja bem vindo(a) ao Escritório de Projetos.</p>
            </section>

            <section>
                <h2>KPI's</h2>
                <p>Como estamos agora?</p>

                <section>
                    <h3>Status das Demandas</h3>
                    <p>Último Mês (últimos 30 dias)</p>

                    <?php if (empty($statusUltimoMes)): ?>
                        <p>Nenhuma demanda cadastrada nos últimos 30 dias.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($statusUltimoMes as $item): ?>
                                <li>
                                    <?= ep_e($item['nome']) ?>:
                                    <?= ep_numero_painel($item['quantidade']) ?>
                                    (<?= ep_percentual_painel($item['percentual']) ?>)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <section>
                    <h3>Demandas em Curso</h3>
                    <p><?= ep_percentual_painel($indicadores['percentual_execucao']) ?></p>
                    <p>
                        <?= ep_numero_painel($indicadores['execucao']) ?> das
                        <?= ep_numero_painel($indicadores['total']) ?> demandas
                        estão em execução.
                    </p>
                </section>

                <section>
                    <h3>Demandas Cadastradas</h3>
                    <p><?= ep_numero_painel($indicadores['total']) ?> demandas cadastradas</p>
                </section>

                <section>
                    <h3>Empresas Cadastradas</h3>
                    <p><?= ep_numero_painel($empresasCadastradas) ?> empresas cadastradas</p>
                </section>

                <section>
                    <h3>Resumo das Demandas</h3>
                    <ul>
                        <li><?= ep_numero_painel($indicadores['triagem']) ?> demandas em triagem</li>
                        <li><?= ep_numero_painel($indicadores['execucao']) ?> demandas em execução</li>
                        <li><?= ep_numero_painel($indicadores['submetidas']) ?> demandas submetidas</li>
                    </ul>
                </section>
            </section>

            <section>
                <h2>Situação das Demandas Enviadas ao CEPE</h2>

                <?php if ($erroFiltro !== ''): ?>
                    <p role="alert"><?= ep_e($erroFiltro) ?></p>
                <?php endif; ?>

                <form action="visao_geral.php" method="get">
                    <div>
                        <label for="pesquisa">Pesquisar Demanda por Nome</label>
                        <input
                            type="search"
                            id="pesquisa"
                            name="pesquisa"
                            placeholder="EX: Projeto Jacto"
                            value="<?= ep_e($pesquisa) ?>"
                            maxlength="150"
                        >
                    </div>

                    <div>
                        <label for="status">Filtrar por</label>
                        <select id="status" name="status">
                            <option value="">Todos os status</option>
                            <?php foreach ($statusDisponiveis as $status): ?>
                                <option
                                    value="<?= ep_e($status) ?>"
                                    <?= $filtroStatus === $status ? 'selected' : '' ?>
                                ><?= ep_e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit">Pesquisar</button>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th scope="col">Empresa</th>
                            <th scope="col">Título e Descrição</th>
                            <th scope="col">Data</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($demandas)): ?>
                            <tr>
                                <td colspan="4">Nenhuma demanda encontrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($demandas as $demanda): ?>
                                <tr>
                                    <td>
                                        <?= ep_e($demanda['nome_fantasia'] ?? 'Empresa não informada') ?>
                                    </td>
                                    <td>
                                        <strong><?= ep_e($demanda['título'] ?? '') ?></strong>
                                        <p><?= ep_e($demanda['descricão_problema'] ?? '') ?></p>
                                    </td>
                                    <td><?= ep_e(ep_data_painel($demanda['data_cadastro'] ?? null)) ?></td>
                                    <td><?= ep_e($demanda['status'] ?: 'Sem status') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($totalPaginas > 1): ?>
                    <nav aria-label="Paginação das demandas">
                        <p>Páginas:</p>
                        <?php if ($paginaAtual > 1): ?>
                            <a href="<?= ep_e(ep_url_visao_geral($paginaAtual - 1, $pesquisa, $filtroStatus)) ?>">
                                Anterior
                            </a>
                        <?php endif; ?>

                        <?php for ($pagina = 1; $pagina <= $totalPaginas; $pagina++): ?>
                            <a
                                href="<?= ep_e(ep_url_visao_geral($pagina, $pesquisa, $filtroStatus)) ?>"
                                <?= $pagina === $paginaAtual ? 'aria-current="page"' : '' ?>
                            ><?= $pagina ?></a>
                        <?php endfor; ?>

                        <?php if ($paginaAtual < $totalPaginas): ?>
                            <a href="<?= ep_e(ep_url_visao_geral($paginaAtual + 1, $pesquisa, $filtroStatus)) ?>">
                                Próxima
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <footer>
        <p>Escritório de Projetos</p>
    </footer>

</body>
</html>

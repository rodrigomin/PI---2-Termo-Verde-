<?php
require_once __DIR__ . '/app/bootstrap.php';

$erroPagina = '';
$erroPesquisa = '';
$parceiro = null;
$empresaId = null;
$propostaId = null;
$proposta = null;
$pesquisa = '';
$ordenacao = 'recentes';
$pagina = 1;
$totalPaginas = 0;
$convenios = [];
$documentos = [];

try {
    $usuario = ep_exigir_login();
    $empresaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $propostaInformada = array_key_exists('proposta_id', $_GET);
    $propostaId = $propostaInformada
        ? filter_input(INPUT_GET, 'proposta_id', FILTER_VALIDATE_INT)
        : null;

    if (!$empresaId || $empresaId < 1
        || ($propostaInformada && (!$propostaId || $propostaId < 1))) {
        http_response_code(400);
        $erroPagina = 'Selecione um parceiro ou proposta válida.';
    } else {
        $parceiro = ep_buscar_empresa_autorizada($empresaId, $usuario);
        if ($parceiro === null) {
            http_response_code(404);
            $erroPagina = 'Parceiro não encontrado ou sem permissão de acesso.';
        } elseif ($propostaId !== null
            && ($proposta = ep_buscar_proposta_parceiro($empresaId, $propostaId)) === null) {
            http_response_code(404);
            $erroPagina = 'Proposta não encontrada para este parceiro.';
        } else {
            $pesquisa = ep_texto($_GET, 'pesquisa');
            if (ep_comprimento_utf8($pesquisa) > 150) {
                $pesquisa = '';
                $erroPesquisa = 'Pesquise por um título de até 150 caracteres.';
            }

            $ordenacao = ep_texto($_GET, 'ordenacao') ?: 'recentes';
            if (!in_array($ordenacao, ['recentes', 'antigos'], true)) {
                $ordenacao = 'recentes';
                $erroPesquisa = 'Ordenação inválida. Exibindo os mais recentes.';
            }

            $paginaSolicitada = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);
            $pagina = ($paginaSolicitada && $paginaSolicitada > 0) ? $paginaSolicitada : 1;
            $porPagina = 2;

            $convenios = ep_listar_convenios_parceiro($empresaId, $propostaId);
            $totalDocumentos = ep_total_documentos_parceiro($empresaId, $propostaId, $pesquisa);
            $totalPaginas = (int) ceil($totalDocumentos / $porPagina);
            if ($totalPaginas > 0) {
                $pagina = min($pagina, $totalPaginas);
            }

            $documentos = ep_listar_documentos_parceiro(
                $empresaId,
                $propostaId,
                $pesquisa,
                $ordenacao,
                $pagina,
                $porPagina
            );
        }
    }
} catch (Throwable $erro) {
    error_log('Erro ao consultar documentos do parceiro: ' . $erro->getMessage());
    http_response_code(503);
    $erroPagina = 'Não foi possível consultar os documentos. Verifique o MySQL no XAMPP.';
}

function ep_data_documento($valor): string
{
    if (!is_string($valor) || trim($valor) === '') {
        return 'Não informada';
    }
    try {
        return (new DateTimeImmutable($valor))->format('d/m/Y');
    } catch (Throwable $erro) {
        return 'Não informada';
    }
}

function ep_url_pagina_documentos(
    int $pagina,
    int $empresaId,
    ?int $propostaId,
    string $pesquisa,
    string $ordenacao
): string {
    $parametros = [
        'id' => $empresaId,
        'pesquisa' => $pesquisa,
        'ordenacao' => $ordenacao,
        'pagina' => $pagina,
    ];
    if ($propostaId !== null) {
        $parametros['proposta_id'] = $propostaId;
    }
    return 'documentos_parceiro.php?' . http_build_query(
        $parametros, '', '&', PHP_QUERY_RFC3986
    );
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos do Parceiro - Escritório de Projetos</title>
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
        <h2>Documentos - <?= $parceiro !== null
            ? '“' . ep_e($parceiro['nome_fantasia'] ?: ($parceiro['razão_social'] ?: 'Nome não informado')) . '”'
            : 'Parceiro' ?></h2>

        <?php if ($erroPagina !== ''): ?>
            <p role="alert"><?= ep_e($erroPagina) ?></p>
        <?php else: ?>
            <?php if ($proposta !== null): ?>
                <p>Proposta: <?= ep_e($proposta['título'] ?: 'Sem título') ?></p>
            <?php endif; ?>

            <section aria-labelledby="titulo-convenios">
                <h3 id="titulo-convenios">Convênios</h3>
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Nº Contrato</th>
                            <th scope="col">Data da Assinatura</th>
                            <th scope="col">Fim da Vigência</th>
                            <th scope="col">Arquivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($convenios)): ?>
                            <tr><td colspan="4">Nenhum convênio disponível para exibição.</td></tr>
                        <?php else: ?>
                            <?php foreach ($convenios as $convenio): ?>
                                <tr>
                                    <td>
                                        <?= ep_e($convenio['número_contrato'] ?? 'Não informado') ?>
                                        <p><?= ep_e($convenio['situacao'] ?: 'Situação não informada') ?></p>
                                    </td>
                                    <td><?= ep_e(ep_data_documento($convenio['data_assinatura'])) ?></td>
                                    <td><?= ep_e(ep_data_documento($convenio['vigência_fim'])) ?></td>
                                    <td>
                                        <?php if (ep_localizar_documento_privado(
                                            $convenio['arquivo_contrato_path'] ?? null, 'convenio'
                                        ) !== null): ?>
                                            <a href="abrir_documento.php?tipo=convenio&amp;id=<?= (int) $convenio['convênio_id'] ?>">Abrir Arquivo</a>
                                        <?php else: ?>
                                            Arquivo indisponível
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section aria-labelledby="titulo-acompanhamento">
                <h3 id="titulo-acompanhamento">Arquivos de Acompanhamento</h3>

                <?php if ($erroPesquisa !== ''): ?>
                    <p role="alert"><?= ep_e($erroPesquisa) ?></p>
                <?php endif; ?>

                <form action="documentos_parceiro.php" method="get">
                    <input type="hidden" name="id" value="<?= (int) $empresaId ?>">
                    <?php if ($propostaId !== null): ?>
                        <input type="hidden" name="proposta_id" value="<?= (int) $propostaId ?>">
                    <?php endif; ?>
                    <div>
                        <label for="pesquisa">Pesquisar por título</label>
                        <input
                            type="search"
                            id="pesquisa"
                            name="pesquisa"
                            placeholder="EX: Arquivo 16-10"
                            maxlength="150"
                            value="<?= ep_e($pesquisa) ?>"
                        >
                    </div>
                    <div>
                        <label for="ordenacao">Filtrar por</label>
                        <select id="ordenacao" name="ordenacao">
                            <option value="recentes" <?= $ordenacao === 'recentes' ? 'selected' : '' ?>>Mais Recentes</option>
                            <option value="antigos" <?= $ordenacao === 'antigos' ? 'selected' : '' ?>>Mais Antigos</option>
                        </select>
                    </div>
                    <button type="submit">Pesquisar</button>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th scope="col">Título do Arquivo</th>
                            <th scope="col">Data do Envio</th>
                            <th scope="col">Arquivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documentos)): ?>
                            <tr><td colspan="3">Nenhum arquivo de acompanhamento disponível.</td></tr>
                        <?php else: ?>
                            <?php foreach ($documentos as $documento): ?>
                                <tr>
                                    <td><?= ep_e($documento['título'] ?: 'Sem título') ?></td>
                                    <td><?= ep_e(ep_data_documento($documento['data_envio'])) ?></td>
                                    <td>
                                        <?php if (ep_localizar_documento_privado(
                                            $documento['arquivo_path'] ?? null, 'acompanhamento'
                                        ) !== null): ?>
                                            <a href="abrir_documento.php?tipo=acompanhamento&amp;id=<?= (int) $documento['documento_id'] ?>">Abrir Arquivo</a>
                                        <?php else: ?>
                                            Arquivo indisponível
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($totalPaginas > 1): ?>
                    <nav aria-label="Paginação dos arquivos">
                        <?php if ($pagina > 1): ?>
                            <a href="<?= ep_e(ep_url_pagina_documentos(
                                $pagina - 1, $empresaId, $propostaId, $pesquisa, $ordenacao
                            )) ?>">Anterior</a>
                        <?php endif; ?>

                        <?php for ($numero = 1; $numero <= $totalPaginas; $numero++): ?>
                            <a
                                href="<?= ep_e(ep_url_pagina_documentos(
                                    $numero, $empresaId, $propostaId, $pesquisa, $ordenacao
                                )) ?>"
                                <?= $numero === $pagina ? 'aria-current="page"' : '' ?>
                            ><?= $numero ?></a>
                        <?php endfor; ?>

                        <?php if ($pagina < $totalPaginas): ?>
                            <a href="<?= ep_e(ep_url_pagina_documentos(
                                $pagina + 1, $empresaId, $propostaId, $pesquisa, $ordenacao
                            )) ?>">Próxima</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <p>
            <a href="<?= $empresaId !== null && $parceiro !== null
                ? 'sobre_parceiro.php?id=' . (int) $empresaId
                : 'parceiros.php' ?>">Voltar ao Parceiro</a>
        </p>
    </main>
</body>
</html>

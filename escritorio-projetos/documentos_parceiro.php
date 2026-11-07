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
    <script type="module" src='./js/index.js?v=<?php echo filemtime(__DIR__ . '/js/index.js'); ?>'> </script>
    <script type="module" src='./js/documentos.js?v=<?php echo filemtime(__DIR__ . '/js/documentos.js'); ?>'> </script>
    <link rel="stylesheet" href="./styles/styles.css?v=<?php echo filemtime(__DIR__ . '/styles/styles.css'); ?>"/>
    <link rel="stylesheet" href="./styles/documentos_parceiro.css?v=<?php echo filemtime(__DIR__ . '/styles/documentos_parceiro.css'); ?>"/>
</head>
<body class="relative">

    <?php include_once __DIR__ . '/components/modal_doc.php'; ?>
    <section class='flex flex-row w-full'>    
        <div class='flex flex-col justify-start items-start' style='height: 100dvh; position: sticky; top: 0'>
            <?php include_once __DIR__ . '/components/nav.php'; ?>
        </div>
        <div class="flex w-full flex-col">
                        <?php include_once __DIR__ . '/components/header.php'; ?>
                        <h2 class='p-common title'>Documentos - <?= $parceiro !== null
                                ? '“' . ep_e($parceiro['nome_fantasia'] ?: ($parceiro['razão_social'] ?: 'Nome não informado')) . '”'
                                : 'Parceiro' ?></h2>
                        <main style="border: solid 3px #D9D9D9; margin: 50px; margin-top: 0px; gap: 100px; padding-top: 50px" class="flex flex-col rounded-lg relative p-6">

                            <section aria-labelledby="titulo-convenios" class="flex flex-col" style="gap: 25px; margin-top: 35px">
                                <?php if ($erroPagina !== ''): ?>
                            <p role="alert"><?= ep_e($erroPagina) ?></p>
                        <?php else: ?>
                            <?php if ($proposta !== null): ?>
                                <p class="title bold">Proposta: <?= ep_e($proposta['título'] ?: 'Sem título') ?></p>
                            <?php endif; ?>

                        <h3 id="titulo-convenios">Convênios</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col" style="text-align: left; align-items: start">Nº Contrato</th>
                                    <th scope="col">Data da Assinatura</th>
                                    <th scope="col">Fim da Vigência</th>
                                    <th scope="col">Arquivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($convenios)): ?>
                                    <tr style="height: 100px; background-color: #F2F2F2" class="rounded-xl">
                                        <td colspan='1' style="text-align: left; align-items: start">
                                            <p class="bold">N° Contrato</p>
                                            <p class="text-2xl" style="color: red">Submetida</p>
                                        </td>
                                        <td colspan='1' class="bold">12/08/2026</td>
                                        <td colspan='1' class="bold">02/05/2028</td>
                                        <td colspan='1'>
                                            <div class="button2 flex interactive flex-row items-center justify-center doc-btn" style="gap: 6px">
                                                <svg width='25' height='25' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="rgb(183, 17, 17)" d="M128 96C110.3 96 96 110.3 96 128L96 224C96 241.7 110.3 256 128 256C145.7 256 160 241.7 160 224L160 160L224 160C241.7 160 256 145.7 256 128C256 110.3 241.7 96 224 96L128 96zM160 416C160 398.3 145.7 384 128 384C110.3 384 96 398.3 96 416L96 512C96 529.7 110.3 544 128 544L224 544C241.7 544 256 529.7 256 512C256 494.3 241.7 480 224 480L160 480L160 416zM416 96C398.3 96 384 110.3 384 128C384 145.7 398.3 160 416 160L480 160L480 224C480 241.7 494.3 256 512 256C529.7 256 544 241.7 544 224L544 128C544 110.3 529.7 96 512 96L416 96zM544 416C544 398.3 529.7 384 512 384C494.3 384 480 398.3 480 416L480 480L416 480C398.3 480 384 494.3 384 512C384 529.7 398.3 544 416 544L512 544C529.7 544 544 529.7 544 512L544 416z"/></svg>
                                                <a>Abrir Arquivo</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <!--
                                    <tr><td colspan="4">Nenhum convênio disponível para exibição.</td></tr>
                                    -->
                                <?php else: ?>
                                    <?php foreach ($convenios as $convenio): ?>
                                        <tr style="height: 100px; background-color: #F2F2F2" class="rounded-xl">
                                            <td colspan='1' style="text-align: left; align-items: start">
                                                <p class="bold"><?= ep_e($convenio['número_contrato'] ?? 'Não informado') ?> </p>
                                                <p class="text-2xl" style="color: red"><?= ep_e($convenio['situacao'] ?: 'Situação não informada') ?></p>
                                            </td>
                                            <td colspan='1' class="bold"><?= ep_e(ep_data_documento($convenio['data_assinatura'])) ?></td>
                                            <td colspan='1' class="bold"><?= ep_e(ep_data_documento($convenio['vigência_fim'])) ?></td>
                                            <td colspan='1'>
                                                <div class="doc-btn button2 interactive flex flex-row items-center justify-center" style="gap: 6px">
                                                    <?php if (ep_localizar_documento_privado(
                                                        $convenio['arquivo_contrato_path'] ?? null, 'convenio'
                                                    ) !== null): ?>
                                                        <svg width='25' height='25' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="rgb(183, 17, 17)" d="M128 96C110.3 96 96 110.3 96 128L96 224C96 241.7 110.3 256 128 256C145.7 256 160 241.7 160 224L160 160L224 160C241.7 160 256 145.7 256 128C256 110.3 241.7 96 224 96L128 96zM160 416C160 398.3 145.7 384 128 384C110.3 384 96 398.3 96 416L96 512C96 529.7 110.3 544 128 544L224 544C241.7 544 256 529.7 256 512C256 494.3 241.7 480 224 480L160 480L160 416zM416 96C398.3 96 384 110.3 384 128C384 145.7 398.3 160 416 160L480 160L480 224C480 241.7 494.3 256 512 256C529.7 256 544 241.7 544 224L544 128C544 110.3 529.7 96 512 96L416 96zM544 416C544 398.3 529.7 384 512 384C494.3 384 480 398.3 480 416L480 480L416 480C398.3 480 384 494.3 384 512C384 529.7 398.3 544 416 544L512 544C529.7 544 544 529.7 544 512L544 416z"/></svg>
                                                        <a href="abrir_documento.php?tipo=convenio&amp;id=<?= (int) $convenio['convênio_id'] ?>">Abrir Arquivo</a>
                                                    <?php else: ?>
                                                        Arquivo indisponível
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </section>

                    <section aria-labelledby="titulo-acompanhamento" class="w-full flex flex-col" style="gap: 15px">
                        <h3 id="titulo-acompanhamento" class="title">Arquivos de Acompanhamento</h3>

                        <?php if ($erroPesquisa !== ''): ?>
                            <p role="alert"><?= ep_e($erroPesquisa) ?></p>
                        <?php endif; ?>

                        <form action="documentos_parceiro.php" method="get">
                            <input type="hidden" name="id" value="<?= (int) $empresaId ?>">
                            <?php if ($propostaId !== null): ?>
                                <input type="hidden" name="proposta_id" value="<?= (int) $propostaId ?>">
                            <?php endif; ?>
                            <div class="flex flex-row w-full justify-between items-center" style="gap: 50px">
                                <div class="w-full flex flex-col">
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
                            
                                <div class="flex flex-col w-full">
                                    <label for="ordenacao">Filtrar por</label>
                                    <select id="ordenacao" name="ordenacao">
                                        <option value="recentes" <?= $ordenacao === 'recentes' ? 'selected' : '' ?>>Mais Recentes</option>
                                        <option value="antigos" <?= $ordenacao === 'antigos' ? 'selected' : '' ?>>Mais Antigos</option>
                                    </select>
                                </div>
                            
                                <button class="button interactive" type="submit">Pesquisar</button>
                            </div>
                        </form>

                        <table>
                            <thead>
                                <tr style="margin: 15px 0">
                                    <th scope="col" style="text-align: left; align-items: start">Título do Arquivo</th>
                                    <th scope="col">Data do Envio</th>
                                    <th scope="col">Arquivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documentos)): ?>
                                    <tr style="min-height: 75px; background-color: #F2F2F2; padding: 15px 0" class="rounded-xl">
                                        <td colspan='1' class="bold" style="text-align: left; align-items: start">Título</td>
                                        <td colspan='1' class="bold">12/12/12</td>
                                        <td colspan='1'>
                                            <div class="doc-btn button2 interactive flex flex-row items-center justify-center" style="gap: 6px">
                                                <svg width='25' height='25' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="rgb(183, 17, 17)" d="M128 96C110.3 96 96 110.3 96 128L96 224C96 241.7 110.3 256 128 256C145.7 256 160 241.7 160 224L160 160L224 160C241.7 160 256 145.7 256 128C256 110.3 241.7 96 224 96L128 96zM160 416C160 398.3 145.7 384 128 384C110.3 384 96 398.3 96 416L96 512C96 529.7 110.3 544 128 544L224 544C241.7 544 256 529.7 256 512C256 494.3 241.7 480 224 480L160 480L160 416zM416 96C398.3 96 384 110.3 384 128C384 145.7 398.3 160 416 160L480 160L480 224C480 241.7 494.3 256 512 256C529.7 256 544 241.7 544 224L544 128C544 110.3 529.7 96 512 96L416 96zM544 416C544 398.3 529.7 384 512 384C494.3 384 480 398.3 480 416L480 480L416 480C398.3 480 384 494.3 384 512C384 529.7 398.3 544 416 544L512 544C529.7 544 544 529.7 544 512L544 416z"/></svg>
                                                <a>Abrir Arquivo</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <!--
                                    <tr><td colspan="3">Nenhum arquivo de acompanhamento disponível.</td></tr>
                                    -->
                                <?php else: ?>
                                    <?php foreach ($documentos as $documento): ?>
                                        <tr style="min-height: 75px; background-color: #F2F2F2; padding: 15px 0" class="rounded-xl">
                                            <td colspan='1' class="bold" style="text-align: left; align-items: start"><?= ep_e($documento['título'] ?: 'Sem título') ?></td>
                                            <td colspan='1' class="bold"><?= ep_e(ep_data_documento($documento['data_envio'])) ?></td>
                                            <td colspan='1'>
                                                <?php if (ep_localizar_documento_privado(
                                                    $documento['arquivo_path'] ?? null, 'acompanhamento'
                                                ) !== null): ?>
                                                <div class="doc-btn button2 interactive flex flex-row items-center justify-center" style="gap: 6px">
                                                    <svg width='25' height='25' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path fill="rgb(183, 17, 17)" d="M128 96C110.3 96 96 110.3 96 128L96 224C96 241.7 110.3 256 128 256C145.7 256 160 241.7 160 224L160 160L224 160C241.7 160 256 145.7 256 128C256 110.3 241.7 96 224 96L128 96zM160 416C160 398.3 145.7 384 128 384C110.3 384 96 398.3 96 416L96 512C96 529.7 110.3 544 128 544L224 544C241.7 544 256 529.7 256 512C256 494.3 241.7 480 224 480L160 480L160 416zM416 96C398.3 96 384 110.3 384 128C384 145.7 398.3 160 416 160L480 160L480 224C480 241.7 494.3 256 512 256C529.7 256 544 241.7 544 224L544 128C544 110.3 529.7 96 512 96L416 96zM544 416C544 398.3 529.7 384 512 384C494.3 384 480 398.3 480 416L480 480L416 480C398.3 480 384 494.3 384 512C384 529.7 398.3 544 416 544L512 544C529.7 544 544 529.7 544 512L544 416z"/></svg>
                                                    <a href="abrir_documento.php?tipo=acompanhamento&amp;id=<?= (int) $documento['documento_id'] ?>">Abrir Arquivo</a>
                                                </div>
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
                            <nav aria-label="Paginação dos arquivos" class="flex flex-row items-center justify-center" style="gap: 25px; margin: 25px 0">
                                <div class="relative flex flex-row p-6 items-center" style="gap: 20px">
                                    <?php if ($pagina > 1): ?>
                                        <a href="<?= ep_e(ep_url_pagina_documentos(
                                            $pagina - 1, $empresaId, $propostaId, $pesquisa, $ordenacao
                                        )) ?>">
                                            <svg style="aspect-ratio: 1/1; width: 35px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                                <path fill="rgb(181, 16, 16)" d="M169.4 297.4C156.9 309.9 156.9 330.2 169.4 342.7L361.4 534.7C373.9 547.2 394.2 547.2 406.7 534.7C419.2 522.2 419.2 501.9 406.7 489.4L237.3 320L406.6 150.6C419.1 138.1 419.1 117.8 406.6 105.3C394.1 92.8 373.8 92.8 361.3 105.3L169.3 297.3z"/>
                                            </svg>
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($numero = 1; $numero <= $totalPaginas; $numero++): ?>
                                        <a class="flex flex-col items-center text-center subtitle underline"
                                            href="<?= ep_e(ep_url_pagina_documentos(
                                                $numero, $empresaId, $propostaId, $pesquisa, $ordenacao
                                            )) ?>"
                                            <?= $numero === $pagina ? 'aria-current="page"' : '' ?>
                                        ><?= $numero ?></a>
                                    <?php endfor; ?>

                                    <?php if ($pagina < $totalPaginas): ?>
                                        <a href="<?= ep_e(ep_url_pagina_documentos(
                                            $pagina + 1, $empresaId, $propostaId, $pesquisa, $ordenacao
                                        )) ?>">
                                            <svg style="aspect-ratio: 1/1; width: 35px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                                <path fill="rgb(181, 16, 16)" d="M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z"/>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </nav>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <div class="p-common flex items-center justify-center" style="padding-top: 0;">
                    <button class="button2 interactive">
                        <a style="text-decoration: none; color: #B71111" href="<?= $empresaId !== null && $parceiro !== null
                            ? 'sobre_parceiro.php?id=' . (int) $empresaId
                            : 'parceiros.php' ?>">Voltar ao Parceiro</a>
                    </button>
                </div>
            </main>
        </div>
    </section>
</body>
</html>

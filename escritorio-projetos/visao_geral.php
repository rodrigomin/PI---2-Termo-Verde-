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
    <link rel="stylesheet" href="./styles/styles.css?v=<?php echo filemtime(__DIR__ . '/styles/styles.css'); ?>"/>
    <link rel="stylesheet" href="./styles/visao_geral.css?v=<?php echo filemtime(__DIR__ . '/styles/visao_geral.css'); ?>"/>
    <script type="module" src='./js/index.js?v=<?php echo filemtime(__DIR__ . '/js/index.js'); ?>'> </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="module" src='./js/visao_geral.js?v=<?php echo filemtime(__DIR__ . '/js/visao_geral.js'); ?>'> </script>
</head>
<body class='flex w-full'>
    <?php 
        # echo ep_logout();
    ?>


    <section class='flex flex-row w-full'>
        <?php include_once __DIR__ . '/components/nav.php'; ?>

        <div class='flex flex-col' style='width: 100%;'>
            <?php include_once __DIR__ . '/components/header.php'; ?>
            <main>
                <?php if ($erroPagina !== ''): ?>
                    <p role="alert"><?= ep_e($erroPagina) ?></p>
                    <?php else: ?>
                    <section class='flex flex-col p-common bg-2' style="gap: 20px;">

                        <div class='flex flex-row justify-between items-center'>
                            <div style='line-height: 0.9'>
                                <h2 class='regular'>Olá, <br> <span class="bold" style='font-size: 60px'><?= ep_e($usuario['nome']) ?>! </span></h2>
                                <p class='light'>Seja bem vindo(a) ao...</p>
                            </div>

                            <div class='usr' style="background-color: white; border-radius: 100%; width: 100px; aspect-ratio: 1/1">
                            </div>
                        </div>
                        
                        <div class="flex flex-row items-center relative justify-center">
                            <div style='background-color: transparent; height: 200px; padding: 0 100px; box-sizing: border-box; z-index: 5' class='flex w-full rounded-xl flex-row items-center justify-between cima observado-me' id='hero-vg'>
                                <img src='./src/public/assets/cps-logo-branco.png' style='height: 60%'>
                                
                                <h1 class='text-center text-white regular' style="font-size: 60px; line-height: 0.70"> ESCRITÓRIO <br> <span style="font-size: 40px;"> DE PROJETOS </span> </h1>
                            </div>
                            <div class='overlay rounded-xl' style="opacity: 0.75"></div>
                            <video class="video-hero rounded-xl" autoplay muted loop playsinline preload="auto" poster="./src/public/assets/hero.png">
                                <source src="./src/public/assets/video-hero.mp4" type="video/mp4">
                                Seu navegador não suporta vídeos.
                            </video>
                        </div>

                        <div class='line-separator-w'></div>

                        <section>
                            <h2 class="title esq observado-me">KPI's</h2>
                            <p class="subtitle esq observado-me">Como estamos agora?</p>

                            <div class='grid-kpi' style='padding: 2rem 0px'>
                                <section class='status-d bg-1 container-grid esq observado-me'>
                                    <div class='title-kpi'>
                                        <h3>Status das Demandas</h3>
                                        <img class='interactive' src='./src/public/svgs/circle-info-solid-full.svg' alt='Status das Demandas'>
                                    </div>
                                    <select name='filtro-status' id='filtro-status'>
                                        <option value='ultimo-mes' selected> Último Mês </option>
                                        <option value='semestre'> Este Semestre </option>
                                        <option value='todas'> Todas </option>
                                    </select>

                                    <div class='line-separator-w'></div>

                                    <?php if (empty($statusUltimoMes)): ?>
                                        <div class="flex flex-col">
                                        <canvas class="circle-box" id='chart'></canvas>

                                        <div style='gap: 20px; display:grid; grid-template-columns: repeat(2, 1fr); align-items: center; justify-items: center; width: 100% '>
                                            <p class="label-circle sub"> Submetidos </p>
                                            <p class="label-circle exec"> Em Execução </p>
                                            <p class="label-circle tria"> Em Triagem </p>
                                            <p class="label-circle concl"> Concluído </p>
                                        </div>
                                        </div>
                                        <!--<p>Nenhuma demanda cadastrada nos últimos 30 dias.</p> -->
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

                                <section class='deman-curso bg-1 container-grid cima observado-me'>
                                    <div class='title-kpi'>
                                        <div class="flex flex-row">
                                            <h3>Demandas em Curso</h3>
                                            <img class='interactive' src='./src/public/svgs/circle-info-solid-full.svg' alt='Status das Demandas'>
                                        </div>

                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" style="width: 25px; height: 25px">
                                            <path fill="rgb(181, 16, 16)" d="M530.8 134.1C545.1 144.5 548.3 164.5 537.9 178.8L281.9 530.8C276.4 538.4 267.9 543.1 258.5 543.9C249.1 544.7 240 541.2 233.4 534.6L105.4 406.6C92.9 394.1 92.9 373.8 105.4 361.3C117.9 348.8 138.2 348.8 150.7 361.3L252.2 462.8L486.2 141.1C496.6 126.8 516.6 123.6 530.9 134z"/>
                                        </svg>
                                    </div>

                                    <div class="line-separator-w"></div>
                                    

                                    <div class="flex flex-row text-primary" style="gap: 20px">
                                        <div class="container-grafico flex items-center justify-center" style="position: relative; width: 200px; aspect-ratio: 2/1; overflow: hidden;">
                                            <canvas class="circle-box" id='semi-circle' style="position: absolute; top: 0; left: 0.5; width: 100%; height: 100%; scale: 1.5"></canvas>
                                        </div>
                                    
                                        <div class="flex flex-col justify-center">
                                            <p class="bold title"><?= ep_percentual_painel($indicadores['percentual_execucao']) ?></p>
                                            <p class="subtitle">
                                                <?= ep_numero_painel($indicadores['execucao']) ?> das
                                                <?= ep_numero_painel($indicadores['total']) ?> demandas
                                                estão em execução.
                                            </p>
                                        </div>
                                    </div>
                                </section>

                                <section class='deman-cadastr bg-1 container-grid cima observado-me'>
                                    <div class='title-kpi'>
                                        <h3>Demandas Cadastradas</h3>
                                        <img class='interactive' src='./src/public/svgs/circle-info-solid-full.svg' alt='Status das Demandas'>
                                    </div>

                                    <div class="line-separator-w"></div>

                                    <div class="flex items-center justify-center w-full h-full text-primary">
                                        <p class='text-thirdiary light text-center'><span class="bold title" id='demandasCadastradas'><?= ep_numero_painel($indicadores['total']) ?></span> <br> demandas cadastradas</p>
                                    </div>
                                </section>

                                <section class='emp-cadastr bg-1 container-grid items-center dir observado-me'>
                                    <div class='title-kpi'>
                                        <h3>Empresas Cadastradas</h3>
                                        <img class='interactive' src='./src/public/svgs/circle-info-solid-full.svg' alt='Status das Demandas'>
                                    </div>
                                    <div class="line-separator-w"></div>
                                    
                                    <div class="flex items-center justify-center w-full h-full text-primary">
                                        <p class='text-thirdiary light text-center'><span class="bold title" id='empresasCadastradas'><?= ep_numero_painel($empresasCadastradas) ?></span> <br> empresas cadastradas</p>
                                    </div>
                                </section>

                                <section class='triagem bg-1 container-grid esq observado-me'>
                                    <div class='flex flex-row items-center justify-center h-full text-primary' style="padding: 0 25px; gap: 25px">
                                        <div style='flex: 1; font-size: 65px' class='bold'><?= ep_numero_painel($indicadores['triagem']) ?> </div>
                                        <span class='semibold subtitle text-center'> demandas em triagem </span>
                                    </div>
                                </section>

                                <section class='execucao bg-1 container-grid cima observado-me'>
                                    <div class='flex flex-row items-center justify-center h-full text-primary' style="padding: 0 25px; gap: 25px">
                                        <div style='flex: 1; font-size: 65px' class='bold'><?= ep_numero_painel($indicadores['execucao']) ?> </div>
                                        <span class='semibold subtitle text-center'> demandas em execução </span>
                                    </div>
                                </section>

                                <section class="submetidas bg-1 container-grid dir observado-me">
                                    <div class='flex flex-row items-center justify-center h-full text-primary' style="padding: 0 25px; gap: 25px">
                                        <div style='flex: 1; font-size: 65px' class='bold'><?= ep_numero_painel($indicadores['submetidas']) ?> </div>
                                        <span class='semibold subtitle text-center'> demandas submetidas </span>
                                    </div>
                                </section>

                            </div>
                        </section>

                    </section>

                    <section class='p-common'>
                        <h2 class="title text-primary">Situação das Demandas Enviadas ao CEPE</h2>

                        <?php if ($erroFiltro !== ''): ?>
                            <p role="alert"><?= ep_e($erroFiltro) ?></p>
                        <?php endif; ?>

                        <form action="visao_geral.php" method="get" style="margin-top: 25px;">
                            <div class="flex flex-row w-full justify-between" style="gap: 20px">
                                <div class="flex flex-col" style="width: 50%;">
                                    <label class="subtitle" for="pesquisa">Pesquisar Demanda por Nome</label>
                                    <input
                                        type="search"
                                        id="pesquisa"
                                        name="pesquisa"
                                        placeholder="EX: Projeto Jacto"
                                        value="<?= ep_e($pesquisa) ?>"
                                        maxlength="150"
                                    >
                                </div>

                                <button type="submit" class='button interactive'>Pesquisar</button>

                                <div>
                                    <label class="subtitle" for="status">Filtrar por</label>
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
                            </div>
                        </form>

                        <table style="border-spacing: 0 25px;" class="w-full h-full" style="margin-top: 50px;">
                            <thead>
                                <tr class="text-primary subtitle">
                                    <th scope="col">Empresa</th>
                                    <th class="line-separator"></th>
                                    <th scope="col">Título e Descrição</th>
                                    <th class="line-separator"></th>
                                    <th scope="col">Data</th>
                                    <th class="line-separator"></th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($demandas)): ?>
                                    <!-- <div class="flex justify-center items-center w-full">
                                        <td class="text-center" colspan="8" style="padding: 50px 0;">Nenhuma demanda encontrada.</td>
                                    </div>
                                    -->

                                    <?php 
                                        for($i = 0; $i < 10; $i++):
                                    ?>
                                    
                                    
                                    <tr class="interactive text-center" style="height: 120px; background-color: #F2F2F2">
                                        <td colspan="2" class="text-center text-primary bold subtitle" style="border-radius: 10px 0 0 10px;"> EMPRESA X</td>
                                        <td colspan="2" class="text-center"> 
                                            <div> 
                                                <h3 class="subtitle semibold"> Titulo do Projeto </h3>
                                                <p class="light text-xl"> Descrição do Problema </p>
                                            </div>
                                        </td>
                                        <td colspan="2" class="text-center semibold subtitle"> 01/12/30 </td>
                                        <td colspan="2" class="text-center" style="border-radius: 0 10px 10px 0;"> <p class="bold subtitle" style="color: red"> EM EXECUÇÃO </p> </td>
                                    </tr>

                                    <?php endfor; ?>


                                <?php else: ?>
                                    <?php foreach ($demandas as $demanda): ?>
                                        <tr class="interactive" style="height: 120px; background-color: #F2F2F2">
                                            <td colspan="2" class="text-center text-primary bold subtitle" style="border-radius: 10px 0 0 10px;"> <?= ep_e($demanda['nome_fantasia'] ?? 'Empresa não informada') ?> </td>
                                            <td colspan="2" class="text-center"> 
                                                <div> 
                                                    <h3 class="subtitle semibold"> <?= ep_e($demanda['título'] ?? '') ?> </h3>
                                                    <p class="light text-xl"> <?= ep_e($demanda['descricão_problema'] ?? '') ?> </p>
                                                </div>
                                            </td>
                                            <td colspan="2" class="text-center semibold subtitle"> <?= ep_e(ep_data_painel($demanda['data_cadastro'] ?? null)) ?> </td>
                                            <td colspan="2" class="text-center" style="border-radius: 0 10px 10px 0;"> <p class="bold subtitle" style="color: red"> <?= ep_e($demanda['status'] ?: 'Sem status') ?> </p> </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <nav class="flex flex-row items-center justify-center" aria-label="Paginação das demandas" style="gap: 25px; margin-top: 25px">
                            <a class="flex flex-col items-center interactive">
                                <svg style="aspect-ratio: 1/1; width: 35px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                    <path fill="rgb(181, 16, 16)" d="M169.4 297.4C156.9 309.9 156.9 330.2 169.4 342.7L361.4 534.7C373.9 547.2 394.2 547.2 406.7 534.7C419.2 522.2 419.2 501.9 406.7 489.4L237.3 320L406.6 150.6C419.1 138.1 419.1 117.8 406.6 105.3C394.1 92.8 373.8 92.8 361.3 105.3L169.3 297.3z"/>
                                </svg>
                            </a>
                            <a class="flex flex-col items-center text-center subtitle underline"> 0 </a>
                            <a class="flex flex-col items-center interactive">
                                <svg style="aspect-ratio: 1/1; width: 35px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                    <path fill="rgb(181, 16, 16)" d="M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z"/>
                                </svg>
                            </a>
                        </nav>

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
        </div>
    </section>
    

    

</body>
</html>

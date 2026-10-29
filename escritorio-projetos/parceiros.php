<?php
require_once __DIR__ . '/app/bootstrap.php';

$erroPagina = '';
$erroPesquisa = '';
$pesquisa = '';
$parceiros = [];
$paginaAtual = 1;
$totalPaginas = 0;
$totalParceiros = 0;
$porPagina = 4;

try {
    $usuario = ep_exigir_login();

    // Mantém a mesma regra de acesso da Visão Geral.
    $empresaId = $usuario['perfil'] === 'administrador'
        ? null
        : (int) ($usuario['empresa_id'] ?? 0);

    $pesquisa = ep_texto($_GET, 'pesquisa');
    if (strlen($pesquisa) > 150) {
        $pesquisa = '';
        $erroPesquisa = 'A pesquisa deve conter no máximo 150 caracteres.';
    }

    $paginaInformada = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);
    if ($paginaInformada !== false && $paginaInformada !== null && $paginaInformada > 0) {
        $paginaAtual = $paginaInformada;
    }

    $totalParceiros = ep_total_empresas_filtradas($pesquisa, $empresaId);
    $totalPaginas = (int) ceil($totalParceiros / $porPagina);
    $paginaAtual = $totalPaginas > 0 ? min($paginaAtual, $totalPaginas) : 1;

    $parceiros = ep_listar_empresas($pesquisa, $paginaAtual, $porPagina, $empresaId);
} catch (Throwable $erro) {
    error_log('Erro no Banco de Parceiros: ' . $erro->getMessage());
    http_response_code(503);
    $erroPagina = 'Não foi possível carregar o Banco de Parceiros. Verifique o MySQL no XAMPP.';
}

function ep_url_parceiros(int $pagina, string $pesquisa): string
{
    return 'parceiros.php?' . http_build_query([
        'pesquisa' => $pesquisa,
        'pagina' => $pagina,
    ], '', '&', PHP_QUERY_RFC3986);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco de Parceiros - Escritório de Projetos</title>
    <link rel="stylesheet" href="./styles/styles.css?v=<?php echo filemtime(__DIR__ . '/styles/styles.css'); ?>"/>
    <link rel="stylesheet" href="./styles/parceiros.css?v=<?php echo filemtime(__DIR__ . '/styles/parceiros.css'); ?>"/>
    <script type="module" src='./js/index.js?v=<?php echo filemtime(__DIR__ . '/js/index.js'); ?>'> </script>
</head>
<body>


    <section class='flex flex-row w-full'>    
        <div class='flex flex-col justify-start items-start' style='height: 100dvh; position: sticky; top: 0'>
            <?php include_once __DIR__ . '/components/nav.php'; ?>
        </div>
        <div class="flex w-full flex-col">
            <?php include_once __DIR__ . '/components/header.php'; ?>
            <main>
                <section class="p-common flex flex-col" style="gap: 25px">
                    <h2 class="title">Banco de Parceiros</h2>

                    <?php if ($erroPagina !== ''): ?>
                        <p role="alert"><?= ep_e($erroPagina) ?></p>
                    <?php else: ?>
                        <?php if ($erroPesquisa !== ''): ?>
                            <p role="alert"><?= ep_e($erroPesquisa) ?></p>
                        <?php endif; ?>

                        <form action="parceiros.php" method="get">
                            <div class="flex flex-row justify-between">
                                <div class="flex flex-col w-full" style="gap: 10px">
                                    <label for="pesquisa">Pesquisar por Nome do Parceiro</label>
                                    <input
                                        type="search"
                                        id="pesquisa"
                                        name="pesquisa"
                                        placeholder="EX: Jacto"
                                        value="<?= ep_e($pesquisa) ?>"
                                        maxlength="150"
                                        style='width: 80%'
                                    >
                                </div>
                                <button class='button' type="submit">Pesquisar</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </section>

                <?php if ($erroPagina === ''): ?>
                    <section aria-label="Lista de parceiros" class="p-common flex flex-col" style="padding-top: 0; gap: 20px">
                        <?php if (empty($parceiros)): ?>
                            <p class="flex text-center w-full justify-center items-center semibold text-gray">Nenhum parceiro encontrado.</p>
                        <?php else: ?>
                            <?php foreach ($parceiros as $parceiro): ?>
                                <article class="flex flex-row p-8 parceiros-container observado-me cima">
                                    <div class="flex flex-col h-full items-between">
                                        <header class="flex flex-row" style="gap: 12px">
                                            <h3 class="text-primary"><?= ep_e($parceiro['nome_fantasia'] ?: ($parceiro['razão_social'] ?: 'Nome não informado')) ?></h3>
                                            <span> • </span>
                                            <p class="light">CNPJ: <?= ep_e($parceiro['CNPJ'] ?: 'Não informado') ?></p>
                                        </header>

                                    <p class="h-full" style="height: 100px; align-content: center"><?= ep_e($parceiro['descricao'] ?: 'Descrição não informada.') ?></p>

                                    <nav class="flex flex-col" style="gap: 10px" aria-label="Ações do parceiro">
                                        <a class="button interactive" href="sobre_parceiro.php?id=<?= (int) $parceiro['empresa_id'] ?>">Ver Mais</a>
                                        <a class="button2 interactive" href="editar_parceiro.php?id=<?= (int) $parceiro['empresa_id'] ?>">Editar Parceiro</a>
                                    </nav>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>
                    <?php if ($totalPaginas > 1): ?>
                        <nav aria-label="Paginação dos parceiros" class="flex flex-row items-center justify-center" style="gap: 25px; margin: 25px 0">
                            <?php if ($paginaAtual > 1): ?>
                                <a class="flex flex-col items-center interactive" href="<?= ep_e(ep_url_parceiros($paginaAtual - 1, $pesquisa)) ?>">
                                    <svg style="aspect-ratio: 1/1; width: 35px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                        <path fill="rgb(181, 16, 16)" d="M169.4 297.4C156.9 309.9 156.9 330.2 169.4 342.7L361.4 534.7C373.9 547.2 394.2 547.2 406.7 534.7C419.2 522.2 419.2 501.9 406.7 489.4L237.3 320L406.6 150.6C419.1 138.1 419.1 117.8 406.6 105.3C394.1 92.8 373.8 92.8 361.3 105.3L169.3 297.3z"/>
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <?php for ($pagina = 1; $pagina <= $totalPaginas; $pagina++): ?>
                                <a  class="flex flex-col items-center text-center subtitle underline"
                                    href="<?= ep_e(ep_url_parceiros($pagina, $pesquisa)) ?>"
                                    <?= $pagina === $paginaAtual ? 'aria-current="page"' : '' ?>
                                ><?= $pagina ?></a>
                            <?php endfor; ?>

                            <?php if ($paginaAtual < $totalPaginas): ?>
                                <a class="flex flex-col items-center interactive" href="<?= ep_e(ep_url_parceiros($paginaAtual + 1, $pesquisa)) ?>">
                                    <svg style="aspect-ratio: 1/1; width: 35px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                        <path fill="rgb(181, 16, 16)" d="M471.1 297.4C483.6 309.9 483.6 330.2 471.1 342.7L279.1 534.7C266.6 547.2 246.3 547.2 233.8 534.7C221.3 522.2 221.3 501.9 233.8 489.4L403.2 320L233.9 150.6C221.4 138.1 221.4 117.8 233.9 105.3C246.4 92.8 266.7 92.8 279.2 105.3L471.2 297.3z"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </section>
</body>
</html>

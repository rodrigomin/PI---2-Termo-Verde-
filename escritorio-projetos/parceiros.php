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
    <script type="module" src='./js/index.js'> </script>
</head>
<body>


    <section class='flex flex-row w-full'>    
    <div class='flex flex-col justify-start items-start h-full' style='height: 100dvh;'>
            <?php include_once __DIR__ . '/components/nav.php'; ?>
        </div>
        <div class='flex flex-col' style='width: 100%;'>
            <?php include_once __DIR__ . '/components/header.php'; ?>
        <main>
        <section>
            <h2>Banco de Parceiros</h2>

            <?php if ($erroPagina !== ''): ?>
                <p role="alert"><?= ep_e($erroPagina) ?></p>
            <?php else: ?>
                <?php if ($erroPesquisa !== ''): ?>
                    <p role="alert"><?= ep_e($erroPesquisa) ?></p>
                <?php endif; ?>

                <form action="parceiros.php" method="get">
                    <div>
                        <label for="pesquisa">Pesquisar por Nome do Parceiro</label>
                        <input
                            type="search"
                            id="pesquisa"
                            name="pesquisa"
                            placeholder="EX: Jacto"
                            value="<?= ep_e($pesquisa) ?>"
                            maxlength="150"
                        >
                        <button type="submit">Pesquisar</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <?php if ($erroPagina === ''): ?>
            <section aria-label="Lista de parceiros">
                <?php if (empty($parceiros)): ?>
                    <p>Nenhum parceiro encontrado.</p>
                <?php else: ?>
                    <?php foreach ($parceiros as $parceiro): ?>
                        <article>
                            <header>
                                <h3><?= ep_e($parceiro['nome_fantasia'] ?: ($parceiro['razão_social'] ?: 'Nome não informado')) ?></h3>
                                <p>CNPJ: <?= ep_e($parceiro['CNPJ'] ?: 'Não informado') ?></p>
                            </header>

                            <p><?= ep_e($parceiro['descricao'] ?: 'Descrição não informada.') ?></p>

                            <nav aria-label="Ações do parceiro">
                                <a href="sobre_parceiro.php?id=<?= (int) $parceiro['empresa_id'] ?>">Ver Mais</a>
                                <a href="editar_parceiro.php?id=<?= (int) $parceiro['empresa_id'] ?>">Editar Parceiro</a>
                            </nav>
                        </article>
                        <hr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <?php if ($totalPaginas > 1): ?>
                <nav aria-label="Paginação dos parceiros">
                    <p>Páginas:</p>
                    <?php if ($paginaAtual > 1): ?>
                        <a href="<?= ep_e(ep_url_parceiros($paginaAtual - 1, $pesquisa)) ?>">Anterior</a>
                    <?php endif; ?>

                    <?php for ($pagina = 1; $pagina <= $totalPaginas; $pagina++): ?>
                        <a
                            href="<?= ep_e(ep_url_parceiros($pagina, $pesquisa)) ?>"
                            <?= $pagina === $paginaAtual ? 'aria-current="page"' : '' ?>
                        ><?= $pagina ?></a>
                    <?php endfor; ?>

                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a href="<?= ep_e(ep_url_parceiros($paginaAtual + 1, $pesquisa)) ?>">Próxima</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
        </main>
        </div>
    </section>
</body>
</html>

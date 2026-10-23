<?php
require_once __DIR__ . '/app/bootstrap.php';

$erroPagina = '';
$erroPesquisa = '';
$parceiro = null;
$empresaId = null;
$pesquisa = '';
$filtroStatus = '';
$statusDisponiveis = [];
$totalDemandas = 0;
$propostas = [];

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
            $erroPagina = 'Parceiro não encontrado ou sem permissão de acesso.';
        } else {
            $pesquisa = ep_texto($_GET, 'pesquisa');
            if (ep_comprimento_utf8($pesquisa) > 150) {
                $pesquisa = '';
                $erroPesquisa = 'A pesquisa deve ter no máximo 150 caracteres.';
            }

            $filtroStatus = ep_texto($_GET, 'status');
            $statusDisponiveis = ep_status_propostas_parceiro($empresaId);
            if ($filtroStatus !== '' && !in_array($filtroStatus, $statusDisponiveis, true)) {
                $filtroStatus = '';
                $erroPesquisa = 'O status informado não está disponível para este parceiro.';
            }

            $totalDemandas = ep_total_propostas_parceiro($empresaId);
            $propostas = ep_listar_propostas_parceiro($empresaId, $pesquisa, $filtroStatus);
        }
    }
} catch (Throwable $erro) {
    error_log('Erro ao consultar parceiro: ' . $erro->getMessage());
    http_response_code(503);
    $erroPagina = 'Não foi possível consultar o parceiro. Verifique o MySQL no XAMPP.';
}

function ep_data_proposta_parceiro($data): string
{
    if (!is_string($data) || $data === '') {
        return 'Não informada';
    }
    try {
        return (new DateTimeImmutable($data))->format('d/m/Y');
    } catch (Throwable $erro) {
        return 'Não informada';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Parceiro - Escritório de Projetos</title>
    <link rel="stylesheet" href="./styles/styles.css?v=<?php echo filemtime(__DIR__ . '/styles/styles.css'); ?>"/>
    <link rel="stylesheet" href="./styles/sobre_parceiros.css?v=<?php echo filemtime(__DIR__ . '/styles/sobre_parceiros.css'); ?>"/>
    <script type="module" src='./js/index.js?v=<?php echo filemtime(__DIR__ . '/js/index.js'); ?>'> </script>
    <script type="module" src='./js/sobre_parceiro.js?v=<?php echo filemtime(__DIR__ . '/js/sobre_parceiro.js'); ?>'> </script>

</head>
<body>
    

    <section class='flex flex-row'>    
        <div class='flex flex-col justify-start items-start' style='height: 100dvh; position: sticky; top: 0'>
            <?php include_once __DIR__ . '/components/nav.php'; ?>
        </div>
        <div style="width: 100%">
            <?php include_once __DIR__ . '/components/header.php'; ?>
    
        <h2 class="title text-primary p-common" style="padding-bottom: 0; font-size: 48px; color: black">Sobre Parceiro<?= $parceiro !== null ? ' “' . ep_e($parceiro['nome_fantasia'] ?: ($parceiro['razão_social'] ?: 'Nome não informado')) . '”' : '' ?></h2>
        <main style="border: solid 3px #D9D9D9; margin: 50px;" class="rounded-lg relative">
            <section class="p-common" style="padding-bottom: 0">    
                <header style="margin: 20px 0">
                    <h3 class="title text-primary">NOME_FANTASIA</h3>
                    <p class="subtitle light">CNPJ: 111.111.111/001-11</p>
                </header>

                <hr>
                
                <?php include_once __DIR__ . '/components/modal_escopo.php' ?>
                
                <section class="flex flex-col" style="margin: 20px 0; gap: 12px">
                    <h3 class="text-primary semibold subtitle">Razão Social</h3>
                    <p class="text-lg"> Lorem, ipsum dolor sit amet consectetur adipisicing elit. Enim fugiat laudantium aliquid odit harum repellendus.</p>
                </section>
            
                <section class="flex flex-col" style="gap: 5px">
                    <h3 class="text-primary semibold subtitle" style="margin-bottom: 12px;">Contato</h3>
                    <p class="representante"><strong>Nome do Representante:</strong> José Carlos Oliveira</p>
                    <p class="email"> <strong>Email:</strong> josecarl0@empresa.com</p>
                    <p class="telefone"><strong>Telefone:</strong> +55 21 99578-7034</p>
                </section>

                 <section aria-label="Indicadores do parceiro" style="padding: 50px 0; padding-bottom: 0" class="flex items-center justify-between w-full h-full text-primary container-grid observado-me">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <article class="bg-2 flex flex-col justify-between rounded-xl p-6" style="max-width: 20%; gap: 20px">
                            <div class="text-secondary flex flex-row title-kpi">
                                <h3>Demandas Cadastradas</h3>
                                <img class='interactive' src='./src/public/svgs/circle-info-solid-full.svg' alt='Demandas Cadastradas'>
                            </div>
                            <div class="flex items-center justify-center w-full h-full text-center">
                                <p class="subtitle semibold flex flex-row" style="width: 70%; gap:20px"><span class="title bold"><?= (int) $totalDemandas ?></span> demandas cadastradas</p>
                            </div>
                        </article>
                    <?php endfor; ?>
                </section>
                
                <?php if ($erroPagina !== ''): ?>
                    <!--<p role="alert"><?= ep_e($erroPagina) ?></p>-->
                <?php else: ?>
                    <header>
                        <h3><?= ep_e($parceiro['nome_fantasia'] ?: ($parceiro['razão_social'] ?: 'Nome não informado')) ?></h3>
                        <p>CNPJ: <?= ep_e($parceiro['CNPJ'] ?: 'Não informado') ?></p>
                    </header>
                
                    <section>
                        <h3>Razão Social</h3>
                        <p><?= ep_e($parceiro['razão_social'] ?: 'Não informada') ?></p>
                    </section>
                
                    <section>
                        <h3>Contato</h3>
                        <p>Nome do Representante: <?= ep_e($parceiro['contato_nome'] ?: 'Não informado') ?></p>
                        <p>Email: <?= ep_e($parceiro['email_corporativo'] ?: 'Não informado') ?></p>
                        <p>Telefone: <?= ep_e($parceiro['telefone'] ?: 'Não informado') ?></p>
                    </section>
                <?php endif; ?>
            </section>
                
            <?php if ($parceiro !== null): ?>
                <section aria-label="Indicadores do parceiro">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <article>
                            <h3>Demandas Cadastradas</h3>
                            <p><?= (int) $totalDemandas ?> demandas cadastradas</p>
                        </article>
                    <?php endfor; ?>
                </section>
                    
                <section>
                    <h2>Propostas do Parceiro</h2>
                    <p>Convênios, escopos, documentos de acompanhamento e status</p>
                    
                    <?php if ($erroPesquisa !== ''): ?>
                        <p role="alert"><?= ep_e($erroPesquisa) ?></p>
                    <?php endif; ?>
                    
                    <form action="sobre_parceiro.php" method="get">
                        <input type="hidden" name="id" value="<?= (int) $empresaId ?>">
                    
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
                </section>
                                
                <section aria-label="Propostas cadastradas">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Título e Descrição</th>
                                <th scope="col">Data</th>
                                <th scope="col">Status</th>
                                <th scope="col">Documentos</th>
                                <th scope="col">Escopo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($propostas)): ?>
                                <tr><td colspan="5">Nenhuma proposta encontrada.</td></tr>
                            <?php else: ?>
                                <?php foreach ($propostas as $proposta): ?>
                                    <tr>
                                        <td>
                                            <strong><?= ep_e($proposta['título'] ?: 'Sem título') ?></strong>
                                            <p><?= ep_e($proposta['descricão_problema'] ?: 'Descrição não informada') ?></p>
                                        </td>
                                        <td><?= ep_e(ep_data_proposta_parceiro($proposta['data_cadastro'] ?? null)) ?></td>
                                        <td><?= ep_e($proposta['status'] ?: 'Sem status') ?></td>
                                        <td>
                                            <a href="documentos_parceiro.php?<?= ep_e(http_build_query([
                                                'id' => $empresaId,
                                                'proposta_id' => (int) $proposta['proposta_id'],
                                            ], '', '&', PHP_QUERY_RFC3986)) ?>">Documentos</a>
                                        </td>
                                        <td>
                                            <?php if (ep_localizar_arquivo_escopo($proposta['escopo'] ?? null) !== null): ?>
                                                <a href="ver_escopo.php?proposta_id=<?= (int) $proposta['proposta_id'] ?>">Ver Escopo</a>
                                            <?php else: ?>
                                                Escopo indisponível
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>
            <?php endif; ?>

            <section class="p-common" style="padding-bottom: 0;">
                <h2 class="text-primary title">Propostas do Parceiro</h2>
                <p class="subtitle">Convênios, escopos, documentos de acompanhamento e status</p>
                
                <?php if ($erroPesquisa !== ''): ?>
                    <p role="alert"><?= ep_e($erroPesquisa) ?></p>
                <?php endif; ?>
                
                <form action="sobre_parceiro.php" method="get" class="flex flex-row justify-between items-center" style="padding: 20px 0;">
                    <input type="hidden" name="id" value="<?= (int) $empresaId ?>">
                
                    <div class="flex flex-col" style="width: 50%">
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
                
                    <div class="flex flex-col">
                        <label for="status">Filtrar por</label>
                        <select style="width: 200px" id="status" name="status">
                            <option value="">Todos os status</option>
                            <?php foreach ($statusDisponiveis as $status): ?>
                                <option
                                    value="<?= ep_e($status) ?>"
                                    <?= $filtroStatus === $status ? 'selected' : '' ?>
                                ><?= ep_e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                            
                    <button type="submit" class="button">Pesquisar</button>
                </form>
            </section>
                            
            <section aria-label="Propostas cadastradas" class="p-common" style="padding-top: 12px;">
                <table style="border-spacing: 0 25px;" class="w-full h-full" style="margin-top: 50px;">
                    <thead>
                        <tr class="text-primary bold subtitle">
                            <th scope="col">Título e Descrição</th>
                            <th class="line-separator"></th>
                            <th scope="col">Data</th>
                            <th class="line-separator"></th>
                            <th scope="col">Status</th>
                            <th class="line-separator"></th>
                            <th scope="col">Documentos</th>
                            <th class="line-separator"></th>
                            <th scope="col">Escopo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($propostas)): ?>
                            <!--
                            <tr><td colspan="9">Nenhuma proposta encontrada.</td></tr>
                            -->

                            <tr style="background-color: #f2f2f2; height: 75px">
                                <td colspan="2" class="" style="border-radius: 10px 0 0 10px;">
                                    <div class="flex items-center justify-center">
                                        <div style="text-align: left">
                                            <strong>Título do Projeto </strong>
                                            <p>Descrição do Problema</p>
                                        </div>
                                    </div>
                                </td>
                                <td colspan="2" class="bold">01/12/25</td>
                                <td colspan="2" class="bold">EM EXECUÇÃO </td>
                                <td colspan="2" class="interactive">
                                    <a class="button">Documentos</a>
                                </td>
                                <td colspan="2" class="interactive" style="border-radius: 0 10px 10px 0;">
                                    <a class="button2" id='ver-escopo'>Ver Escopo</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($propostas as $proposta): ?>
                                <tr>
                                    <td>
                                        <strong><?= ep_e($proposta['título'] ?: 'Sem título') ?></strong>
                                        <p><?= ep_e($proposta['descricão_problema'] ?: 'Descrição não informada') ?></p>
                                    </td>
                                    <td><?= ep_e(ep_data_proposta_parceiro($proposta['data_cadastro'] ?? null)) ?></td>
                                    <td><?= ep_e($proposta['status'] ?: 'Sem status') ?></td>
                                    <td>
                                        <a href="documentos_parceiro.php?<?= ep_e(http_build_query([
                                            'id' => $empresaId,
                                            'proposta_id' => (int) $proposta['proposta_id'],
                                        ], '', '&', PHP_QUERY_RFC3986)) ?>">Documentos</a>
                                    </td>
                                    <td>
                                        <?php if (ep_localizar_arquivo_escopo($proposta['escopo'] ?? null) !== null): ?>
                                            <a href="ver_escopo.php?proposta_id=<?= (int) $proposta['proposta_id'] ?>">Ver Escopo</a>
                                        <?php else: ?>
                                            Escopo indisponível
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
                                            
            <div class="p-common flex items-center justify-center" style="padding-top: 0;"><button class="interactive button2"><a href="parceiros.php" class="text-primary" style="text-decoration: none;">Voltar ao Banco de Parceiros</a></button></div>
        </main>
    </div>
    </section>
</body>
</html>

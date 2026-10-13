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
            <h2>Sobre Parceiro<?= $parceiro !== null ? ' “' . ep_e($parceiro['nome_fantasia'] ?: ($parceiro['razão_social'] ?: 'Nome não informado')) . '”' : '' ?></h2>

            <?php if ($erroPagina !== ''): ?>
                <p role="alert"><?= ep_e($erroPagina) ?></p>
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

        <p><a href="parceiros.php">Voltar ao Banco de Parceiros</a></p>
    </main>
</body>
</html>

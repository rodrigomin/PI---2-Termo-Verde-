<?php

require_once __DIR__ . '/app/bootstrap.php';


$erro = '';
$mensagem = '';
$pendentes = [];
$tokenCsrf = '';

try {
    ep_inicializar_banco();
    ep_iniciar_sessao();
    $usuario = ep_usuario_atual();
    if ($usuario === null) {
        header('Location: ../../escritorio-projetos/index.php', true, 303);
        exit;
    }
    if ($usuario['perfil'] !== 'administrador') {
        http_response_code(403);
        exit('Acesso restrito.');
    }

    $db = ep_db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!ep_validar_csrf($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $erro = 'Não foi possível validar o formulário. Atualize a página e tente novamente.';
        } else {
            $id = filter_var($_POST['empresa_id'] ?? null, FILTER_VALIDATE_INT);
            $acao = ep_texto($_POST, 'acao');
            $motivo = ep_texto($_POST, 'motivo');

            if ($id === false || $id < 1 || !in_array($acao, ['aprovar', 'rejeitar'], true)) {
                http_response_code(400);
                $erro = 'A solicitação enviada é inválida.';
            } elseif ($acao === 'rejeitar' && $motivo === '') {
                $erro = 'Informe o motivo da rejeição.';
            } else {
                $novoStatus = $acao === 'aprovar' ? 'aprovado' : 'rejeitado';
                $db->beginTransaction();
                try {
                    $atualizar = $db->prepare(
                        'UPDATE `Empresas_Parceiras`
                         SET `status_cadastro` = :status,
                             `revisado_por` = :revisor,
                             `revisado_em` = NOW(),
                             `motivo_rejeicao` = :motivo
                         WHERE `empresa_id` = :id AND `status_cadastro` = \'pendente\''
                    );
                    $atualizar->execute([
                        'status' => $novoStatus,
                        'revisor' => (int) $usuario['usuario_id'],
                        'motivo' => $novoStatus === 'rejeitado' ? $motivo : null,
                        'id' => $id,
                    ]);

                    if ($atualizar->rowCount() === 0) {
                        $db->rollBack();
                        $erro = 'A solicitação já foi analisada ou não existe.';
                    } else {
                        $buscar = $db->prepare(
                            'SELECT `email_corporativo`, `contato_nome`
                             FROM `Empresas_Parceiras`
                             WHERE `empresa_id` = :id'
                        );
                        $buscar->execute(['id' => $id]);
                        $dadosContato = $buscar->fetch();
                        if (!$dadosContato) {
                            throw new RuntimeException('Não foi possível localizar o contato da empresa.');
                        }
                        $email = $dadosContato['email_corporativo'];
                        if (!is_string($email) || $email === '') {
                            throw new RuntimeException('Não foi possível localizar o e-mail da empresa.');
                        }
                        $db->commit();
                    }
                } catch (Throwable $erroTransacao) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    throw $erroTransacao;
                }
                ep_redirecionar('aprovacao_de_parceiros.php');
            }
        }
    }

    $mensagem = (string) ($_SESSION['aprovacao_mensagem'] ?? '');
    unset($_SESSION['aprovacao_mensagem']);

    $pendentes = $db->query(
        'SELECT `empresa_id`, `nome_fantasia`, `contato_nome`, `email_corporativo`, `solicitado_em`
         FROM `Empresas_Parceiras`
         WHERE `status_cadastro` = \'pendente\'
         ORDER BY `solicitado_em`'
    )->fetchAll();
    $tokenCsrf = ep_token_csrf();
} catch (Throwable $erroPagina) {
    error_log('Erro na aprovação de parceiros: ' . $erroPagina->getMessage());
    http_response_code(503);
    $erro = 'Não foi possível carregar as solicitações de parceiros.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aprovação de parceiros</title>
</head>
<body>
    <main>
        <h1>Cadastros pendentes</h1>
        <p><a href="visao_geral.php">Voltar ao painel</a></p>

        <?php if ($erro !== ''): ?>
            <p role="alert"><?= ep_e($erro) ?></p>
        <?php endif; ?>
        <?php if ($mensagem !== ''): ?>
            <p role="status"><?= ep_e($mensagem) ?></p>
        <?php endif; ?>

        <?php if ($pendentes === []): ?>
            <p>Não há cadastros aguardando análise.</p>
        <?php else: ?>
            <?php foreach ($pendentes as $empresa): ?>
                <section>
                    <h2><?= ep_e($empresa['nome_fantasia']) ?></h2>
                    <p>Contato: <?= ep_e($empresa['contato_nome']) ?></p>
                    <p>E-mail: <?= ep_e($empresa['email_corporativo']) ?></p>
                    <p>Solicitado em: <?= ep_e($empresa['solicitado_em']) ?></p>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= ep_e($tokenCsrf) ?>">
                        <input type="hidden" name="empresa_id" value="<?= (int) $empresa['empresa_id'] ?>">
                        <label>
                            Motivo da rejeição
                            <input type="text" name="motivo" maxlength="1000">
                        </label>
                        <button type="submit" name="acao" value="aprovar">Aprovar</button>
                        <button type="submit" name="acao" value="rejeitar">Rejeitar</button>
                    </form>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>

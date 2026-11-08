<?php

require_once dirname(__DIR__, 2) . '/escritorio-projetos/app/bootstrap.php';

function ep_aprovacao_garantir_colunas(PDO $db): void
{
    $colunas = [
        'status_cadastro' => "VARCHAR(20) NOT NULL DEFAULT 'aprovado'",
        'solicitado_em' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'revisado_por' => 'INT NULL',
        'revisado_em' => 'DATETIME NULL',
        'motivo_rejeicao' => 'TEXT NULL',
    ];
    $consulta = $db->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = \'Empresas_Parceiras\'
           AND COLUMN_NAME = :coluna'
    );

    foreach ($colunas as $nome => $definicao) {
        $consulta->execute(['coluna' => $nome]);
        if ((int) $consulta->fetchColumn() === 0) {
            $db->exec('ALTER TABLE `Empresas_Parceiras` ADD COLUMN `' . $nome . '` ' . $definicao);
        }
    }
}

function ep_aprovacao_enviar_email(string $para, string $assunto, string $texto): bool
{
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('Falha ao enviar e-mail de aprovação: dependências do Composer não instaladas.');
        return false;
    }

    require_once $autoload;
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        error_log('Falha ao enviar e-mail de aprovação: PHPMailer não está instalado.');
        return false;
    }

    $host = getenv('SMTP_HOST') ?: '';
    $usuario = getenv('SMTP_USER') ?: '';
    $senha = getenv('SMTP_PASS');
    $porta = filter_var(getenv('SMTP_PORT'), FILTER_VALIDATE_INT);
    if ($host === '' || $usuario === '' || $senha === false || $senha === ''
        || $porta === false || $porta < 1 || $porta > 65535
        || !filter_var($para, FILTER_VALIDATE_EMAIL)) {
        error_log('Falha ao enviar e-mail de aprovação: configuração SMTP ou destinatário inválido.');
        return false;
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $usuario;
        $mail->Password = $senha;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $porta;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($usuario, 'Escritório de Projetos');
        $mail->addAddress($para);
        $mail->Subject = $assunto;
        $mail->Body = $texto;
        $mail->send();
        return true;
    } catch (Throwable $erro) {
        error_log('Falha ao enviar e-mail de aprovação: ' . $erro->getMessage());
        return false;
    }
}

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
    ep_aprovacao_garantir_colunas($db);

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
                            'SELECT `email_corporativo` FROM `Empresas_Parceiras`
                             WHERE `empresa_id` = :id'
                        );
                        $buscar->execute(['id' => $id]);
                        $email = $buscar->fetchColumn();
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

                if ($erro === '') {
                    $texto = $novoStatus === 'aprovado'
                        ? 'Seu cadastro foi aprovado. Você já pode entrar no sistema.'
                        : 'Seu cadastro não foi aprovado. Motivo: ' . $motivo;
                    ep_aprovacao_enviar_email($email, 'Resultado do cadastro da empresa', $texto);

                    $_SESSION['aprovacao_mensagem'] = 'Solicitação atualizada com sucesso.';
                    ep_redirecionar('aprovacao_de_parceiros.php');
                }
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
        <p><a href="../../escritorio-projetos/visao_geral.php">Voltar ao painel</a></p>

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

<?php

function ep_aprovacao_enviar_email(
    string $para,
    string $assunto,
    string $html,
    string $texto
): void {
    $host = getenv('SMTP_HOST');
    $usuario = getenv('SMTP_USER');
    $senha = getenv('SMTP_PASS');
    $porta = filter_var(getenv('SMTP_PORT'), FILTER_VALIDATE_INT);

    if (
        !is_string($host) || $host === ''
        || !is_string($usuario) || $usuario === ''
        || !is_string($senha) || $senha === ''
        || $porta === false || $porta < 1 || $porta > 65535
        || !filter_var($para, FILTER_VALIDATE_EMAIL)
    ) {
        throw new RuntimeException('Configuração SMTP inválida ou incompleta.');
    }

    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Autoload do Composer não encontrado.');
    }

    require_once $autoload;

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
    $mail->isHTML(true);
    $mail->Subject = $assunto;
    $mail->Body = $html;
    $mail->AltBody = $texto;
    $mail->send();
}

function ep_aprovacao_notificar_gestor(array $empresa): void
{
    $gestor = getenv('GESTOR_EMAIL');
    $appUrl = rtrim((string) getenv('APP_URL'), '/');

    if (
        !is_string($gestor)
        || !filter_var($gestor, FILTER_VALIDATE_EMAIL)
        || $appUrl === ''
    ) {
        throw new RuntimeException('E-mail do gestor ou URL do sistema inválidos.');
    }

    $nome = htmlspecialchars(
        (string) ($empresa['nome_fantasia'] ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
    $contato = htmlspecialchars(
        (string) ($empresa['contato_nome'] ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
    $email = htmlspecialchars(
        (string) ($empresa['email_corporativo'] ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
    $url = htmlspecialchars(
        $appUrl . '/aprovacao_de_parceiros.php',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    ep_aprovacao_enviar_email(
        $gestor,
        'Novo cadastro de parceiro aguardando análise',
        "<p>Há um novo cadastro aguardando análise.</p>
         <p>Empresa: {$nome}</p>
         <p>Contato: {$contato}</p>
         <p>E-mail: {$email}</p>
         <p><a href=\"{$url}\">Acessar a área de aprovação</a></p>",
        "Novo cadastro aguardando análise: {$nome}. "
        . "Acesse o sistema para revisar a solicitação."
    );
}

function ep_aprovacao_notificar_representante(
    string $para,
    string $nome,
    string $status,
    string $motivo = ''
): void {
    $nomeSeguro = htmlspecialchars(
        $nome,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
    $motivoSeguro = htmlspecialchars(
        $motivo,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    if ($status === 'aprovado') {
        $assunto = 'Cadastro da empresa aprovado';
        $texto = "Olá, {$nome}. Seu cadastro foi aprovado. Você já pode acessar o sistema.";
        $html = "<p>Olá, {$nomeSeguro}.</p>
                 <p>Seu cadastro foi aprovado. Você já pode acessar o sistema.</p>";
    } else {
        $assunto = 'Resultado do cadastro da empresa';
        $texto = "Olá, {$nome}. Seu cadastro não foi aprovado. Motivo: {$motivo}";
        $html = "<p>Olá, {$nomeSeguro}.</p>
                 <p>Seu cadastro não foi aprovado.</p>
                 <p>Motivo: {$motivoSeguro}</p>";
    }

    ep_aprovacao_enviar_email($para, $assunto, $html, $texto);
}
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// caminho original do autoload do composer
require 'C:\\xampp\\htdocs\\PI---2-Termo-Verde-\\vendor\\autoload.php';

$mail_example = new PHPMailer(true);

function ep_enviar_email($destinatario, $nomeDestinatario, $assunto, $corpo_texto)
{
    $testhtml = '<div style="background-color: blue; width: 500px; height: 500px; display: flex; flex-direction: row"><h1>Teste de envio de e-mail</h1><p>Este é um teste de envio de e-mail usando PHPMailer.</p></div>';
    global $mail_example;
    try {
        $mail_example->isSMTP();
        $mail_example->Host       = 'smtp.gmail.com';
        $mail_example->SMTPAuth   = true;
        $mail_example->Username   = 'seu_email@gmail.com'; // seu gmail
        $mail_example->Password   = 'sua_senha_de_app'; // senha de app do gmail
        $mail_example->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail_example->Port       = 587;

        // Remetente
        $mail_example->setFrom('seu_email_vai_aqui', 'nome_do_remetente');
        // Destinatário
        $mail_example->addAddress($destinatario, $nomeDestinatario);

        $mail_example->isHTML(true);
        $mail_example->Subject = $assunto;
        $mail_example->Body    = $testhtml; // corpo do e-mail em HTML
        $mail_example->AltBody = $corpo_texto;

        $mail_example->send();
        echo 'E-mail enviado com sucesso!';
    } catch (Exception $e) {
        echo "Erro ao enviar e-mail. Erro do PHPMailer: {$mail_example->ErrorInfo}";
    }
}
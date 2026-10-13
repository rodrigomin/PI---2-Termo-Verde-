<?php
require_once __DIR__ . '/app/bootstrap.php';

try {
    ep_exigir_login();

    $tipo = ep_texto($_GET, 'tipo');
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!in_array($tipo, ['convenio', 'acompanhamento'], true) || !$id || $id < 1) {
        http_response_code(400);
        exit('Informe um documento válido.');
    }

    $documento = ep_buscar_documento_para_abertura($tipo, $id);
    if ($documento === null
        || !ep_pode_acessar_empresa((int) $documento['empresa_id'])) {
        http_response_code(404);
        exit('Documento não encontrado.');
    }

    $arquivo = ep_localizar_documento_privado($documento['caminho'] ?? null, $tipo);
    if ($arquivo === null) {
        http_response_code(404);
        exit('Arquivo indisponível.');
    }

    $extensao = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
    $tipos = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    if (!isset($tipos[$extensao])) {
        http_response_code(404);
        exit('Tipo de arquivo não permitido.');
    }

    $disposicao = $extensao === 'pdf' ? 'inline' : 'attachment';
    $nome = $tipo . '-' . $id . '.' . $extensao;
    $tamanho = filesize($arquivo);
    if ($tamanho === false) {
        throw new RuntimeException('Não foi possível consultar o tamanho do arquivo.');
    }

    // Libera o bloqueio da sessão antes de transmitir o arquivo ao navegador.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    header('Content-Type: ' . $tipos[$extensao]);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    header('Content-Disposition: ' . $disposicao . '; filename="' . $nome . '"');
    header('Content-Length: ' . $tamanho);
    readfile($arquivo);
    exit;
} catch (Throwable $erro) {
    error_log('Erro ao abrir documento do parceiro: ' . $erro->getMessage());
    http_response_code(503);
    exit('Não foi possível abrir o documento. Verifique o MySQL e o armazenamento.');
}

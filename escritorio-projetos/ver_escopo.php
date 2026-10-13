<?php
require_once __DIR__ . '/app/bootstrap.php';

try {
    $usuario = ep_exigir_login();
    $propostaId = filter_input(INPUT_GET, 'proposta_id', FILTER_VALIDATE_INT);
    if (!$propostaId || $propostaId < 1) {
        http_response_code(400);
        exit('Informe uma proposta válida.');
    }

    $proposta = ep_buscar_demanda($propostaId);
    if ($proposta === null || !ep_pode_acessar_empresa((int) $proposta['empresa_id'])) {
        http_response_code(404);
        exit('Escopo não encontrado.');
    }

    $arquivo = ep_localizar_arquivo_escopo($proposta['escopo'] ?? null);
    if ($arquivo === null) {
        http_response_code(404);
        exit('Arquivo de escopo indisponível.');
    }

    $extensao = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
    $tipos = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    $tipo = $tipos[$extensao] ?? 'application/octet-stream';
    $disposicao = $extensao === 'pdf' ? 'inline' : 'attachment';

    header('Content-Type: ' . $tipo);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    header('Content-Disposition: ' . $disposicao . '; filename="escopo.' . $extensao . '"');
    header('Content-Length: ' . filesize($arquivo));
    readfile($arquivo);
    exit;
} catch (Throwable $erro) {
    error_log('Erro ao consultar escopo: ' . $erro->getMessage());
    http_response_code(503);
    exit('Não foi possível consultar o arquivo de escopo.');
}

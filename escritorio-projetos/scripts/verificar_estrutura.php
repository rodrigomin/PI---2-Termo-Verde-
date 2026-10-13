<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script deve ser executado pela linha de comando.');
}
require_once __DIR__ . '/../app/bootstrap.php';

try {
    $criado = ep_inicializar_banco();
    echo $criado ? "Banco criado.\n" : "Banco já existente.\n";
    $tabelas = ep_db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tabelas as $tabela) {
        echo '- ' . $tabela . PHP_EOL;
    }
} catch (Throwable $erro) {
    fwrite(STDERR, 'Erro: ' . $erro->getMessage() . PHP_EOL);
    exit(1);
}

<?php
function ep_db(): PDO
{
    static $conexao = null;

    if ($conexao instanceof PDO) {
        return $conexao;
    }

    $config = require __DIR__ . '/config.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['porta'],
        $config['banco']
    );

    $conexao = new PDO($dsn, $config['usuario'], $config['senha'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $conexao;
}

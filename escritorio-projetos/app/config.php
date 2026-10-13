<?php
return [
    'host' => getenv('EP_DB_HOST') ?: '127.0.0.1',
    'porta' => getenv('EP_DB_PORT') ?: '3306',
    'banco' => getenv('EP_DB_NAME') ?: 'escritorio_de_projetos',
    'usuario' => getenv('EP_DB_USER') ?: 'root',
    'senha' => getenv('EP_DB_PASSWORD') !== false ? getenv('EP_DB_PASSWORD') : '',
];

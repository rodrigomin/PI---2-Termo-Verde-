<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['fechado'] = (isset($_GET['fechado']) && $_GET['fechado'] === '1');


if (isset($_GET['destino'])) {
    $arquivo = basename($_GET['destino']);
    $_SESSION['destino'] = $arquivo;
}

$destinoFinal = $_SESSION['destino'] ?? 'index.php';

header('Location: ../' . $destinoFinal);
exit;

<?php
function ep_iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (headers_sent()) {
        throw new RuntimeException('A sessão precisa ser iniciada antes do HTML.');
    }

    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function ep_e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ep_texto(array $dados, string $campo): string
{
    $valor = $dados[$campo] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

function ep_redirecionar(string $pagina): void
{
    if (!preg_match('/^[a-zA-Z0-9_-]+\.php(?:\?[a-zA-Z0-9_=&%-]*)?$/', $pagina)) {
        throw new InvalidArgumentException('Destino de redirecionamento inválido.');
    }
    header('Location: ' . $pagina, true, 303);
    exit;
}

function ep_token_csrf(): string
{
    ep_iniciar_sessao();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function ep_validar_csrf($token): bool
{
    ep_iniciar_sessao();
    return is_string($token) && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function ep_validar_data(string $data): bool
{
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
    return $objeto !== false && $objeto->format('Y-m-d') === $data;
}

function ep_normalizar_cnpj(string $cnpj): string
{
    return preg_replace('/\D/', '', $cnpj);
}

function ep_validar_cnpj(string $cnpj): bool
{
    $cnpj = ep_normalizar_cnpj($cnpj);
    if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
        return false;
    }

    for ($tamanho = 12; $tamanho <= 13; $tamanho++) {
        $pesos = $tamanho === 12
            ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]
            : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < $tamanho; $i++) {
            $soma += (int) $cnpj[$i] * $pesos[$i];
        }
        $resto = $soma % 11;
        $digito = $resto < 2 ? 0 : 11 - $resto;
        if ((int) $cnpj[$tamanho] !== $digito) {
            return false;
        }
    }
    return true;
}

function ep_validar_telefone(string $telefone): bool
{
    $digitos = preg_replace('/\D/', '', $telefone);
    return strlen($digitos) === 10 || strlen($digitos) === 11;
}

/** Comprimento em caracteres para respeitar os campos VARCHAR do MySQL UTF-8. */
function ep_comprimento_utf8(string $texto): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($texto, 'UTF-8');
    }
    $quantidade = preg_match_all('/./us', $texto, $correspondencias);
    return $quantidade === false ? strlen($texto) : $quantidade;
}

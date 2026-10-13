<?php

/** Mantém documentos privados fora da pasta pública htdocs no XAMPP. */
function ep_diretorio_escopos(): string
{
    $baseConfigurada = getenv('EP_FILES_DIR');
    $base = $baseConfigurada !== false && trim($baseConfigurada) !== ''
        ? rtrim($baseConfigurada, '/\\')
        : dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'escritorio-projetos-arquivos';

    return $base . DIRECTORY_SEPARATOR . 'escopos';
}

/** Valida tamanho, extensão e conteúdo sem confiar no nome enviado pelo usuário. */
function ep_validar_arquivo_escopo(array $arquivo): string
{
    $erro = $arquivo['error'] ?? UPLOAD_ERR_NO_FILE;
    if (!is_int($erro) || $erro !== UPLOAD_ERR_OK) {
        if ($erro === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException('Selecione um arquivo de escopo.');
        }
        if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE) {
            throw new InvalidArgumentException('O arquivo excede o limite de upload do PHP.');
        }
        throw new InvalidArgumentException('Não foi possível receber o arquivo de escopo.');
    }

    $tamanho = $arquivo['size'] ?? null;
    $temporario = $arquivo['tmp_name'] ?? null;
    $nome = $arquivo['name'] ?? null;

    if (!is_int($tamanho) || $tamanho <= 0 || $tamanho > 8 * 1024 * 1024) {
        throw new InvalidArgumentException('O escopo deve ter até 8 MB e não pode estar vazio.');
    }
    if (!is_string($temporario) || !is_uploaded_file($temporario) || !is_string($nome)) {
        throw new InvalidArgumentException('O arquivo recebido não é um upload válido.');
    }

    $extensao = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
    if (!in_array($extensao, ['pdf', 'doc', 'docx'], true)) {
        throw new InvalidArgumentException('Envie um arquivo PDF, DOC ou DOCX.');
    }

    $detector = new finfo(FILEINFO_MIME_TYPE);
    $mime = $detector->file($temporario);
    $handle = fopen($temporario, 'rb');
    if ($handle === false) {
        throw new InvalidArgumentException('Não foi possível verificar o conteúdo do arquivo.');
    }
    $assinatura = fread($handle, 8);
    fclose($handle);

    if ($extensao === 'pdf') {
        if ($mime !== 'application/pdf' || substr($assinatura, 0, 5) !== '%PDF-') {
            throw new InvalidArgumentException('O conteúdo do PDF é inválido.');
        }
    } elseif ($extensao === 'doc') {
        $tiposDoc = ['application/msword', 'application/vnd.ms-office',
            'application/x-ole-storage', 'application/CDFV2', 'application/octet-stream'];
        if (!in_array($mime, $tiposDoc, true)
            || $assinatura !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            throw new InvalidArgumentException('O conteúdo do documento DOC é inválido.');
        }
    } else {
        $tiposDocx = ['application/zip',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/octet-stream'];
        if (!in_array($mime, $tiposDocx, true)
            || substr($assinatura, 0, 2) !== 'PK'
            || !class_exists('ZipArchive')) {
            throw new InvalidArgumentException('Não foi possível validar o documento DOCX. Verifique a extensão ZIP do PHP.');
        }
        $zip = new ZipArchive();
        if ($zip->open($temporario, ZipArchive::CHECKCONS) !== true) {
            throw new InvalidArgumentException('O arquivo DOCX não é um documento ZIP válido.');
        }
        $valido = $zip->locateName('[Content_Types].xml') !== false
            && $zip->locateName('word/document.xml') !== false;
        $zip->close();
        if (!$valido) {
            throw new InvalidArgumentException('O arquivo não possui a estrutura de um DOCX.');
        }
    }

    return $extensao;
}

/** Retorna caminho RELATIVO para armazenar na coluna `escopo`. */
function ep_salvar_arquivo_escopo(array $arquivo): string
{
    $extensao = ep_validar_arquivo_escopo($arquivo);
    $diretorio = ep_diretorio_escopos();

    if (!is_dir($diretorio) && !mkdir($diretorio, 0700, true) && !is_dir($diretorio)) {
        throw new RuntimeException('Não foi possível criar a pasta de arquivos privados.');
    }
    if (!is_writable($diretorio)) {
        throw new RuntimeException('A pasta de arquivos privados não permite escrita.');
    }

    $nome = bin2hex(random_bytes(16)) . '.' . $extensao;
    $destino = $diretorio . DIRECTORY_SEPARATOR . $nome;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new RuntimeException('Não foi possível salvar o arquivo de escopo.');
    }
    @chmod($destino, 0600);
    return 'escopos/' . $nome;
}

/** Remove somente arquivos com nomes aleatórios gerados por este sistema. */
function ep_remover_arquivo_escopo(string $caminhoRelativo): void
{
    if (!preg_match('~^escopos/[a-f0-9]{32}\.(pdf|doc|docx)$~D', $caminhoRelativo)) {
        return;
    }
    $caminho = ep_diretorio_escopos()
        . DIRECTORY_SEPARATOR . basename($caminhoRelativo);
    if (is_file($caminho)) {
        @unlink($caminho);
    }
}


/** Resolve um escopo privado gerado pelo aplicativo; nunca aceita caminhos arbitrários. */
function ep_localizar_arquivo_escopo($caminhoRelativo): ?string
{
    if (!is_string($caminhoRelativo)
        || !preg_match('~^escopos/[a-f0-9]{32}\.(pdf|doc|docx)$~D', $caminhoRelativo)) {
        return null;
    }

    $diretorio = realpath(ep_diretorio_escopos());
    if ($diretorio === false) {
        return null;
    }

    $caminho = realpath($diretorio . DIRECTORY_SEPARATOR . basename($caminhoRelativo));
    if ($caminho === false || dirname($caminho) !== $diretorio || !is_file($caminho)
        || !is_readable($caminho)) {
        return null;
    }

    return $caminho;
}

/** Os documentos de contratos e acompanhamento ficam na mesma área privada dos escopos. */
function ep_diretorio_documentos_privados(string $tipo): ?string
{
    $pasta = [
        'convenio' => 'convenios',
        'acompanhamento' => 'acompanhamento',
    ][$tipo] ?? null;

    if ($pasta === null) {
        return null;
    }
    return dirname(ep_diretorio_escopos()) . DIRECTORY_SEPARATOR . $pasta;
}

/** Aceita apenas caminhos relativos nos diretórios privados conhecidos. */
function ep_localizar_documento_privado($caminhoRelativo, string $tipo): ?string
{
    $pasta = [
        'convenio' => 'convenios',
        'acompanhamento' => 'acompanhamento',
    ][$tipo] ?? null;
    if ($pasta === null || !is_string($caminhoRelativo)) {
        return null;
    }

    $expressao = '~^' . preg_quote($pasta, '~')
        . '/[A-Za-z0-9_-]{1,100}\.(pdf|doc|docx)$~iD';
    if (!preg_match($expressao, $caminhoRelativo)) {
        return null;
    }

    $diretorio = ep_diretorio_documentos_privados($tipo);
    $diretorioReal = $diretorio === null ? false : realpath($diretorio);
    if ($diretorioReal === false) {
        return null;
    }

    $caminho = realpath($diretorioReal . DIRECTORY_SEPARATOR . basename($caminhoRelativo));
    if ($caminho === false || dirname($caminho) !== $diretorioReal
        || !is_file($caminho) || !is_readable($caminho)) {
        return null;
    }

    return $caminho;
}

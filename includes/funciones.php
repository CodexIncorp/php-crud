<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const UPLOAD_DIR = __DIR__ . '/../uploads/';

/** Escapa salida HTML (XSS) */
function e(?string $valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirigir(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

/* ---------- Mensajes flash y estado del formulario ---------- */
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'msg' => $mensaje];
}

function flash_leer(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function estado_formulario(): array
{
    $old = $_SESSION['old'] ?? [];
    $err = $_SESSION['errores'] ?? [];
    unset($_SESSION['old'], $_SESSION['errores']);
    return [$old, $err];
}

/* ---------- Datos y validación ---------- */
function leer_id(array $origen): ?int
{
    $id = filter_var($origen['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

function contacto_vacio(): array
{
    return [
        'id' => null,
        'nombre' => '',
        'apellido' => '',
        'telefono' => '',
        'foto' => '',
        'email' => '',
        'direccion' => '',
        'notas' => ''
    ];
}

function datos_contacto(array $post): array
{
    $campos = ['nombre', 'apellido', 'telefono', 'email', 'direccion', 'notas'];
    $d = [];
    foreach ($campos as $c) {
        $d[$c] = trim((string)($post[$c] ?? ''));
    }
    return $d;
}

function validar_contacto(array $d): array
{
    $e = [];
    if ($d['nombre'] === '')                  $e['nombre'] = 'El nombre es obligatorio.';
    elseif (mb_strlen($d['nombre']) > 80)     $e['nombre'] = 'Máximo 80 caracteres.';

    if ($d['apellido'] === '')                $e['apellido'] = 'El apellido es obligatorio.';
    elseif (mb_strlen($d['apellido']) > 80)   $e['apellido'] = 'Máximo 80 caracteres.';

    if ($d['telefono'] === '')                                    $e['telefono'] = 'El teléfono es obligatorio.';
    elseif (!preg_match('/^[0-9+\s().-]{7,20}$/', $d['telefono'])) $e['telefono'] = 'Teléfono no válido (7 a 20 dígitos).';

    if ($d['email'] !== '') {
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e['email'] = 'Formato de email no válido.';
        elseif (mb_strlen($d['email']) > 120)                $e['email'] = 'Máximo 120 caracteres.';
    }
    if (mb_strlen($d['direccion']) > 255) $e['direccion'] = 'Máximo 255 caracteres.';
    if (mb_strlen($d['notas']) > 2000)    $e['notas']     = 'Máximo 2000 caracteres.';
    return $e;
}

/* ---------- Foto ---------- */
/** @return array{0:?string,1:?string} [nombreArchivo, error] */
function procesar_foto(array $file, bool $obligatoria): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $obligatoria ? [null, 'La foto es obligatoria.'] : [null, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK)     return [null, 'No se pudo subir la foto.'];
    if ($file['size'] > 2 * 1024 * 1024)      return [null, 'La foto no debe superar 2 MB.'];

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($permitidos[$mime]))           return [null, 'Formato no permitido (solo JPG, PNG o WEBP).'];

    $nombre = bin2hex(random_bytes(16)) . '.' . $permitidos[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $nombre)) {
        return [null, 'No se pudo guardar la foto.'];
    }
    return [$nombre, null];
}

function borrar_foto(?string $nombre): void
{
    if ($nombre && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $nombre)) {
        $ruta = UPLOAD_DIR . $nombre;
        if (is_file($ruta)) unlink($ruta);
    }
}

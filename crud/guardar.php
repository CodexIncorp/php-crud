<?php

declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('crear.php');
}
if (!csrf_valido()) {
    flash('error', 'Token de seguridad inválido. Intenta de nuevo.');
    redirigir('crear.php');
}

$d = datos_contacto($_POST);
$errores = validar_contacto($d);
[$foto, $errFoto] = procesar_foto($_FILES['foto'] ?? [], false);
if ($errFoto !== null) {
    $errores['foto'] = $errFoto;
}
$fotoFinal = $foto ?? 'default.svg';

if ($errores) {
    borrar_foto($foto); // si la foto sí subió pero otro campo falló
    $_SESSION['old'] = $d;
    $_SESSION['errores'] = $errores;
    redirigir('crear.php');
}

$sql = 'INSERT INTO contactos (nombre, apellido, telefono, foto, email, direccion, notas)
        VALUES (:nombre, :apellido, :telefono, :foto, :email, :direccion, :notas)';
try {
    $st = $pdo->prepare($sql);
    $st->execute([
        ':nombre'    => $d['nombre'],
        ':apellido'  => $d['apellido'],
        ':telefono'  => $d['telefono'],
        ':foto'      => $fotoFinal, // <--- cambia $foto por $fotoFinal
        ':email'     => $d['email'] !== '' ? $d['email'] : null,
        ':direccion' => $d['direccion'] !== '' ? $d['direccion'] : null,
        ':notas'     => $d['notas'] !== '' ? $d['notas'] : null,
    ]);
} catch (Throwable $ex) {
    borrar_foto($foto);
    throw $ex; // lo captura el handler de db.php: log + mensaje genérico
}

flash('exito', 'Contacto creado correctamente.');
redirigir('../index.php');

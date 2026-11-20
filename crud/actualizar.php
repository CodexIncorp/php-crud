<?php

declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('../index.php');
}
$id = leer_id($_POST);
if ($id === null) {
    flash('error', 'Solicitud no válida.');
    redirigir('../index.php');
}
if (!csrf_valido()) {
    flash('error', 'Token de seguridad inválido. Intenta de nuevo.');
    redirigir("editar.php?id=$id");
}

$st = $pdo->prepare('SELECT foto FROM contactos WHERE id = :id');
$st->execute([':id' => $id]);
$actual = $st->fetch();
if (!$actual) {
    flash('error', 'El contacto no existe.');
    redirigir('../index.php');
}

$d = datos_contacto($_POST);
$errores = validar_contacto($d);
[$fotoNueva, $errFoto] = procesar_foto($_FILES['foto'] ?? [], false);
if ($errFoto) $errores['foto'] = $errFoto;

if ($errores) {
    borrar_foto($fotoNueva);
    $_SESSION['old'] = $d;
    $_SESSION['errores'] = $errores;
    redirigir("editar.php?id=$id");
}

$sql = 'UPDATE contactos
        SET nombre = :nombre, apellido = :apellido, telefono = :telefono, foto = :foto,
            email = :email, direccion = :direccion, notas = :notas
        WHERE id = :id';
try {
    $st = $pdo->prepare($sql);
    $st->execute([
        ':nombre'    => $d['nombre'],
        ':apellido'  => $d['apellido'],
        ':telefono'  => $d['telefono'],
        ':foto'      => $fotoNueva ?? $actual['foto'],
        ':email'     => $d['email'] !== '' ? $d['email'] : null,
        ':direccion' => $d['direccion'] !== '' ? $d['direccion'] : null,
        ':notas'     => $d['notas'] !== '' ? $d['notas'] : null,
        ':id'        => $id,
    ]);
} catch (Throwable $ex) {
    borrar_foto($fotoNueva);
    throw $ex;
}

if ($fotoNueva !== null) {
    borrar_foto($actual['foto']); // se elimina la foto anterior solo si el UPDATE tuvo éxito
}

flash('exito', 'Contacto actualizado correctamente.');
redirigir('../index.php');

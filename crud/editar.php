<?php

declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/funciones.php';

$id = leer_id($_GET);
$contacto = null;
if ($id !== null) {
    $st = $pdo->prepare('SELECT * FROM contactos WHERE id = :id');
    $st->execute([':id' => $id]);
    $contacto = $st->fetch();
}
if (!$contacto) {
    flash('error', 'El contacto no existe.');
    redirigir('../index.php');
}

[$old, $errores] = estado_formulario();
$c = array_merge($contacto, $old); // si hubo errores, se muestran los datos que escribió

foreach (['email', 'direccion', 'notas'] as $k) {
    $c[$k] = (string)($c[$k] ?? '');
}

$base = '../';
$titulo = 'Editar contacto';
$accion = 'actualizar.php';
$fotoObligatoria = false;
$boton = 'Guardar cambios';

include __DIR__ . '/../includes/header.php';
echo '<h1>Editar contacto</h1>';
include __DIR__ . '/../includes/form_contacto.php';
include __DIR__ . '/../includes/footer.php';

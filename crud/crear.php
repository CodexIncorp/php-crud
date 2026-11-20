<?php

declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/funciones.php';

[$old, $errores] = estado_formulario();
$c = array_merge(contacto_vacio(), $old);

$base = '../';
$titulo = 'Nuevo contacto';
$accion = 'guardar.php';
$fotoObligatoria = true;
$boton = 'Guardar contacto';

include __DIR__ . '/../includes/header.php';
echo '<h1>Nuevo contacto</h1>';
include __DIR__ . '/../includes/form_contacto.php';
include __DIR__ . '/../includes/footer.php';

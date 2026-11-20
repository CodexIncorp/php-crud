<?php

// Variables esperadas: $titulo (string) y $base ('' en raíz, '../' en crud/)
$base = $base ?? '';
$titulo = $titulo ?? 'Agenda';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | Agenda de contactos</title>
    <link rel="stylesheet" href="<?= e($base) ?>css/styles.css">
</head>

<body>
    <?php include __DIR__ . '/menu.php'; ?>
    <main class="contenedor">
        <?php if ($f = flash_leer()): ?>
            <div class="alerta alerta-<?= e($f['tipo']) ?>"><?= e($f['msg']) ?></div>
        <?php endif; ?>

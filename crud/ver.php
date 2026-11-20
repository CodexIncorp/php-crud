<?php

declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/funciones.php';

$id = leer_id($_GET);
$c = null;
if ($id !== null) {
    $st = $pdo->prepare('SELECT * FROM contactos WHERE id = :id');
    $st->execute([':id' => $id]);
    $c = $st->fetch();
}
if (!$c) {
    flash('error', 'El contacto no existe.');
    redirigir('../index.php');
}

$base = '../';
$titulo = $c['nombre'] . ' ' . $c['apellido'];
include __DIR__ . '/../includes/header.php';
?>
<article class="detalle">
    <img class="foto-grande" src="../uploads/<?= e($c['foto']) ?>" alt="Foto de <?= e($c['nombre']) ?>">
    <div>
        <h1><?= e($c['nombre']) ?> <?= e($c['apellido']) ?></h1>
        <dl>
            <dt>Teléfono</dt>
            <dd><?= e($c['telefono']) ?></dd>
            <dt>Email</dt>
            <dd><?= $c['email'] ? e($c['email']) : '—' ?></dd>
            <dt>Dirección</dt>
            <dd><?= $c['direccion'] ? e($c['direccion']) : '—' ?></dd>
            <dt>Notas</dt>
            <dd><?= $c['notas'] ? nl2br(e($c['notas'])) : '—' ?></dd>
        </dl>
        <div class="acciones">
            <a class="btn btn-primario" href="editar.php?id=<?= (int)$c['id'] ?>">Editar</a>
            <a class="btn btn-peligro" href="eliminar.php?id=<?= (int)$c['id'] ?>">Eliminar</a>
            <a class="btn" href="../index.php">Volver</a>
        </div>
    </div>
</article>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php

declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/funciones.php';

// ---- Paso 2: borrado real (solo POST + CSRF) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = leer_id($_POST);
    if ($id === null || !csrf_valido()) {
        flash('error', 'Solicitud no válida.');
        redirigir('../index.php');
    }
    $st = $pdo->prepare('SELECT foto FROM contactos WHERE id = :id');
    $st->execute([':id' => $id]);
    $c = $st->fetch();

    if ($c) {
        $del = $pdo->prepare('DELETE FROM contactos WHERE id = :id');
        $del->execute([':id' => $id]);
        borrar_foto($c['foto']);
        flash('exito', 'Contacto eliminado.');
    } else {
        flash('error', 'El contacto no existe.');
    }
    redirigir('../index.php');
}

// ---- Paso 1: pantalla de confirmación ----
$id = leer_id($_GET);
$c = null;
if ($id !== null) {
    $st = $pdo->prepare('SELECT id, nombre, apellido, telefono, foto FROM contactos WHERE id = :id');
    $st->execute([':id' => $id]);
    $c = $st->fetch();
}
if (!$c) {
    flash('error', 'El contacto no existe.');
    redirigir('../index.php');
}

$base = '../';
$titulo = 'Eliminar contacto';
include __DIR__ . '/../includes/header.php';
?>
<div class="confirmacion">
    <h1>¿Eliminar este contacto?</h1>
    <img class="foto-grande" src="../uploads/<?= e($c['foto']) ?>" alt="">
    <p><strong><?= e($c['nombre']) ?> <?= e($c['apellido']) ?></strong> · <?= e($c['telefono']) ?></p>
    <p>Esta acción no se puede deshacer.</p>
    <form action="eliminar.php" method="post" class="acciones">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
        <button type="submit" class="btn btn-peligro">Sí, eliminar</button>
        <a class="btn" href="../index.php">Cancelar</a>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

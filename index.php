<?php

declare(strict_types=1);
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/funciones.php';

$q = trim((string)($_GET['q'] ?? ''));

if ($q !== '') {
    $sql = 'SELECT id, nombre, apellido, telefono, foto FROM contactos
            WHERE LOWER(nombre) LIKE LOWER(:q1) OR LOWER(apellido) LIKE LOWER(:q2) OR telefono LIKE :q3
            ORDER BY apellido, nombre';
    $st = $pdo->prepare($sql);
    $like = '%' . $q . '%';
    $st->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
} else {
    $st = $pdo->prepare('SELECT id, nombre, apellido, telefono, foto FROM contactos ORDER BY apellido, nombre');
    $st->execute();
}
$contactos = $st->fetchAll();

$base = '';
$titulo = 'Contactos';
include __DIR__ . '/includes/header.php';
?>
<h1>Mis contactos</h1>

<form method="get" class="busqueda">
    <input type="search" name="q" placeholder="Buscar por nombre o teléfono" value="<?= e($q) ?>">
    <button class="btn" type="submit">Buscar</button>
</form>

<?php if (!$contactos): ?>
    <p class="vacio">No hay contactos<?= $q !== '' ? ' que coincidan con la búsqueda' : '' ?>.</p>
<?php else: ?>
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contactos as $c): ?>
                    <tr>
                        <td><img class="avatar" src="uploads/<?= e($c['foto']) ?>" alt="Foto de <?= e($c['nombre']) ?>"></td>
                        <td><?= e($c['apellido']) ?>, <?= e($c['nombre']) ?></td>
                        <td><?= e($c['telefono']) ?></td>
                        <td class="acciones">
                            <a class="btn" href="crud/ver.php?id=<?= (int)$c['id'] ?>">Ver</a>
                            <a class="btn" href="crud/editar.php?id=<?= (int)$c['id'] ?>">Editar</a>
                            <a class="btn btn-peligro" href="crud/eliminar.php?id=<?= (int)$c['id'] ?>">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

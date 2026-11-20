<?php
// Variables: $c, $errores, $accion, $fotoObligatoria, $base, $boton
function err_campo(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? '<span class="error">' . e($errores[$campo]) . '</span>' : '';
}
?>
<form action="<?= e($accion) ?>" method="post" enctype="multipart/form-data" class="formulario">
    <?= csrf_campo() ?>
    <?php if (!empty($c['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
    <?php endif; ?>

    <label>Nombre *
        <input type="text" name="nombre" maxlength="80" required value="<?= e($c['nombre']) ?>">
        <?= err_campo($errores, 'nombre') ?>
    </label>

    <label>Apellido *
        <input type="text" name="apellido" maxlength="80" required value="<?= e($c['apellido']) ?>">
        <?= err_campo($errores, 'apellido') ?>
    </label>

    <label>Teléfono *
        <input type="tel" name="telefono" maxlength="20" required
            pattern="[0-9+\s().\-]{7,20}" title="7 a 20 caracteres: dígitos, +, espacios, ( ) . -"
            value="<?= e($c['telefono']) ?>">
        <?= err_campo($errores, 'telefono') ?>
    </label>

    <label>Foto <?= $fotoObligatoria ? '*' : '(déjala vacía para conservar la actual)' ?>
        <?php if (!empty($c['foto'])): ?>
            <img class="miniatura" src="<?= e($base) ?>uploads/<?= e($c['foto']) ?>" alt="Foto actual">
        <?php endif; ?>
        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp"
            <?= $fotoObligatoria ? 'required' : '' ?>>
        <?= err_campo($errores, 'foto') ?>
    </label>

    <label>Email
        <input type="email" name="email" maxlength="120" value="<?= e($c['email']) ?>">
        <?= err_campo($errores, 'email') ?>
    </label>

    <label>Dirección
        <input type="text" name="direccion" maxlength="255" value="<?= e($c['direccion']) ?>">
        <?= err_campo($errores, 'direccion') ?>
    </label>

    <label>Notas
        <textarea name="notas" rows="4" maxlength="2000"><?= e($c['notas']) ?></textarea>
        <?= err_campo($errores, 'notas') ?>
    </label>

    <div class="acciones">
        <button type="submit" class="btn btn-primario"><?= e($boton) ?></button>
        <a class="btn" href="<?= e($base) ?>index.php">Cancelar</a>
    </div>
</form>

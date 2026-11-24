<?php
// Variables: $c, $errores, $accion, $base, $boton
function err_campo(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? '<span class="error">' . e($errores[$campo]) . '</span>' : '';
}

function attr_error(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? ' class="campo-error" aria-invalid="true"' : '';
}
?>
<form action="<?= e($accion) ?>" method="post" enctype="multipart/form-data" class="formulario" id="form-contacto">
    <?= csrf_campo() ?>
    <?php if (!empty($c['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
    <?php endif; ?>

    <p class="nota-obligatorios">Los campos marcados con <span class="requerido">*</span> son obligatorios.</p>

    <label>
        <span>Nombre <span class="requerido">*</span></span>
        <input type="text" name="nombre" maxlength="80" required
            value="<?= e($c['nombre']) ?>"<?= attr_error($errores, 'nombre') ?>>
        <?= err_campo($errores, 'nombre') ?>
    </label>

    <label>
        <span>Apellido <span class="requerido">*</span></span>
        <input type="text" name="apellido" maxlength="80" required
            value="<?= e($c['apellido']) ?>"<?= attr_error($errores, 'apellido') ?>>
        <?= err_campo($errores, 'apellido') ?>
    </label>

    <label>
        <span>Teléfono <span class="requerido">*</span></span>
        <input type="tel" name="telefono" maxlength="20" required
            pattern="[0-9+\s().\-]{7,20}"
            title="7 a 20 caracteres: dígitos, +, espacios, ( ) . -"
            value="<?= e($c['telefono']) ?>"<?= attr_error($errores, 'telefono') ?>>
        <?= err_campo($errores, 'telefono') ?>
    </label>

    <label>
        <span>Foto <span class="requerido">*</span></span>
        <?php if (!empty($c['foto'])): ?>
            <img class="miniatura" src="<?= e($base) ?>uploads/<?= e($c['foto']) ?>" alt="Foto actual">
        <?php endif; ?>
        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" required<?= attr_error($errores, 'foto') ?>>
        <?= err_campo($errores, 'foto') ?>
    </label>

    <label>
        <span>Email <span class="opcional">(opcional)</span></span>
        <input type="email" name="email" maxlength="120"
            value="<?= e($c['email']) ?>"<?= attr_error($errores, 'email') ?>>
        <?= err_campo($errores, 'email') ?>
    </label>

    <label>
        <span>Dirección <span class="opcional">(opcional)</span></span>
        <input type="text" name="direccion" maxlength="255"
            value="<?= e($c['direccion']) ?>"<?= attr_error($errores, 'direccion') ?>>
        <?= err_campo($errores, 'direccion') ?>
    </label>

    <label>
        <span>Notas <span class="opcional">(opcional)</span></span>
        <textarea name="notas" rows="4" maxlength="2000"<?= attr_error($errores, 'notas') ?>><?= e($c['notas']) ?></textarea>
        <?= err_campo($errores, 'notas') ?>
    </label>

    <div class="acciones">
        <button type="submit" class="btn btn-primario"><?= e($boton) ?></button>
        <a class="btn" href="<?= e($base) ?>index.php">Cancelar</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-contacto');
    if (!form) return;

    // Resalta el campo inmediatamente si el navegador bloquea el envío por dato faltante/inválido
    form.addEventListener('invalid', (e) => {
        e.target.classList.add('campo-error');
        e.target.setAttribute('aria-invalid', 'true');
    }, true);

    // Quita el resaltado en cuanto el usuario corrige el dato
    form.addEventListener('input', (e) => {
        if (e.target.checkValidity()) {
            e.target.classList.remove('campo-error');
            e.target.removeAttribute('aria-invalid');
        }
    });
    form.addEventListener('change', (e) => {
        if (e.target.checkValidity()) {
            e.target.classList.remove('campo-error');
            e.target.removeAttribute('aria-invalid');
        }
    });
});
</script>
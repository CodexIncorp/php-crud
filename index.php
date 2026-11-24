<?php

declare(strict_types=1);
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/funciones.php';

// 1. Leer y validar parámetros desde la URL
$q       = trim((string)($_GET['q'] ?? ''));
$letra   = mb_strtoupper(trim((string)($_GET['letra'] ?? '')), 'UTF-8');
$orden   = (string)($_GET['orden'] ?? 'apellido_asc');
$fEmail  = isset($_GET['email']) && $_GET['email'] === '1';
$fDir    = isset($_GET['dir']) && $_GET['dir'] === '1';
$fFoto   = isset($_GET['foto']) && $_GET['foto'] === '1';

$alfabeto = [
    'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
    'N', 'Ñ', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
];
if (!in_array($letra, $alfabeto, true)) {
    $letra = '';
}

$ordenesDisponibles = [
    'apellido_asc' => 'Apellido (A–Z)',
    'nombre_asc'   => 'Nombre (A–Z)',
    'recientes'    => 'Más recientes',
];
if (!isset($ordenesDisponibles[$orden])) {
    $orden = 'apellido_asc';
}

// Mapa para agrupar vocales acentuadas bajo su letra base (portable PG/MySQL)
$mapaAcentos = [
    'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
];
$variantesLetra = [
    'A' => ['A', 'Á'],
    'E' => ['E', 'É'],
    'I' => ['I', 'Í'],
    'O' => ['O', 'Ó'],
    'U' => ['U', 'Ú', 'Ü'],
];

// Helper para construir URLs preservando el resto de filtros activos
$estadoActual = [
    'q'     => $q !== '' ? $q : null,
    'letra' => $letra !== '' ? $letra : null,
    'email' => $fEmail ? '1' : null,
    'dir'   => $fDir ? '1' : null,
    'foto'  => $fFoto ? '1' : null,
    'orden' => $orden !== 'apellido_asc' ? $orden : null,
];

$urlCon = static function (array $cambios) use ($estadoActual): string {
    $params = array_filter(
        array_merge($estadoActual, $cambios),
        static fn($v): bool => $v !== null && $v !== ''
    );
    return $params ? 'index.php?' . http_build_query($params) : 'index.php';
};

// 2. Obtener total general y letras disponibles según el campo de orden
$colLetra = ($orden === 'nombre_asc') ? 'nombre' : 'apellido';
$totalGeneral = (int)$pdo->query('SELECT COUNT(*) FROM contactos')->fetchColumn();

$stLetras = $pdo->query("SELECT DISTINCT UPPER(SUBSTR(TRIM({$colLetra}), 1, 1)) FROM contactos");
$letrasDisponibles = [];
foreach ($stLetras->fetchAll(PDO::FETCH_COLUMN) as $ini) {
    $ini = mb_strtoupper((string)$ini, 'UTF-8');
    $ini = $mapaAcentos[$ini] ?? $ini;
    if (in_array($ini, $alfabeto, true)) {
        $letrasDisponibles[$ini] = true;
    }
}

// 3. Construir consulta filtrada de forma modular
$condiciones = [];
$params      = [];

if ($q !== '') {
    $qEsc = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q);
    $like = '%' . $qEsc . '%';
    $condiciones[] = "(LOWER(CONCAT(nombre, ' ', apellido)) LIKE LOWER(:q1)
                       OR LOWER(CONCAT(apellido, ' ', nombre)) LIKE LOWER(:q2)
                       OR telefono LIKE :q3
                       OR LOWER(COALESCE(email, '')) LIKE LOWER(:q4)
                       OR LOWER(COALESCE(direccion, '')) LIKE LOWER(:q5)
                       OR LOWER(COALESCE(notas, '')) LIKE LOWER(:q6))";
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
    $params[':q4'] = $like;
    $params[':q5'] = $like;
    $params[':q6'] = $like;
}

if ($letra !== '') {
    $variantes = $variantesLetra[$letra] ?? [$letra];
    $placeholders = [];
    foreach ($variantes as $idx => $v) {
        $key = ':l' . $idx;
        $placeholders[] = $key;
        $params[$key] = $v;
    }
    $condiciones[] = "UPPER(SUBSTR(TRIM({$colLetra}), 1, 1)) IN (" . implode(', ', $placeholders) . ")";
}

if ($fEmail) {
    $condiciones[] = "email IS NOT NULL AND email <> ''";
}
if ($fDir) {
    $condiciones[] = "direccion IS NOT NULL AND direccion <> ''";
}
if ($fFoto) {
    $condiciones[] = "foto <> 'default.svg'";
}

$where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

$orderBy = match ($orden) {
    'nombre_asc' => 'ORDER BY nombre ASC, apellido ASC',
    'recientes'  => 'ORDER BY id DESC',
    default      => 'ORDER BY apellido ASC, nombre ASC',
};

$sql = "SELECT id, nombre, apellido, telefono, email, direccion, foto
        FROM contactos
        {$where}
        {$orderBy}";
$st = $pdo->prepare($sql);
$st->execute($params);
$contactos = $st->fetchAll();

// 4. Armar lista de filtros activos para los chips removibles
$filtrosActivos = [];
if ($q !== '') {
    $filtrosActivos[] = ['etiqueta' => 'Búsqueda: "' . $q . '"', 'url' => $urlCon(['q' => null])];
}
if ($letra !== '') {
    $filtrosActivos[] = ['etiqueta' => 'Inicial: ' . $letra, 'url' => $urlCon(['letra' => null])];
}
if ($fEmail) {
    $filtrosActivos[] = ['etiqueta' => 'Con email', 'url' => $urlCon(['email' => null])];
}
if ($fDir) {
    $filtrosActivos[] = ['etiqueta' => 'Con dirección', 'url' => $urlCon(['dir' => null])];
}
if ($fFoto) {
    $filtrosActivos[] = ['etiqueta' => 'Con foto propia', 'url' => $urlCon(['foto' => null])];
}

$hayFiltros = !empty($filtrosActivos);

$base   = '';
$titulo = 'Contactos';
include __DIR__ . '/includes/header.php';
?>
<h1>Mis contactos</h1>

<section class="panel-exploracion" aria-label="Búsqueda y filtros">
    <!-- Buscador principal + Ordenamiento separado -->
    <form method="get" class="barra-superior">
        <?php if ($letra !== ''): ?><input type="hidden" name="letra" value="<?= e($letra) ?>"><?php endif; ?>
        <?php if ($fEmail): ?><input type="hidden" name="email" value="1"><?php endif; ?>
        <?php if ($fDir): ?><input type="hidden" name="dir" value="1"><?php endif; ?>
        <?php if ($fFoto): ?><input type="hidden" name="foto" value="1"><?php endif; ?>

        <div class="buscador-caja">
            <input type="search" name="q" placeholder="Buscar por nombre, teléfono, email o dirección..." value="<?= e($q) ?>">
            <button class="btn btn-primario" type="submit">Buscar</button>
        </div>

        <label class="orden-control">
            <span>Ordenar:</span>
            <select name="orden" onchange="this.form.submit()">
                <?php foreach ($ordenesDisponibles as $valor => $etiqueta): ?>
                    <option value="<?= e($valor) ?>" <?= $orden === $valor ? 'selected' : '' ?>>
                        <?= e($etiqueta) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>

    <!-- Chips de filtros booleanos combinables -->
    <div class="chips-fila" role="group" aria-label="Filtros rápidos">
        <span class="chips-titulo">Filtros:</span>
        <a class="chip <?= $fEmail ? 'chip-activo' : '' ?>" href="<?= e($urlCon(['email' => $fEmail ? null : '1'])) ?>">
            Con email
        </a>
        <a class="chip <?= $fDir ? 'chip-activo' : '' ?>" href="<?= e($urlCon(['dir' => $fDir ? null : '1'])) ?>">
            Con dirección
        </a>
        <a class="chip <?= $fFoto ? 'chip-activo' : '' ?>" href="<?= e($urlCon(['foto' => $fFoto ? null : '1'])) ?>">
            Con foto propia
        </a>
    </div>

    <!-- Índice alfabético A–Z -->
    <?php if ($totalGeneral > 0): ?>
        <nav class="indice-az" aria-label="Filtrar por letra inicial">
            <a class="az-item <?= $letra === '' ? 'az-activo' : '' ?>" href="<?= e($urlCon(['letra' => null])) ?>">Todas</a>
            <?php foreach ($alfabeto as $l): ?>
                <?php $activa = ($letra === $l); ?>
                <?php if (isset($letrasDisponibles[$l]) || $activa): ?>
                    <a class="az-item <?= $activa ? 'az-activo' : '' ?>"
                       href="<?= e($urlCon(['letra' => $activa ? null : $l])) ?>">
                        <?= e($l) ?>
                    </a>
                <?php else: ?>
                    <span class="az-item az-deshabilitada" aria-disabled="true"><?= e($l) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
</section>

<!-- Resumen de resultados y etiquetas de filtros activos -->
<div class="barra-resumen">
    <p class="resumen-conteo">
        Mostrando <strong><?= count($contactos) ?></strong> de <?= $totalGeneral ?> contacto(s)
    </p>

    <?php if ($hayFiltros): ?>
        <div class="filtros-activos">
            <?php foreach ($filtrosActivos as $fa): ?>
                <a class="tag-activo" href="<?= e($fa['url']) ?>" title="Quitar filtro">
                    <?= e($fa['etiqueta']) ?> <span aria-hidden="true">&times;</span>
                </a>
            <?php endforeach; ?>
            <a class="limpiar-todo" href="<?= e($urlCon(['q' => null, 'letra' => null, 'email' => null, 'dir' => null, 'foto' => null])) ?>">
                Limpiar todo
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if (!$contactos): ?>
    <div class="estado-vacio">
        <?php if ($hayFiltros): ?>
            <p>No se encontraron contactos con los filtros aplicados.</p>
            <a class="btn btn-primario" href="index.php">Quitar filtros y ver todos</a>
        <?php else: ?>
            <p>Aún no tienes contactos guardados.</p>
            <a class="btn btn-primario" href="crud/crear.php">+ Agregar primer contacto</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="tabla-wrap">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Contacto</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contactos as $c): ?>
                    <tr>
                        <td><img class="avatar" src="uploads/<?= e($c['foto']) ?>" alt="Foto de <?= e($c['nombre']) ?>"></td>
                        <td>
                            <strong><?= e($c['apellido']) ?>, <?= e($c['nombre']) ?></strong>
                            <?php if (!empty($c['email'])): ?>
                                <br><small class="subdato"><?= e($c['email']) ?></small>
                            <?php elseif (!empty($c['direccion'])): ?>
                                <br><small class="subdato"><?= e($c['direccion']) ?></small>
                            <?php endif; ?>
                        </td>
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
<?php

declare(strict_types=1);
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/funciones.php';

/**
 * Revierte el escape de caracteres de vCard 3.0
 */
function vcard_unescape(string $valor): string
{
    $valor = str_replace(['\n', '\N'], "\n", $valor);
    $valor = str_replace(['\;', '\,', '\\\\'], [';', ',', '\\'], $valor);
    return trim($valor);
}

/**
 * Parsea un archivo .vcf y devuelve un arreglo de contactos crudos
 */
function parsear_vcards(string $contenido): array
{
    // Normalizar saltos de línea y desplegar líneas continuadas (folding RFC)
    $contenido = str_replace(["\r\n", "\r"], "\n", $contenido);
    $contenido = preg_replace("/\n[ \t]/", '', $contenido) ?? $contenido;

    preg_match_all('/BEGIN:VCARD(.*?)END:VCARD/is', $contenido, $bloques);
    $resultado = [];

    foreach ($bloques[1] as $bloque) {
        $c = [
            'nombre'    => '',
            'apellido'  => '',
            'telefono'  => '',
            'email'     => '',
            'direccion' => '',
            'notas'     => '',
        ];
        $fn = '';

        foreach (explode("\n", $bloque) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || !str_contains($linea, ':')) {
                continue;
            }

            [$claveCompleta, $valor] = explode(':', $linea, 2);
            // Quitar parámetros como ;TYPE=CELL o ;CHARSET=UTF-8
            $clave = strtoupper(explode(';', $claveCompleta, 2)[0]);

            switch ($clave) {
                case 'N':
                    $partes = explode(';', $valor);
                    $c['apellido'] = vcard_unescape($partes[0] ?? '');
                    $c['nombre']   = vcard_unescape($partes[1] ?? '');
                    break;

                case 'FN':
                    $fn = vcard_unescape($valor);
                    break;

                case 'TEL':
                    if ($c['telefono'] === '') {
                        $c['telefono'] = trim(preg_replace('/[^\d+\s().\-]/', '', $valor) ?? '');
                    }
                    break;

                case 'EMAIL':
                    if ($c['email'] === '') {
                        $c['email'] = vcard_unescape($valor);
                    }
                    break;

                case 'ADR':
                    if ($c['direccion'] === '') {
                        $partes = array_filter(
                            array_map('vcard_unescape', explode(';', $valor)),
                            static fn(string $p): bool => $p !== ''
                        );
                        $c['direccion'] = implode(', ', $partes);
                    }
                    break;

                case 'NOTE':
                    if ($c['notas'] === '') {
                        $c['notas'] = vcard_unescape($valor);
                    }
                    break;
            }
        }

        // Si el vCard no trajo N: completo, usar FN: como respaldo
        if ($c['nombre'] === '' && $fn !== '') {
            $partesFn = preg_split('/\s+/', $fn, 2) ?: [];
            $c['nombre'] = $partesFn[0] ?? '';
            if ($c['apellido'] === '') {
                $c['apellido'] = $partesFn[1] ?? '-';
            }
        } elseif ($c['apellido'] === '' && $c['nombre'] !== '') {
            $c['apellido'] = '-';
        }

        $resultado[] = $c;
    }

    return $resultado;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        flash('error', 'Sesión inválida. Intenta de nuevo.');
        redirigir('importar.php');
    }

    $archivo = $_FILES['vcard'] ?? null;

    if (!$archivo || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Selecciona un archivo .vcf para importar.';
    } elseif ($archivo['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error al subir el archivo.';
    } elseif ($archivo['size'] > 2 * 1024 * 1024) {
        $error = 'El archivo no debe superar 2 MB.';
    } else {
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if ($ext !== 'vcf') {
            $error = 'Formato no permitido. Sube un archivo con extensión .vcf.';
        } else {
            $contenido = file_get_contents($archivo['tmp_name']);
            $contactos = $contenido !== false ? parsear_vcards($contenido) : [];

            if (!$contactos) {
                $error = 'No se encontraron contactos válidos dentro del archivo vCard.';
            } else {
                $st = $pdo->prepare(
                    'INSERT INTO contactos (nombre, apellido, telefono, foto, email, direccion, notas)
                     VALUES (:nombre, :apellido, :telefono, :foto, :email, :direccion, :notas)'
                );

                $importados = 0;
                $omitidos = 0;

                try {
                    $pdo->beginTransaction();

                    foreach ($contactos as $c) {
                        $erroresContacto = validar_contacto($c);
                        if ($erroresContacto) {
                            $omitidos++;
                            continue;
                        }

                        $st->execute([
                            ':nombre'    => $c['nombre'],
                            ':apellido'  => $c['apellido'],
                            ':telefono'  => $c['telefono'],
                            ':foto'      => 'default.svg',
                            ':email'     => $c['email'] !== '' ? $c['email'] : null,
                            ':direccion' => $c['direccion'] !== '' ? $c['direccion'] : null,
                            ':notas'     => $c['notas'] !== '' ? $c['notas'] : null,
                        ]);
                        $importados++;
                    }

                    $pdo->commit();

                    if ($importados > 0) {
                        $msg = "Se importaron {$importados} contacto(s).";
                        if ($omitidos > 0) {
                            $msg .= " Se omitieron {$omitidos} por datos incompletos o inválidos.";
                        }
                        flash('ok', $msg);
                        redirigir('../index.php');
                    } else {
                        $error = "No se importó ningún contacto ({$omitidos} omitidos por datos incompletos o teléfono inválido).";
                    }
                } catch (Throwable $ex) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('Error al importar vCard: ' . $ex->getMessage());
                    $error = 'Ocurrió un error al guardar los contactos en la base de datos.';
                }
            }
        }
    }
}

$base = '../';
$titulo = 'Importar contactos';
include __DIR__ . '/../includes/header.php';
?>
<h1>Importar contactos (vCard)</h1>

<?php if ($error !== null): ?>
    <p class="error"><?= e($error) ?></p>
<?php endif; ?>

<form action="importar.php" method="post" enctype="multipart/form-data" class="formulario">
    <?= csrf_campo() ?>

    <p class="nota-obligatorios">
        Sube un archivo <strong>.vcf</strong> (vCard). Los contactos importados usarán un avatar por defecto y se omitirán los que no tengan nombre o teléfono válido.
    </p>

    <label>
        <span>Archivo vCard (.vcf) <span class="requerido">*</span></span>
        <input type="file" name="vcard" accept=".vcf,text/vcard,text/x-vcard" required>
    </label>

    <div class="acciones">
        <button type="submit" class="btn btn-primario">Importar</button>
        <a class="btn" href="../index.php">Cancelar</a>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
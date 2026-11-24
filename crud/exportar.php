<?php

declare(strict_types=1);
require __DIR__ . '/../config/db.php';


function vcard_escape(?string $valor): string
{
    if ($valor === null || $valor === '') {
        return '';
    }
    $valor = str_replace('\\', '\\\\', $valor);
    $valor = str_replace(["\r\n", "\r", "\n"], '\n', $valor);
    $valor = str_replace([';', ','], ['\;', '\,'], $valor);
    return $valor;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $st = $pdo->prepare('SELECT nombre, apellido, telefono, email, direccion, notas FROM contactos WHERE id = :id');
    $st->execute([':id' => $id]);
    $contactos = $st->fetchAll();

    if (!$contactos) {
        http_response_code(404);
        exit('Contacto no encontrado.');
    }
    $nombreArchivo = 'contacto_' . $id . '.vcf';
} else {
    $st = $pdo->query('SELECT nombre, apellido, telefono, email, direccion, notas FROM contactos ORDER BY apellido, nombre');
    $contactos = $st->fetchAll();
    $nombreArchivo = 'contactos_' . date('Y-m-d') . '.vcf';
}

header('Content-Type: text/vcard; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('X-Content-Type-Options: nosniff');

$out = fopen('php://output', 'wb');

foreach ($contactos as $c) {
    $nombre   = vcard_escape($c['nombre'] ?? '');
    $apellido = vcard_escape($c['apellido'] ?? '');
    $fn       = trim($nombre . ' ' . $apellido);
    $tel      = preg_replace('/[^\d+\s().\-]/', '', (string)($c['telefono'] ?? ''));

    $lineas = [
        'BEGIN:VCARD',
        'VERSION:3.0',
        "N:{$apellido};{$nombre};;;",
        "FN:{$fn}",
        "TEL;TYPE=CELL:{$tel}",
    ];

    if (!empty($c['email'])) {
        $lineas[] = 'EMAIL;TYPE=INTERNET:' . vcard_escape($c['email']);
    }

    if (!empty($c['direccion'])) {
        $lineas[] = 'ADR;TYPE=HOME:;;' . vcard_escape($c['direccion']) . ';;;;';
    }

    if (!empty($c['notas'])) {
        $lineas[] = 'NOTE:' . vcard_escape($c['notas']);
    }

    $lineas[] = 'END:VCARD';

    fwrite($out, implode("\r\n", $lineas) . "\r\n");
}

fclose($out);
exit;
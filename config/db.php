<?php

declare(strict_types=1);

$cred = require __DIR__ . '/credenciales.php';

// Nunca mostrar errores técnicos al usuario: se registran en el log.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

set_exception_handler(function (Throwable $e): void {
    error_log('[Agenda] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Error</title></head>'
        . '<body><p>Ocurrió un error inesperado. Inténtalo más tarde.</p></body></html>';
});

if ($cred['driver'] === 'pgsql') {
    $dsn = "pgsql:host={$cred['host']};port={$cred['port']};dbname={$cred['dbname']}";
} else {
    $dsn = "mysql:host={$cred['host']};port={$cred['port']};dbname={$cred['dbname']};charset=utf8mb4";
}

$pdo = new PDO($dsn, $cred['user'], $cred['pass'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,   // prepared statements reales
]);

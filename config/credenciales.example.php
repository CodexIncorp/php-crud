<?php
// Local con PostgreSQL. En el hosting: driver 'mysql', puerto 3306 y los datos del panel.
return [
    'driver' => 'pgsql',        // 'pgsql' o 'mysql'
    'host'   => 'localhost',
    'port'   => '5432',
    'dbname' => 'agenda',
    'user'   => 'postgres',
    'pass'   => 'password',
];

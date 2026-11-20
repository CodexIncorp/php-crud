-- Ejecutar desde psql o pgAdmin

CREATE DATABASE IF NOT EXISTS agenda ENCODING 'UTF8';
\c agenda

CREATE TABLE IF NOT EXISTS contactos (
    id        INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre    VARCHAR(80)  NOT NULL,
    apellido  VARCHAR(80)  NOT NULL,
    telefono  VARCHAR(20)  NOT NULL,
    foto      VARCHAR(255) NOT NULL,
    email     VARCHAR(120),
    direccion VARCHAR(255),
    notas     TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

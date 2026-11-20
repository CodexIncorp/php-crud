# Agenda de contactos

Aplicación web dinámica para gestionar una agenda de contactos con operaciones CRUD completas, desarrollada con **PHP** y **PDO**.

**Sitio publicado:** https://SUBDOMINIO.infinityfreeapp.com <!-- Modificar tras publicar -->

## Objetivo

Demostrar la aplicación práctica de:

- Conexión a base de datos con PDO
- Consultas seguras con sentencias preparadas
- Formularios y validación en servidor
- Manipulación de datos desde PHP
- Buenas prácticas de seguridad

## Funcionalidades

| Operación | Página | Descripción |
|---|---|---|
| READ (lista) | `index.php` | Listado de contactos con búsqueda por nombre o teléfono |
| CREATE (vista) | `crud/crear.php` | Formulario de alta |
| CREATE (lógica) | `crud/guardar.php` | Valida e inserta (INSERT) |
| READ (detalle) | `crud/ver.php` | Detalle de un contacto |
| UPDATE (vista) | `crud/editar.php` | Formulario con datos cargados |
| UPDATE (lógica) | `crud/actualizar.php` | Valida y actualiza (UPDATE) |
| DELETE | `crud/eliminar.php` | Confirmación previa y borrado por POST |

### Campos del contacto

`id`, `nombre`, `apellido`, `telefono` (obligatorio), `foto` (obligatoria), `email`, `direccion`, `notas` (opcionales).

## Tecnologías

- PHP 8.x (PDO)
- MySQL / MariaDB en producción · PostgreSQL en desarrollo local (el código es portable)
- HTML5 + CSS3 externo
- Hosting: InfinityFree

## Seguridad implementada

- Sentencias preparadas reales (`prepare` / `execute`, `ATTR_EMULATE_PREPARES = false`) contra inyección SQL
- Escape de toda salida con `htmlspecialchars()` (función `e()`) contra XSS
- Validación en cliente (HTML5) y en servidor (PHP)
- Token CSRF en todos los formularios POST
- Operaciones de escritura y borrado solo por POST, con confirmación previa al eliminar
- Subida de fotos validada por tipo MIME real, tamaño máximo (2 MB) y nombre aleatorio
- `uploads/.htaccess` que bloquea la ejecución de scripts
- Errores técnicos solo en el log del servidor; el usuario ve un mensaje genérico
- Credenciales fuera del repositorio (`config/credenciales.php` está en `.gitignore`)

## Estructura del proyecto

```
/
├── index.php
├── crud/
│   ├── crear.php
│   ├── guardar.php
│   ├── editar.php
│   ├── actualizar.php
│   ├── ver.php
│   └── eliminar.php
├── config/
│   ├── db.php
│   ├── credenciales.example.php
│   └── credenciales.php          (local, no versionado)
├── includes/
│   ├── funciones.php
│   ├── header.php
│   ├── menu.php
│   ├── footer.php
│   └── form_contacto.php
├── css/styles.css
├── uploads/
├── database/
│   ├── schema_pgsql.sql
│   └── schema_mysql.sql
├── .gitignore
└── README.md
```

## Instalación local

### Requisitos

- PHP 8.1 o superior con las extensiones `pdo_pgsql` o `pdo_mysql`, `fileinfo` y `mbstring` habilitadas en `php.ini`
- PostgreSQL o MySQL/MariaDB

### Pasos

1. Clona el repositorio:
   ```bash
   git clone https://github.com/CodexIncorp/php-crud.git
   cd php-crud
   ```
2. Crea la base de datos e importa el esquema que corresponda:
   - PostgreSQL: `database/schema_pgsql.sql`
   - MySQL/MariaDB: `database/schema_mysql.sql`
3. Copia la plantilla de credenciales y edítala:
   ```bash
   copy config\credenciales.example.php config\credenciales.php
   ```
4. Ejecuta el servidor integrado de PHP:
   ```bash
   php -S localhost:8000
   ```
5. Abre http://localhost:8000

## Despliegue en InfinityFree

1. Crea la cuenta y el hosting en [infinityfree.com](https://www.infinityfree.com).
2. Crea una base de datos MySQL desde el panel y anota host, usuario, contraseña y nombre.
3. Importa `database/schema_mysql.sql` desde phpMyAdmin.
4. Sube el contenido del proyecto a la carpeta `htdocs/` por FTP o con el administrador de archivos.
5. Crea `config/credenciales.php` directamente en el servidor con `'driver' => 'mysql'` y los datos del panel.
6. Verifica que `uploads/` tenga permisos de escritura.

## Autores

- **Rafael de Jesús Balderas Osorio**
- **Alexis Emiliano Morales Jiménez**
- **Adrik Said Soancatl Niño**

Programación Web / Instituto Tecnológico de Tuxtla Gutiérrez · 2026

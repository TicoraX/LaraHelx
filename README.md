# Discoteca

Un catálogo personal de música. Cada usuario registra sus artistas y los álbumes de cada uno, con foto y portada. Puede ver su colección en tablas, en un gráfico por género o en la ficha PDF de un artista. El catálogo de cada usuario es privado.

Es un proyecto de la universidad hecho en Laravel 12. Está publicado en https://laravel.helx.page.

## Qué hace

- Registro, login y recuperación de contraseña con vistas propias (Fortify). El enlace de recuperación vence a los 15 minutos.
- CRUD de artistas y álbumes con imagen, en tablas con búsqueda, orden y paginación (DataTables).
- Dashboard con los conteos del usuario, gráfico de álbumes por género (Chart.js) y ficha PDF por artista (dompdf).
- Perfil con foto, cambio de contraseña y tokens de API. Al cambiar la contraseña se cierran las otras sesiones y se revocan los tokens.
- API REST en `/api/v1` con tokens de Sanctum: `POST /tokens` y el CRUD de artistas.
- Consumo de [GoRest](https://gorest.co.in) con token, para crear, editar y borrar usuarios externos.

## Stack

PHP 8.4, Laravel 12 y PostgreSQL 17. El frontend se compila con Vite y Tailwind 4; DataTables 2.3 y Chart.js se instalan por npm. En producción la app corre en FrankenPHP dentro de Docker, detrás de Caddy.

## Instalación local en Windows

1. Descarga PHP 8.4 NTS x64 de [windows.php.net](https://windows.php.net/downloads/releases/), descomprímelo en `%USERPROFILE%\.config\php-8.4` y pon esa carpeta primera en el PATH de usuario.
2. Copia `php.ini-development` como `php.ini` y ajusta:
   - `extension_dir = "ext"`
   - extensiones: `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_pgsql`, `pgsql`, `sodium`, `zip`
   - `upload_max_filesize = 7M`, `post_max_size = 8M`, `memory_limit = 512M`
   - `curl.cainfo` y `openssl.cafile` con la ruta de un paquete de certificados, por ejemplo `"C:\Program Files\Git\mingw64\etc\ssl\certs\ca-bundle.crt"`. Si falta, las llamadas a GoRest fallan con `cURL error 60`.
3. Instala Composer 2 y Node 22 o superior.
4. Con Docker Desktop encendido, levanta PostgreSQL y crea la base de tests:
   ```
   docker run -d --name laravel-pg --restart unless-stopped -e POSTGRES_DB=laravel -e POSTGRES_USER=laravel -e POSTGRES_PASSWORD=laravel_dev_local -p 127.0.0.1:5432:5432 -v laravel-pgdata:/var/lib/postgresql/data postgres:17-alpine
   docker exec laravel-pg psql -U laravel -d postgres -c "CREATE DATABASE laravel_test"
   ```
5. Prepara el proyecto:
   ```
   composer install
   npm ci
   copy .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   composer run dev
   ```
   En `.env` van dos valores propios: `GOREST_TOKEN` (se crea en gorest.co.in; basta con permiso para la API v2) y `SEED_DEMO_PASSWORD`, la clave de los usuarios de demostración.
6. Corre los tests con `php artisan test`. Usan la base `laravel_test` de PostgreSQL.

## Equipo

| Integrante | Historias |
| :--- | :--- |
| A | Landing, registro, login, recuperación, rutas protegidas, dashboard y perfil |
| B | CRUD de artistas y álbumes con imágenes, DataTables y PDF |
| C | Gráfico, API con Sanctum, consumo de GoRest, despliegue y guion |

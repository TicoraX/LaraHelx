# Discoteca

Discoteca es un catálogo personal de música. Cada usuario guarda sus artistas y los álbumes de cada uno, con foto y portada, y puede ver la colección en tablas, en un gráfico por género o en la ficha PDF de un artista. Lo de cada usuario es privado: nadie ve el catálogo de otro.

La hicimos en Laravel 12 para la universidad y está publicada en https://laravel.helx.page.

## Qué tiene

- Registro, login y recuperación de contraseña con vistas propias. Fortify pone la lógica; el enlace de recuperación vence a los 15 minutos.
- CRUD de artistas y de álbumes, con imagen. Las dos listas usan DataTables para buscar, ordenar y paginar.
- Dashboard con los conteos del usuario, un gráfico de álbumes por género con Chart.js y una ficha PDF por artista con dompdf.
- Perfil con foto, cambio de contraseña y tokens de API. Cambiar la contraseña cierra las otras sesiones y revoca los tokens.
- Una API REST en `/api/v1` con tokens de Sanctum: `POST /tokens` para pedir un token y el CRUD de artistas.
- Consumo de [GoRest](https://gorest.co.in) con token Bearer, para crear, editar y borrar usuarios externos.

Para probar la API, importa `docs/LaraHelx.postman_collection.json` en Postman. Ya apunta a producción: la petición "Crear token" guarda el token en la variable `token` y las demás lo usan.

## Con qué está hecho

PHP 8.4, Laravel 12 y PostgreSQL 17. El frontend se compila con Vite y Tailwind 4, y DataTables y Chart.js entran por npm. En producción la app corre con FrankenPHP dentro de Docker, detrás de Caddy.

## Cómo correrlo en Windows

1. Descarga PHP 8.4 NTS x64 de [windows.php.net](https://windows.php.net/downloads/releases/), descomprímelo en una carpeta y ponla primera en el PATH de usuario.
2. Copia `php.ini-development` como `php.ini` y ajusta:
   - `extension_dir = "ext"`
   - extensiones: `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_pgsql`, `pgsql`, `sodium`, `zip`
   - `upload_max_filesize = 7M`, `post_max_size = 8M`, `memory_limit = 512M`
   - `curl.cainfo` y `openssl.cafile` con la ruta de un paquete de certificados, por ejemplo `"C:\Program Files\Git\mingw64\etc\ssl\certs\ca-bundle.crt"`. Sin eso, las llamadas a GoRest fallan con `cURL error 60`.
3. Instala Composer 2 y Node 22 o superior.
4. Con Docker Desktop encendido, levanta PostgreSQL y crea la base de los tests:
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
   En `.env` faltan dos valores tuyos: `GOREST_TOKEN`, que se saca en gorest.co.in, y `SEED_DEMO_PASSWORD`, la clave de los usuarios de demostración que crea el seeder.
6. Los tests se corren con `php artisan test` y usan la base `laravel_test` de PostgreSQL.

## Equipo

| Integrante | Parte |
| :--- | :--- |
| A | Landing, registro, login, recuperación de contraseña, rutas protegidas, dashboard y perfil |
| B | CRUD de artistas y álbumes con imágenes, DataTables y PDF |
| C | Gráfico, API con Sanctum, consumo de GoRest y despliegue |

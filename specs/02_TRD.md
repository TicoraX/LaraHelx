---
document: 02_TRD
stage: 2
status: APPROVED
version: 1.2.0
last_updated: 2026-10-24
approved_by: human
depends_on: [01_PRD]
---

# 02. Technical Requirements Document (TRD)

> v1.1.0 (2026-10-23): corregido según `REVIEW_00-04.md` (A1, A3, A4, A5, A6, M1-M5, L4, L6). Secciones nuevas: §6 (configuración obligatoria) y §7 (entorno local).
>
> v1.2.0 (2026-10-24): corregido según `REVIEW_05-06.md` (A1, A2, A3, A4, A6, A8, M2, M3, M4, M6, M7, M9, M11, B2, B4, B9). §6 enlaza cada línea con su compuerta; Resend ya tiene DNS en `helx.page`.

Versiones consultadas en Packagist y Docker Hub el 2026-10-23. Laravel 13 ya es la versión actual (v13.38); la rúbrica exige **12**, así que se fija la rama 12.x, que sigue recibiendo parches (framework v12.70.1 del 2026-10-21).

## 1. Stack y Justificación

| Capa | Elección | Por qué | Descartado |
| :--- | :--- | :--- | :--- |
| Lenguaje | PHP 8.4 | Soportado por Laravel 12 (`^8.2`), mismo minor en local (8.4.27) y en el contenedor. | PHP 8.5: menos probado con paquetes de terceros a un mes de la entrega. |
| Framework | Laravel 12 (`composer create-project laravel/laravel:^12.0`) | Lo pide la rúbrica. `laravel new` instalaría la 13. | Laravel 13. |
| Autenticación web | Laravel Fortify 1.41 | Lógica de registro, login, logout, reset, perfil y contraseña sin vistas: las vistas son Blade propias. Trae rate limit de login. | Starter kits (React, Vue, Livewire): imponen UI y una SPA. Breeze: retirado en favor de los starter kits. |
| API y tokens | Laravel Sanctum 4.3 (`php artisan install:api`) | Tokens personales con hash en BD, middleware `auth:sanctum`, revocables. | Passport (OAuth2 completo): sobra para tokens personales. JWT a mano: inventar la rueda. |
| Base de datos | PostgreSQL 17 | Elegida por el usuario. Mismo motor y versión en local (contenedor `laravel-pg`) y en producción. | MySQL, SQL Server. |
| Vistas | Blade con dos layouts (`layouts.guest`, `layouts.app`) | La rúbrica pide herencia de maestras. | Inertia o Livewire. |
| CSS y JS | Vite + Tailwind CSS 4 (vienen en el esqueleto de Laravel 12) | Un solo pipeline de assets; la dirección visual la define la Etapa 4. | Bootstrap por CDN. |
| Tablas | DataTables 2 instalado por npm (`datatables.net-dt@^2.3`; el `latest` de npm ya es la 3.x) y empaquetado con Vite | Sale del mismo origen: funciona con `script-src 'self'` y no depende de un CDN el día de la sustentación. Corrección a la Etapa 0: DataTables 2 sigue trayendo jQuery como dependencia, aunque su API se usa sin jQuery (`new DataTable(...)`). | CDN (punto de falla externo y CSP abierta). `yajra/laravel-datatables`: purgatorio. |
| Gráficos | Chart.js 4 por npm | Igual que DataTables. Los datos llegan en un `<script type="application/json">` generado con `@json`, que no ejecuta código y no rompe la CSP. | ApexCharts (más pesado). |
| PDF | `barryvdh/laravel-dompdf` 3.1 | Renderiza una vista Blade. Las imágenes se pasan como ruta de disco, con `isRemoteEnabled=false`. | Browsershot (necesita Chrome en el contenedor). |
| Cliente REST externo | Cliente `Http` de Laravel contra GoRest | `withToken()`, `acceptJson()` y `timeout()` cubren tokens, cabeceras y autorización. | Guzzle directo: es lo mismo con más código. |
| Tests | PHPUnit 11 (default del esqueleto) con tests de feature y `RefreshDatabase` sobre una base `laravel_test` en el mismo PostgreSQL | Probar contra el motor real evita diferencias de SQLite (tipos, `ILIKE`, restricciones). | SQLite en memoria (rápido, pero otro motor). Pest: otra sintaxis que explicar en la sustentación. |
| Servidor en contenedor | FrankenPHP 1 sobre PHP 8.4 (`dunglas/frankenphp:1-php8.4`), modo clásico con `SERVER_NAME=:8000` (sin esa variable, su Caddyfile interno sirve HTTPS en :443) | Un solo proceso sirve HTTP en `:8000`; imagen multi-arquitectura (amd64 y arm64); `install-php-extensions` ya viene. | nginx + php-fpm: dos procesos y un supervisor. `php artisan serve`: es de desarrollo y atiende de a una petición. |

## 2. Arquitectura

```mermaid
flowchart LR
    B["Navegador"] -->|HTTPS| C["Caddy (host VPS)"]
    X["App externa / Postman"] -->|HTTPS + Bearer| C
    C -->|127.0.0.1:8000| F["Contenedor app: FrankenPHP + Laravel 12"]
    F --> W["Rutas web: Fortify, controladores resource, Policies"]
    F --> A["Rutas /api/v1: Sanctum"]
    W --> M["Modelos Eloquent: User, Artist, Album"]
    A --> M
    M --> P[("Contenedor db: PostgreSQL 17")]
    W --> S[("Volumen: storage/app/public (imágenes)")]
    W --> G["GoRestClient"] -->|HTTPS + Bearer| GR["gorest.co.in"]
    W --> SMTP["SMTP (correo de reset)"]
```

- **Patrón**: monolito MVC de Laravel, sin capas extra. Controladores resource delgados, validación en `FormRequest`, autorización en Policies y un único servicio (`GoRestClient`) para la frontera externa.
- **Propiedad de los datos**: `artists.user_id`; el dueño de un álbum es el de su artista (`User::albums()` es `hasManyThrough`, ver 05 §1). La barrera principal es el `Route::bind` de `{artist}` y `{album}`, que resuelve el parámetro desde `$request->user()->artists()` o `->albums()` (05 §2, I2): lo ajeno lanza el mismo 404 que lo inexistente, antes de la validación y de la Policy, en web y en API. Las Policies con `Response::denyAsNotFound()` quedan como segunda línea en `show`, `update` y `destroy`.
- **Concurrencia**: una instancia y tráfico de demostración. Sin colas: el correo de reset se envía en la misma petición (`QUEUE_CONNECTION=sync`).

## 3. Requisitos No Funcionales

- **Rendimiento**: páginas del dashboard con menos de 300 ms de servidor (p95) y PDF en menos de 3 s, con los datos del seeder. Listados con carga anticipada (`with('artist')`) para evitar N+1.
- **Peso de JS**: DataTables y Chart.js son entradas de Vite separadas y se cargan solo en las vistas que las usan. Landing y formularios sin JS de librerías.
- **Seguridad**:
  - CSRF en todos los formularios (default de Laravel); cookies `HttpOnly`, `Secure` (`SESSION_SECURE_COOKIE=true`) y `SameSite=Lax`.
  - Rate limits: login de Fortify en 5 por minuto por correo e IP; `POST /api/v1/tokens` en 5 por minuto; resto de la API en 60 por minuto por usuario autenticado; `POST /forgot-password` en 6 por minuto por IP. Fortify no le pone límite, y `fortify.middleware` lo aplicaría a todas sus rutas (un `GET /login` repetido daría 429 en un salón con una sola IP de salida), así que se agrega solo a la ruta `password.email` en `AppServiceProvider::boot`: `$this->app->booted(fn () => Route::getRoutes()->getByName('password.email')?->middleware('throttle:6,1'));`. Se confirma con `php artisan route:list --name=password.email -v` y lo prueba F18.
  - `trustProxies(at: '*')` es seguro solo porque el puerto de la app se publica en `127.0.0.1` y únicamente Caddy le habla. Si el puerto se abre a internet, deja de serlo.
  - Cabeceras desde un middleware propio, solo en producción: `Content-Security-Policy` (`default-src 'self'`, `script-src 'self'`, `img-src 'self' data:`, `frame-ancestors 'none'`), `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin` y `Strict-Transport-Security: max-age=31536000` (las mismas de 05 §5). Caddy no agrega CSP (regla de la VPS).
  - "Olvidé mi contraseña" no revela correos: Fortify por defecto responde "We can't find a user with that email address". Se registra un `FailedPasswordResetLinkRequestResponse` propio que, para `INVALID_USER` y `RESET_THROTTLED`, responde lo mismo que el éxito: "Si el correo existe, te enviamos un enlace".
  - Subidas: `mimes:jpg,jpeg,png,webp`, `max:2048` y hasta 2000 x 2000 px (05 §3), nombre aleatorio que genera Laravel, guardadas en el disco `public`. Nunca se usa el nombre original como ruta.
  - Secretos (`APP_KEY`, clave de BD, token de GoRest, SMTP) solo en `.env` (600 en la VPS); el repo lleva `.env.example` sin valores.
- **Tolerancia a fallos (fail-fast)**:
  - Crear o editar con imagen, en este orden:
    1. Validación.
    2. Se guarda el archivo nuevo **antes** de abrir la transacción. El disco `public` lleva `'throw' => true` en `config/filesystems.php` (el default de Laravel 12 es `false`: `store()` devolvería `false` en silencio y la fila quedaría con una imagen rota). Si falla, la excepción corta la petición y no se escribe nada.
    3. Escritura de la fila dentro de `DB::transaction`. Si la transacción falla, se borra el archivo nuevo y se relanza la excepción.
    4. La imagen vieja se borra con `DB::afterCommit`, para que un rollback no deje la fila apuntando a un archivo borrado.
  - Borrar un artista: sus álbumes caen por `ON DELETE CASCADE`, y los archivos se borran después del commit.
  - GoRest: `connectTimeout(3)->timeout(6)`. Los `GET` reintentan 1 vez a los 200 ms, solo ante `ConnectionException`; `POST`, `PUT` y `DELETE` no se reintentan. El peor caso queda por debajo de 15 s, lejos del `max_execution_time` de 30 s. Los errores se clasifican por código de estado, nunca por el texto del cuerpo (GoRest devuelve textos distintos para el mismo 401). Un error se muestra tal cual con botón "Reintentar"; nunca hay datos de relleno.
  - Correo de reset: si el SMTP falla, la petición devuelve error 500 y queda en el log del contenedor (`LOG_CHANNEL=stderr`, se lee con `docker compose logs app`); no se finge el envío.
- **Plataformas**: desarrollo en Windows 11 (PHP 8.4.27 oficial, PostgreSQL en Docker); producción en Linux, primero x86_64 y después ARM64.

## 4. Despliegue (VPS Oracle, según "Infraestructura" de `~/.claude/CLAUDE.md`)

- **URL**: `https://laravel.helx.page`. El DNS comodín ya apunta a la VPS (verificado: resuelve a 157.137.195.173).
- **Carpetas**: clon en `~/apps/laravel`; `compose.yml` y `.env` (600) en `~/apps/laravel-deploy/`, fuera del repo. El `Dockerfile` va **en el repo** (regla actual de la VPS).
- **Dockerfile** (multi-etapa):
  - Etapa `assets`: `node:22-alpine` corre `npm ci && npm run build`.
  - Etapa final: `dunglas/frankenphp:1-php8.4` con `install-php-extensions pdo_pgsql gd intl zip opcache` y `composer install --no-dev --optimize-autoloader`.
  - `conf.d/uploads.ini` propio: `upload_max_filesize=7M`, `post_max_size=8M`. Sin él, PHP usa 2M y un archivo de más de 2 MB falla con un error genérico en vez del mensaje de 2 MB. Con 7M, todo archivo de hasta 7 MB llega a la validación y recibe el mensaje propio; por encima de 8 MB responde la vista 413.
  - `php artisan storage:link` en el build (sin el symlink `public/storage`, todas las imágenes dan 404).
  - `chown -R 1001:1001 storage bootstrap/cache` antes de declarar los volúmenes, para que el usuario 1001 pueda escribir.
  - `ENV SERVER_NAME=:8000`.
  - Entrypoint: `mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions` y después `php artisan optimize`. El `tmpfs` se monta vacío encima de `storage/framework`: sin esas carpetas, `realpath` de `framework/views` da `false`, el compilador de Blade lanza "Please provide a valid cache path" y ninguna vista renderiza.
  - `route:cache` (dentro de `optimize`) falla si alguna ruta es un closure: la landing usa `Route::view('/', 'landing')`.
- **compose.yml**: dos servicios, `app` (`container_name: laravel-app`) y `db` (`container_name: laravel-db`; P9 y el respaldo usan ese nombre), con la plantilla de endurecimiento de la VPS adaptada a lo que Laravel necesita escribir:
  - `app`: `user: "1001:1001"`, `read_only: true`, `cap_drop: [ALL]`, `security_opt: [no-new-privileges:true]`, `mem_limit: 512m`. Publica `127.0.0.1:8000:8000`.
    - Volumen `laravel-storage` en `/app/storage/app/public` (imágenes).
    - `tmpfs` para `/tmp`, `/app/storage/framework` (vistas compiladas y caché; las sesiones van en la BD), `/app/storage/logs`, `/app/bootstrap/cache`, `/app/storage/fonts` (caché de fuentes de dompdf), y `/data` y `/config` (el Caddy interno de FrankenPHP). Lo que vive en `tmpfs` se regenera al arrancar.
    - Healthcheck de Docker sobre `/up`.
    - Variables de producción en el `.env` del compose: `LOG_CHANNEL=stderr` (con `storage/logs` en `tmpfs` el archivo de log desaparece al reiniciar), `DB_HOST=db`, `DB_USERNAME=app`, `DB_PASSWORD` igual a `APP_DB_PASSWORD`, `MAIL_FROM_ADDRESS="no-reply@helx.page"`, `MAIL_FROM_NAME="Discoteca"`.
  - `db`: `postgres:17-alpine` sin puerto publicado, volumen `laravel-pgdata`, `mem_limit: 256m`. Recibe `APP_DB_PASSWORD` y monta `./docker/postgres/10-app.sh` (del repo) de solo lectura en `/docker-entrypoint-initdb.d/`, que crea el rol `app` sin superusuario y dueño de la base (05 §1).
- **Prueba local del compose**: antes del 2026-10-30, un `docker compose up` local con el mismo `read_only` y los mismos `tmpfs` muestra los fallos de escritura antes que producción.
- **Caddy**: se agrega el bloque `laravel.helx.page` con `import comun laravel` y `reverse_proxy localhost:8000`, sin tocar los demás bloques; después `sudo -u caddy caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile && sudo systemctl reload caddy`. Nunca `sudo caddy validate`: deja los logs a nombre de root y Caddy no arranca en el siguiente reinicio.
- **Laravel detrás del proxy**: `$middleware->trustProxies(at: '*')` en `bootstrap/app.php`, `APP_URL=https://laravel.helx.page`, `SESSION_SECURE_COOKIE=true`, `APP_ENV=production`, `APP_DEBUG=false`.
- **Redesplegar**: `git pull` → `docker compose build` → `docker compose run --rm app php artisan migrate --force` → `docker compose up -d`. El contenedor corre `php artisan optimize` al arrancar (cachea config, rutas y vistas en los `tmpfs`).
- **Prueba de humo después de cada deploy**: `curl -fsS https://laravel.helx.page/up`, subir una imagen, verla en la lista y descargar un PDF. Es una compuerta del GATES.
- **Migración de host (A1, Pay As You Go o plan B)**: a más tardar el 2026-11-12. La imagen se construye en el propio host y todas las bases (`node`, `frankenphp`, `postgres`) publican arm64, así que el código no cambia. Los datos se mueven con el último respaldo: crear el rol `app` (05 §1), `pg_restore --no-owner --role=app -U <superusuario> -d laravel laravel-YYYYmmdd.dump` y extraer `laravel-uploads-YYYYmmdd.tar.gz` en el volumen `laravel-storage`. Cambia la IP: se actualizan los registros A del DNS y el `HostName` del alias SSH. Si el host cambia, P1 a P7 y el ensayo E2 se repiten sobre el host nuevo. P10 ensaya la restauración antes de necesitarla.
- **Respaldo**: cron diario en el host que escribe en `~/backups/laravel/` dos archivos: `laravel-YYYYmmdd.dump` (`docker exec laravel-db pg_dump -Fc -U <superusuario> laravel`) y `laravel-uploads-YYYYmmdd.tar.gz` (`tar` del volumen `laravel-storage`). Guarda los últimos 7 días. RPO de 24 h. Se mantiene porque protege los datos de demostración durante la migración de VM. La copia fuera del servidor queda en el purgatorio. P7 verifica que el último par sea de las últimas 26 horas.
- **Registro** (pendiente del dueño, fuera del ledger): al publicar, se agrega la app a "Apps desplegadas" y el puerto 8000 a "Puertos tomados" en `~/.claude/CLAUDE.md`.

## 5. Calendario y Riesgos

El riesgo mayor (despliegue más correo) va primero, no al final.

| Fecha | Hito |
| :--- | :--- |
| 2026-10-24 | Etapa 4 aprobada; Etapa 5 corta (esquema de 3 tablas) y GATES. Repo creado. |
| **2026-10-30** | **Esqueleto publicado**: Laravel 12 + Fortify con vistas mínimas en `https://laravel.helx.page`, en el host que va a existir el 2026-11-21, con un correo de reset real llegando por Resend. Si a esa fecha A1 no tiene capacidad o el upgrade a Pay As You Go no está resuelto, se activa el plan B. |
| 2026-11-08 | CRUD x2 con imágenes, DataTables, PDF, gráfico, API y GoRest funcionando en producción. |
| 2026-11-12 | Fecha límite para migrar de host si la VM Intel no sigue. Si cambia, se repiten P1 a P7 y E2 sobre el host nuevo. |
| 2026-11-14 | Funcionalidad congelada. Solo arreglos. |
| 2026-11-18 | Ensayo completo de la sustentación sobre producción, cada integrante con sus historias. |

| Riesgo | Mitigación |
| :--- | :--- |
| La VM Intel del trial vence hacia el 2026-11-20, un día antes de la sustentación, y la capacidad de A1 no está garantizada. | Resolver el host antes del 2026-10-30. Plan B: VPS genérico de unos 5 USD al mes con Docker; el despliegue es el mismo `git clone` + `.env` + `docker compose up` + restaurar el respaldo. |
| GoRest caído o lento el día de la sustentación. | Timeouts cortos, error honesto y video grabado del flujo como respaldo. Los tests usan `Http::fake()`, así que no dependen de GoRest. GoRest resiembra sus datos a diario: el guion crea, edita y borra en el momento, sin depender de filas de días anteriores. |
| Resend rechaza un remitente fuera del dominio verificado y el reset da 500. | Dominio `helx.page` verificado en Resend: DKIM, SPF y DMARC publicados el 2026-10-24 (se hicieron para el Portfolio). Remitente `MAIL_FROM_ADDRESS="no-reply@helx.page"`, `MAIL_FROM_NAME="Discoteca"`; S5 prueba el envío real. SMTP: `smtp.resend.com`, puerto 587 (STARTTLS) o 465, usuario `resend`, clave = API key. No usar el 25 (Oracle suele bloquear la salida por ese puerto; no verificado). En local, `MAIL_MAILER=log`. |
| Un integrante no logra levantar el proyecto. | Camino único de instalación en el README (§7) y prueba de instalación en la máquina de cada integrante hasta el 2026-10-28 (compuerta S8). |
| Composer resuelve Laravel 13 por error. | `composer.json` fija `laravel/framework: ^12.0`; una compuerta del GATES verifica `php artisan --version` = 12.x. |

## 6. Configuración Obligatoria (sobre el esqueleto de Laravel 12)

Lo que el esqueleto trae por defecto y hay que cambiar. Cada línea termina con la compuerta de `06_GATES.md` que la observa.

- `config/fortify.php`: `'home' => '/dashboard'` (el default `/home` da 404 después del login). **Gate S3**.
- `config/fortify.php`: `features` solo con `registration()`, `resetPasswords()`, `updateProfileInformation()` y `updatePasswords()`: fuera `twoFactorAuthentication` y `passkeys`, que vienen activas y piden `laravel/passkeys`. No se corre la migración de columnas de 2FA. **Gate S10** (no existen rutas de 2FA ni de passkeys).
- `config/fortify.php`: `'lowercase_usernames' => true` (el default es `false`; sin él, un correo registrado con mayúscula no entra con minúscula en PostgreSQL). **Gates S3 y F6**.
- `config/auth.php`: `passwords.users.expire = 15` (I9). **Gate S3** (enlace vencido y de un solo uso).
- `config/sanctum.php`: `expiration => 43200`. **Gate F9** (token de más de 30 días da 401).
- `config/filesystems.php`: `'throw' => true` en el disco `public`. **Gate S10**.
- `AppServiceProvider::boot`: `Route::bind` de `artist` y `album` con alcance de usuario (05 §2) y el `throttle:6,1` de la ruta `password.email` (§3). **Gates F1, F9 y F18**.
- `bootstrap/app.php`: la API responde JSON aunque falte `Accept` (sin esto, un 401 sin token desde Postman es un 302 a la página de login) y su 404 no filtra el nombre de la clase:
  ```php
  ->withExceptions(function (Exceptions $exceptions): void {
      $exceptions->shouldRenderJsonWhen(fn (Request $r, Throwable $e) => $r->is('api/*') || $r->expectsJson());
      $exceptions->render(function (NotFoundHttpException $e, Request $r) {
          if ($r->is('api/*')) {
              return response()->json(['message' => 'No encontrado'], 404);
          }
      });
  })
  ```
  `shouldRenderJsonWhen` gobierna también el 401 y el 422. **Gate F9** (peticiones sin `Accept`).
- Grupo `auth` de rutas web con el middleware `cache.headers:no_store;private`, para que Atrás después del logout no muestre el dashboard. **Gate F18**.
- Ninguna ruta es un closure (`route:cache` falla con closures): la landing es `Route::view`. **Gates S10 y S4**.
- Idioma: `APP_LOCALE=es`, `APP_FALLBACK_LOCALE=es`, `php artisan lang:publish`, los archivos `lang/es/{validation,auth,passwords,pagination}.php` y `lang/es.json` con las cadenas JSON que usan el correo de reset (`Reset Password Notification`, `You are receiving this email because...` y las demás de `ResetPassword`), el stub `UpdateUserPassword` (`The provided password does not match your current password.`) y `Unauthenticated.`; con `laravel-lang/common` como dependencia de desarrollo o a mano. **Gate F11**.
- `phpunit.xml`: `DB_CONNECTION=pgsql`, `DB_DATABASE=laravel_test` (el esqueleto trae SQLite en memoria, y el PHP de Windows no tiene `pdo_sqlite`). `SESSION_DRIVER=array` queda como viene: F20 prueba la acción que borra sesiones y P6 lo comprueba con dos navegadores. **Gate S2**.
- `.env.example`: `DB_CONNECTION=pgsql` con los valores del contenedor local, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=database`, `GOREST_TOKEN=` vacío, `SEED_DEMO_PASSWORD=` con un valor de desarrollo, `MAIL_MAILER=log`, `MAIL_FROM_ADDRESS="no-reply@helx.page"` y `MAIL_FROM_NAME="Discoteca"`. **Gate S10**.
- Producción: `SESSION_SECURE_COOKIE=true`; con los defaults de Laravel las cookies salen `HttpOnly` y `SameSite=Lax`. **Gate S4** (lee `Set-Cookie`).
- Vistas de error propias: 403, 404, 413 (archivo mayor al `post_max_size`), 419 y 500, que heredan la maestra. 429 y 503 quedan con las de Laravel. **Gate F12**.
- Contenedor (§4): `uploads.ini`, `storage:link`, `chown`, `tmpfs` con el `mkdir` del entrypoint y healthcheck. **Gates S4 y P6**.

## 7. Entorno Local (camino único para los tres integrantes; va al README)

1. PHP 8.4 NTS x64 oficial: el zip de `windows.php.net/downloads/releases/`, descomprimido en `%USERPROFILE%\.config\php-8.4`, con esa carpeta primero en el PATH de usuario.
2. `php.ini` copiado de `php.ini-development`, con `extension_dir = "ext"` y estas extensiones activas: `curl`, `fileinfo` (obligatoria para la regla `mimes:`), `gd`, `intl`, `mbstring`, `openssl`, `pdo_pgsql`, `pgsql`, `sodium`, `zip`. Además `upload_max_filesize = 7M`, `post_max_size = 8M` y `memory_limit = 512M`, iguales a producción.
3. Composer 2.
4. Docker Desktop y el PostgreSQL: `docker run -d --name laravel-pg --restart unless-stopped -e POSTGRES_DB=laravel -e POSTGRES_USER=laravel -e POSTGRES_PASSWORD=laravel_dev_local -p 127.0.0.1:5432:5432 -v laravel-pgdata:/var/lib/postgresql/data postgres:17-alpine`, y después `docker exec laravel-pg psql -U laravel -d postgres -c "CREATE DATABASE laravel_test"`.
5. Node 22 o superior, para Vite.
6. Git y `gitleaks` 8.19 o superior (`winget install Gitleaks.Gitleaks`; el subcomando `gitleaks git` de S6 no existe en versiones anteriores).
7. `composer install`, `npm ci`, copiar `.env.example` a `.env`, `php artisan key:generate`, `php artisan migrate --seed`, `php artisan storage:link`, `composer run dev`.
8. `php artisan test` en verde contra el PostgreSQL local: es la evidencia de S8, hasta el 2026-10-28.

En la máquina del dueño ya está todo hecho, incluida la base `laravel_test` (creada el 2026-10-23); falta subir `upload_max_filesize` a 7M en su `php.ini` y instalar `gitleaks`.

## 8. Supuestos a Validar

1. **Proveedor de correo** (DECIDIDO 2026-10-23: Resend con `helx.page`). RESUELTO el 2026-10-24: DKIM, SPF y DMARC publicados en `helx.page` (se hicieron para el Portfolio). Remitente `no-reply@helx.page`, nombre "Discoteca". Queda probar el envío real desde producción (S5).
2. **Token de GoRest**: alguien del equipo tiene que crear una cuenta en gorest.co.in (entra con GitHub o Google) y copiar su token al `.env`. No se puede automatizar.
3. **Repositorio** (RESUELTO 2026-10-24): `https://github.com/TicoraX/LaraHelx`, privado. Antes de la entrega se hace público o se agrega al docente como colaborador; los otros dos integrantes entran como colaboradores.
4. **Etapa 5 corta**: el esquema son 3 tablas propias (`users`, `artists`, `albums`) más `personal_access_tokens` de Sanctum. El documento de la Etapa 5 se reduce a columnas, índices, llaves foráneas y reglas de validación; las migraciones son la fuente de verdad.

---
document: REVIEW_00-04
reviewed: [00_REALITY_CHECK, 01_PRD, 02_TRD, 03_APP_FLOW, 04_DESIGN_BRIEF, design/prototype.html, design/contrast.mjs]
date: 2026-10-23
reviewer: subagente revisor (solo lectura de specs; ningún spec editado)
---

# Revisión técnica de las Etapas 0 a 4

**Veredicto**: la rúbrica está cubierta completa en papel, pero el flujo PUT/DELETE de GoRest, tal como está diseñado, va a fallar con 403 en la demo, y hay seis supuestos técnicos falsos o incompletos (Fortify, Tailwind 4, contenedor, discos) que rompen historias aprobadas. Hay que corregirlo antes de escribir código y recortar alrededor de un 30 % del alcance que la rúbrica no califica.

Conteo: 1 BLOQUEANTE, 6 ALTO, 7 MEDIO, 7 BAJO.

---


> **Estado (2026-10-23)**: el usuario aprobó aplicar todo. Aplicado en 00-04 v1.1.0. Excepciones decididas por el orquestador: se mantienen el plan de migración a ARM, el cron de respaldo y la CSP en modo estricto (no report-only), y la revocación de tokens desde la UI.

## 1. Hallazgos

### BLOQUEANTE

#### B1. GoRest: Editar y Eliminar sobre filas ajenas devuelve 403, y el App Flow no lo contempla
- **Dónde**: 03_APP_FLOW §2 "API externa" (Idle y Error); 01_PRD H14; 04_DESIGN_BRIEF §7 "API externa".
- **Qué está mal**: la pantalla lista "la primera página de usuarios de GoRest" con botones Editar y Eliminar en cada fila. Esa primera página la llenan otras personas con sus propios tokens (hoy: 2.996 usuarios; los primeros ids, 8683835 y siguientes, son de terceros). Según la documentación oficial de GoRest, modificar una fila creada por otro token da **403**. La lista de errores del App Flow cubre 401, 404, 422 y 429, pero no 403. En la sustentación, cualquier clic en Editar o Eliminar sobre una fila que no creó el equipo falla, y son justo los verbos PUT y DELETE de la rúbrica.
- **Evidencia**:
  - `https://gorest.co.in/docs/http-status-codes`: "403 Forbidden ... On Go REST that almost always means: someone else's token created the row, and you cannot modify it."
  - `curl https://gorest.co.in/public/v2/users` → 200, cabeceras `x-pagination-total: 2996`, `x-pagination-limit: 10`; los primeros registros son de terceros.
  - Portada de GoRest: "Names, emails, posts, and comments seeded daily", así que los datos pueden reiniciarse a diario (NO VERIFICADO si borra los creados por un token).
  - Límite: no probé el 403 en vivo porque exige un token, y la consigna prohíbe crear datos. Lo respalda la documentación oficial, no una prueba.
- **Corrección**:
  1. Crear siempre con un correo marcado (`<algo>@discoteca.test`) y listar con el filtro documentado `GET /public/v2/users?email=discoteca.test` ("Use any field as a search filter"). Así la tabla muestra solo filas del equipo y Editar y Eliminar funcionan. Si el filtro no aísla bien, guardar en la BD local los ids que devuelve el POST (tabla `external_users`: `user_id`, `gorest_id`) y mostrar acciones solo para esos.
  2. Agregar 403 a la lista de errores: "Ese usuario lo creó otra cuenta de GoRest; solo puedes editar los que creaste aquí".
  3. Guion de demo: POST → aparece en la lista → PUT sobre ese mismo → DELETE sobre ese mismo. No depender de filas creadas días antes.
  4. Test con `Http::fake()` que cubra el 403.

### ALTO

#### A1. "Olvidé mi contraseña" con Fortify revela qué correos existen, sale en inglés y no tiene el throttle que dice el App Flow
- **Dónde**: 01_PRD H4 ("la pantalla responde igual"); 03_APP_FLOW §1 (`/forgot-password` "throttle 6/min") y §2 ("Si el correo existe, te enviamos un enlace").
- **Qué está mal**: con un correo no registrado, `PasswordResetLinkController::store` devuelve `FailedPasswordResetLinkRequestResponse`, que vuelve atrás con el error `trans('passwords.user')`: "We can't find a user with that email address." Además, la ruta POST `/forgot-password` de Fortify **no tiene throttle**. Lo único parecido es el `throttle => 60` del broker en `config/auth.php`, que solo se dispara con correos que existen ("Please wait before retrying.") y por eso también revela cuáles existen. El test de H4 va a fallar contra el comportamiento por defecto.
- **Evidencia**: `laravel/fortify` 1.x, `routes/routes.php` líneas 64-66 (POST `password.email` solo con `guest`); `src/Http/Responses/FailedPasswordResetLinkRequestResponse.php` (`withErrors(['email' => trans($this->status)])`); `laravel/laravel` 12.x, `config/auth.php` (`'expire' => 60, 'throttle' => 60`).
- **Corrección**: registrar en el contenedor un `FailedPasswordResetLinkRequestResponse` propio (unas 10 líneas en `FortifyServiceProvider`) que, para `Password::INVALID_USER` y `Password::RESET_THROTTLED`, devuelva el mismo `back()->with('status', 'Si el correo existe, te enviamos un enlace')`. Agregar `throttle:6,1` en `fortify.middleware`, o con un `Route::middleware` sobre `password.email`. Si no se hace, borrar la línea "throttle 6/min" del App Flow: hoy es falsa.

#### A2. Tailwind 4 no emite las variables de `@theme` que no se usan en CSS: Chart.js lee cadenas vacías
- **Dónde**: 04_DESIGN_BRIEF §9 (`t('--color-chart-bar')`, etc.) y §11 (`--color-chart-*` dentro de `@theme`).
- **Qué está mal**: los cinco `--color-chart-*` solo se leen desde JS con `getComputedStyle`. Ninguna clase de utilidad los usa, así que Tailwind 4 no los emite. `getPropertyValue` devuelve `''` y las barras, la grilla y el tooltip salen con los colores por defecto de Chart.js, o sin color. Justo lo que el brief dice que "no aparece nunca".
- **Evidencia**: documentación de Tailwind (`tailwindcss.com/docs/theme`, sección "Generating all CSS variables"): "By default only used CSS variables will be generated in the final CSS output. If you want to always generate all CSS variables, you can use the static theme option". La misma página recomienda `:root` para variables "that shouldn't have corresponding utility classes".
- **Corrección**: sacar los espejos hex de `@theme` y ponerlos en un bloque `:root { --chart-bar: #4f565f; ... }`, o usar `@theme static`. Si `--font-body` no lo usa ninguna clase (`font-body`), le pasa lo mismo.

#### A3. El contenedor de producción, tal como está escrito, no escucha en :8000 y choca con la plantilla de endurecimiento de la VPS
- **Dónde**: 02_TRD §1 (fila "Servidor en contenedor") y §4 (compose, volumen, "cachea al arrancar").
- **Qué está mal**:
  1. El Caddyfile que trae la imagen de FrankenPHP sirve `{$SERVER_NAME:localhost}`, que por defecto es HTTPS en :443 con certificado interno. El TRD da por hecho "HTTP en :8000" sin fijar `SERVER_NAME=:8000`. Sin eso, `reverse_proxy localhost:8000` no encuentra nada.
  2. La sección Infraestructura de `~/.claude/CLAUDE.md` exige en cada compose `user: "1001:1001"`, `read_only: true` y `cap_drop: [ALL]`. Laravel necesita escribir en `storage/framework/{views,cache}`, `storage/logs`, `bootstrap/cache` (el "cachea config, rutas y vistas al arrancar"), `storage/fonts` (caché de fuentes de dompdf) y en el volumen de imágenes. Caddy, dentro de FrankenPHP, escribe en `/data` y `/config`. El TRD no menciona nada de esto. Con la plantilla aplicada, fallan el arranque, las vistas, los PDF y las subidas. Sin la plantilla, se incumple la regla de la VPS.
  3. Un volumen nombrado nuevo hereda dueño y permisos del directorio de la imagen. Si el Dockerfile no hace `chown 1001` de `storage/app/public`, el usuario 1001 no puede escribir: la primera subida en producción falla. Ver A6: con el disco por defecto, falla **en silencio**.
  4. Falta `php artisan storage:link`, en la imagen o en el entrypoint. Sin el symlink `public/storage`, todas las imágenes dan 404.
- **Evidencia**: `php/frankenphp` main, `caddy/frankenphp/Caddyfile` (`{$SERVER_NAME:localhost} {`, `root {$SERVER_ROOT:public/}`); su `Dockerfile` (`EXPOSE 80`, `EXPOSE 443`, `WORKDIR /app`); `~/.claude/CLAUDE.md` líneas 75-77 (plantilla del compose). Docker Hub: `dunglas/frankenphp:1-php8.4` existe para amd64 y arm64 (actualizada el 2026-10-23).
- **Corrección**: agregar al TRD §4 un bloque explícito con: `SERVER_NAME=:8000`; `tmpfs` o volúmenes para `/data`, `/config`, `storage/framework`, `storage/logs`, `bootstrap/cache` y `storage/fonts`; `chown -R 1001:1001 storage bootstrap/cache` en el Dockerfile antes de declarar el volumen; `storage:link` en el build; y una prueba de humo después del primer deploy (subir una imagen, bajar un PDF, `curl -f https://laravel.helx.page/up`). Hacerlo con un "hola mundo" la primera semana, no el 2026-11-15.

#### A4. Los compañeros no tienen un camino de instalación que funcione, y nadie repartió el trabajo
- **Dónde**: 00_REALITY_CHECK §2 (fila "Entorno local sin PHP": "ADOPTAR php.new") y §6.4; 02_TRD §5 (fila "Un integrante sin PHP ni PostgreSQL").
- **Qué está mal**:
  1. La Etapa 0 recomienda php.new (Herd Lite) para los otros dos integrantes. La nota de entorno de `~/.claude/CLAUDE.md` dice que Herd Lite "no trae `pdo_pgsql`" y que por eso en esta máquina se instaló PHP oficial. Con PostgreSQL, el camino de la Etapa 0 no funciona, y el TRD lo contradice sin corregir la Etapa 0.
  2. El PHP oficial de Windows viene sin extensiones activas. Hay que editar `php.ini` (aquí: `curl fileinfo gd intl mbstring openssl pdo_pgsql pgsql sodium zip`) y subir `upload_max_filesize`. Ningún documento lo lista. `fileinfo` es obligatoria para la regla `mimes:` de la validación.
  3. El PHP local no tiene `pdo_sqlite` (salida de `php -m`). El esqueleto de Laravel 12 trae `DB_CONNECTION=sqlite` en `.env.example` y `phpunit.xml` con `sqlite` en `:memory:`. El primer `php artisan test` sin tocar nada falla con "could not find driver". La base `laravel_test` que pide el TRD §1 no existe en el contenedor (`\l` muestra solo `laravel` y `postgres`).
  4. En ningún documento hay reparto de historias entre las 3 personas. La sustentación es del equipo, y un stack elegido por una persona con su agente (FrankenPHP, CSP, OKLCH, Fontsource) que los otros dos no han corrido es un riesgo directo para la defensa oral.
- **Evidencia**: `C:\Users\santi\.config\php-8.4\php.exe -m` (sin `pdo_sqlite`); `php.ini` con 10 `extension=` activadas a mano; `docker exec laravel-pg psql -U laravel -c '\l'`; `laravel/laravel` 12.x, `phpunit.xml` líneas 26-27; `~/.claude/CLAUDE.md` línea 228.
- **Corrección**: tachar php.new en la Etapa 0. El README debe traer un único camino: el zip de PHP 8.4 NTS x64, la lista exacta de `extension=` y los valores de `php.ini`, Composer, `docker run` de Postgres y `CREATE DATABASE laravel_test`. El `phpunit.xml` del repo ya apuntando a `pgsql`/`laravel_test`. Agregar al PRD o al GATES una tabla "historia → responsable" (por ejemplo: A = auth, perfil y tokens; B = Artistas, Álbumes, DataTables y PDF; C = GoRest, chart, API y deploy), y que cada quien demuestre lo suyo en el ensayo.

#### A5. El calendario pone el host definitivo y el correo real al final, y el pipeline de specs se come la primera semana
- **Dónde**: 00 §1 (riesgo de muerte = despliegue); 02_TRD §5 (VM Intel vence hacia el 2026-11-20; host definitivo el 2026-11-15; Resend "decidido"); estado en `~/.claude/CLAUDE.md` (Portfolio "Sin `RESEND_*`").
- **Qué está mal**: el mayor riesgo declarado (despliegue más correo) tiene la fecha límite más tardía: 2026-11-15, seis días antes de la sustentación y cinco antes de que venza la VM. Los registros DNS de Resend para `helx.page` todavía no existen: el Portfolio sigue sin enviar. Pasar a Ampere A1 depende de que haya capacidad en la región, algo que Oracle no garantiza (NO VERIFICABLE desde aquí). Quedan las Etapas 4, 5 y 6 antes de la primera línea de código, con 29 días para 3 personas a tiempo parcial.
- **Corrección**: aprobar ya el 04 recortado (ver §4), fusionar la Etapa 5 con las migraciones y escribir el GATES en una tarde. **Walking skeleton antes del 2026-10-30**: Laravel 12 vacío más Fortify, desplegado en el host que va a existir el 2026-11-21, con un correo de reset real llegando por Resend. Si A1 no tiene capacidad o el upgrade a Pay As You Go no está resuelto para esa fecha, activar ya un plan B pagado de unos 5 USD (por ejemplo, un VPS genérico con Docker) en lugar de descubrirlo el 2026-11-15.

#### A6. El fail-fast de las subidas es falso con la configuración por defecto: `store()` devuelve `false` y la fila se guarda igual
- **Dónde**: 02_TRD §3, Tolerancia a fallos ("Si falla el guardado del archivo nuevo, la excepción corta la petición y no se escribe la fila"); 03_APP_FLOW §4.
- **Qué está mal**: en `config/filesystems.php` de Laravel 12 todos los discos traen `'throw' => false`. Si la escritura falla (justo el caso de permisos de A3), `$file->store()` devuelve `false` sin excepción, la fila se guarda con `photo_path = false` o vacío y la ficha muestra una imagen rota. En producción, el ítem "subir imágenes" falla sin que nadie lo vea.
- **Evidencia**: `laravel/laravel` 12.x, `config/filesystems.php` líneas 37 y 46 (`'throw' => false` en `local` y `public`).
- **Corrección**: `'throw' => true` en el disco `public` (una línea), y guardar el archivo **antes** de abrir la transacción. Si la transacción falla después, borrar el archivo nuevo dentro del `catch` y relanzar la excepción. El TRD solo cubre la imagen vieja, no la nueva que queda huérfana.

### MEDIO

#### M1. Policy (403) contra "404 propio": contradicción entre tablas
- **Dónde**: 03_APP_FLOW §1 (guardas "`auth` + `ArtistPolicy`", API "`auth:sanctum` + Policy") frente a §1 "Errores de la API: 404 ... de otro usuario" y §2 "Ficha: artista ajeno o inexistente da 404". El 01 H5/H13 dice "403 (o 404)".
- **Qué está mal**: con route model binding implícito más una Policy que devuelve `false`, el resultado es **403**, no 404. El test escrito contra el App Flow va a fallar.
- **Corrección**: en las Policies, `return $user->id === $artist->user_id ? Response::allow() : Response::denyAsNotFound();` (método existente en `Illuminate\Auth\Access\Response`, verificado en 12.x), o resolver por `$request->user()->artists()->findOrFail($id)`. Escoger uno y escribirlo en el TRD.

#### M2. Configuración por defecto de Fortify 1.41 que hay que cambiar y que ningún spec menciona
- **Qué está mal**: `config/fortify.php` publicado trae `'home' => '/home'`: después del login o el registro, Fortify redirige a `/home`, que no existe (404). `features` trae activos `twoFactorAuthentication` y **`passkeys`**, y Fortify 1.41 ya exige `laravel/passkeys` como dependencia. El PRD dice que la 2FA está "apagada".
- **Evidencia**: `laravel/fortify` 1.x, `stubs/fortify.php` (`'home' => '/home'`; `features` con `twoFactorAuthentication([...])` y `passkeys([...])`); `composer.json` de Fortify (`"laravel/passkeys": "^0.2.0"`).
- **Corrección**: en el TRD, `'home' => '/dashboard'` y `features` solo con `registration`, `resetPasswords`, `updateProfileInformation` y `updatePasswords`. No correr la migración de columnas 2FA, o dejarla sin uso.

#### M3. La app "solo en español" va a mostrar los errores de validación y de auth en inglés
- **Dónde**: 01_PRD §3 (fuera de alcance i18n: "la app va solo en español"); 03_APP_FLOW §2 (mensajes en español).
- **Qué está mal**: Laravel 12 no trae `lang/es`. "The email has already been taken.", "These credentials do not match our records." y "The photo field must be an image." salen en inglés en la demo.
- **Corrección**: `APP_LOCALE=es` y `php artisan lang:publish`, más los archivos `es` de `validation.php`, `auth.php`, `passwords.php` y `pagination.php` (a mano o con `laravel-lang/common` como dependencia de desarrollo). Agregar un test que afirme un mensaje en español.

#### M4. GoRest: timeout de 10 s con 2 reintentos supera el `max_execution_time` de PHP
- **Dónde**: 02_TRD §3 ("`timeout(10)`. Los `GET` reintentan 2 veces").
- **Qué está mal**: en el peor caso son 3 × 10 s más las pausas, es decir 30 s o más, igual al `max_execution_time` por defecto de PHP (30 s). La petición muere con un fatal antes de pintar el "error honesto con Reintentar", y en la demo son 30 s de pantalla congelada.
- **Corrección**: `connectTimeout(3)->timeout(6)->retry(2, 200, fn ($e) => $e instanceof ConnectionException, throw: false)` solo en GET. Así el peor caso queda por debajo de 15 s.

#### M5. Límites de subida de PHP en el contenedor
- **Qué está mal**: sin `php.ini` propio, PHP usa sus valores compilados: `upload_max_filesize=2M` y `post_max_size=8M`. Un archivo de 2 a 8 MB llega con `UPLOAD_ERR_INI_SIZE` y el usuario ve "failed to upload" en lugar del mensaje de 2 MB. Por encima de 8 MB, Laravel lanza `PostTooLargeException` (413), y no hay vista `errors/413`. En local, `php.ini` tiene `upload_max_filesize = 8M`, así que el problema no se ve hasta producción.
- **Corrección**: un `conf.d/uploads.ini` en la imagen (`upload_max_filesize=4M`, `post_max_size=8M`) y el mismo valor en el README local. Agregar una vista `errors/413` o un mensaje.

#### M6. El prototipo es estático: lo de DataTables, `<dialog>` y 320/375 px no está probado contra el DOM real
- **Dónde**: 04 §0 (artefactos), §8, §12.
- **Qué está mal**: `prototype.html` no carga DataTables ni Chart.js. Pinta un marcado imitado y el diálogo es un `<div role="dialog">`, no un `<dialog>` con `showModal()`. Las unas 80 líneas de CSS propio para DataTables apuntan a clases que la 2.3.8 sí genera (`dt-paging-button`, `dt-column-order` y `aria-sort` están en `dataTables.mjs`), pero nadie las probó sobre la salida real: búsqueda, `select` de longitud y flechas. La afirmación "verificado a 320 y 375 px" no tiene captura ni registro en el repo.
- **Corrección**: en el GATES, una compuerta con DataTables 2.3.8 real y 25 filas del seeder, capturas a 1280 y 375 px y el diálogo abierto con teclado. Si el CSS propio no aguanta, importar `dataTables.dataTables.css` con unas 20 líneas de override: menos código que defender.

#### M7. El borrado sin JS exige rutas que no están en la tabla de rutas
- **Dónde**: 03_APP_FLOW §2 "Confirmación de borrado" ("Sin JS, el botón lleva a una página de confirmación") y 04 §7.
- **Qué está mal**: implica `GET /artists/{artist}/delete` y `GET /albums/{album}/delete`, con vistas y Policies, que no aparecen en §1. Es trabajo para un caso (JS apagado) que no ocurre en la sustentación.
- **Corrección**: cortar el respaldo sin JS. El botón Eliminar abre el `<dialog>`, y sin JS el formulario DELETE se envía directo (el PRD H7 se cumple con el diálogo).

### BAJO

- **L1** 00 §2: "DataTables JS (CDN, sin jQuery desde la v2)" es FALSO: `datatables.net@2.3.8` declara `"jquery": ">=1.7"` y `dataTables.mjs` hace `import jQuery from 'jquery'`. El TRD lo corrige, pero el 00 aprobado y la ficha del proyecto en `~/.claude/CLAUDE.md` (línea 229, "DataTables y Chart.js por CDN") siguen diciendo lo contrario. Actualizar los dos.
- **L2** 01 H4 y 03 §1: "enlace firmado". El reset de Laravel no usa URL firmada: es un token aleatorio guardado con hash en `password_reset_tokens`, que vence a los 60 min (`expire => 60`). Cambiar la redacción para no defender algo falso en la sustentación.
- **L3** 03 §2 GoRest 401: la documentación dice que el cuerpo es "Invalid token" tanto sin token como con uno inválido. En vivo, sin token responde `{"message":"Authentication failed"}` y con un token inválido `{"message":"Invalid token"}`. Decidir por código de estado, nunca por el texto.
- **L4** 02 §6.1 Resend: usar el puerto 465 o 587 (`smtp.resend.com`, usuario `resend`, clave = API key; verificado en la documentación de Resend). Oracle Cloud suele bloquear la salida por el 25 (NO VERIFICADO en esta sesión). Hasta verificar el dominio, Resend solo entrega a la cuenta dueña.
- **L5** 04 §3: dice "subconjunto `latin`", pero `@fontsource/atkinson-hyperlegible-mono/400.css` e `index.css` del paquete variable declaran también `latin-ext`. Es inofensivo (`unicode-range`); corregir el texto.
- **L6** 03 §1: `throttle:60,1` limita por usuario autenticado, no "por token" como dice el TRD §3. Ajustar la redacción.
- **L7** 01 §4: la métrica habla de "los 15 requisitos" y la rúbrica tiene 17 ítems. El "guion de sustentación escrito" no tiene dueño ni documento: asignarlo en el GATES.

---

## 2. Tabla de veracidad

| # | Afirmación (documento) | Estado | Fuente / evidencia |
| :--- | :--- | :--- | :--- |
| 1 | Laravel 13 es la actual (v13.38) y la 12.x sigue con parches (v12.70.1, 2026-10-21) (02) | VERIFICADA | Packagist `laravel/framework`: v13.38.0 (2026-10-20), v12.70.1 (2026-10-21) |
| 2 | `composer create-project laravel/laravel:^12.0` fija framework ^12 (02) | VERIFICADA | `laravel/laravel` v12.12.2; `composer.json` 12.x: `"laravel/framework": "^12.0"` |
| 3 | PHP 8.4.27 local; Laravel 12 pide `^8.2` (02) | VERIFICADA | `php.exe -v` → 8.4.27 NTS x64; `composer.json` 12.x |
| 4 | Fortify 1.41, compatible con Laravel 12 (02) | VERIFICADA | Packagist: v1.41.0 (2026-09-28), `illuminate/support ^11.0\|^12.0\|^13.0` |
| 5 | Fortify trae `PUT /user/profile-information` y `PUT /user/password` (03) | VERIFICADA | `fortify/routes/routes.php` líneas 105 y 112 |
| 6 | Error bags separados por formulario en Fortify (03) | VERIFICADA | `stubs/UpdateUserProfileInformation.php`: `validateWithBag('updateProfileInformation')`; `UpdateUserPassword.php`: `validateWithBag('updatePassword')` |
| 7 | La foto viaja por `/user/profile-information` | VERIFICADA | `ProfileInformationController` pasa `$request->all()`; `InteractsWithInput::all()` mezcla `allFiles()` |
| 8 | Rate limit de login: 5/min por correo e IP (02, 03) | VERIFICADA | `stubs/FortifyServiceProvider.php`: `Limit::perMinute(5)->by(email\|ip)` |
| 9 | `/forgot-password` con throttle 6/min (03) | FALSA | `routes.php` líneas 64-66: solo `guest`. Existe el throttle del broker (60 s por correo existente) |
| 10 | "Olvidé" responde igual exista o no el correo (01, 03) | FALSA por defecto | `FailedPasswordResetLinkRequestResponse` devuelve `trans('passwords.user')` |
| 11 | Fortify trae la 2FA "apagada" (00) | FALSA | `stubs/fortify.php`: `twoFactorAuthentication` y `passkeys` activos por defecto |
| 12 | Sanctum 4.3 con `php artisan install:api` (02) | VERIFICADA | Packagist v4.3.3; `ApiInstallCommand` (12.x) instala `laravel/sanctum:^4.0`, publica la migración y `routes/api.php` |
| 13 | `last_used_at` se actualiza y un token revocado da 401 al instante (03) | VERIFICADA | `sanctum/src/Guard.php` líneas 128 y 167 (búsqueda en BD por petición, `forceFill(['last_used_at' => now()])`) |
| 14 | Ruta `/up` en Laravel 12 (02, 03) | VERIFICADA | `bootstrap/app.php` 12.x: `health: '/up'` |
| 15 | `$middleware->trustProxies(at: '*')` (02) | VERIFICADA | `Foundation/Configuration/Middleware.php` línea 674: `trustProxies(array\|string\|null $at = null, ...)` |
| 16 | `guest` redirige al dashboard si hay sesión (01 H5) | VERIFICADA (si la ruta se llama `dashboard`) | `RedirectIfAuthenticated.php`: prueba `route('dashboard')`, luego `home` |
| 17 | Fortify redirige al dashboard tras login/registro | FALSA por defecto | `stubs/fortify.php`: `'home' => '/home'` |
| 18 | `barryvdh/laravel-dompdf` 3.1 (02) | VERIFICADA | Packagist v3.1.2; requiere `dompdf/dompdf ^3.0` (v3.1.6) |
| 19 | Imágenes locales con `isRemoteEnabled=false` (02, 04) | VERIFICADA | `config/dompdf.php`: `'enable_remote' => false`, `'chroot' => realpath(base_path())` (`storage/app/public` queda dentro) |
| 20 | dompdf pinta WebP (04 supuesto 3) | VERIFICADA (código); pendiente prueba en el contenedor | dompdf `Helpers.php` (`IMAGETYPE_WEBP`), `Image/Cache.php` (tipos incluyen `webp`); GD local: `WebP Support => true`; `install-php-extensions` compila gd con `libwebp` (líneas 982-1022). Confirmar con `php -r 'var_dump(gd_info()["WebP Support"]);'` dentro de la imagen |
| 21 | `dunglas/frankenphp:1-php8.4` existe, multi-arch, con `install-php-extensions` (02) | VERIFICADA | Docker Hub: amd64, arm64, arm, 386 (2026-10-23); su Dockerfile descarga `install-php-extensions` |
| 22 | FrankenPHP "sirve HTTP en :8000" sin más (02) | FALSA sin configuración | Caddyfile de la imagen: `{$SERVER_NAME:localhost}` (HTTPS); hace falta `SERVER_NAME=:8000` |
| 23 | `node:22-alpine` y `postgres:17-alpine` publican arm64 (02) | VERIFICADA | Docker Hub |
| 24 | Contenedor `laravel-pg` con PostgreSQL 17 (02) | VERIFICADA | `pg_isready` → accepting; `postgres --version` → 17.11; puerto en `127.0.0.1:5432` |
| 25 | Base `laravel_test` para los tests (02) | FALSA hoy | `\l` lista `laravel`, `postgres`, `template0` y `template1` |
| 26 | GoRest `GET /public/v2/users` 200 con `id, name, email, gender, status` (00, 03) | VERIFICADA | `curl` 2026-10-23: 200, 10 por página, cabeceras `x-pagination-*` |
| 27 | GoRest `POST` sin token → 401 "Authentication failed" (00) | VERIFICADA | `curl -X POST` → 401 `{"message":"Authentication failed"}` |
| 28 | GoRest acepta PUT (no solo PATCH) (01, 03) | VERIFICADA | Portada: "PUT /public/v2/users/{id} Replace a user (full)" y PATCH parcial; `curl -X PUT` sin token sobre un id real → 401 (la ruta existe). PUT exige los 4 campos |
| 29 | GoRest 422 como `[{field, message}]` (03) | VERIFICADA | `gorest.co.in/docs/http-status-codes` |
| 30 | GoRest 429 con un mensaje propio (03) | VERIFICADA | `docs/rate-limiting`: 90 req/min por token por defecto (configurable 1-300), `X-RateLimit-Reset`, `{"message":"Too many requests"}` |
| 31 | GoRest permite editar y borrar cualquier fila listada (03, implícito) | FALSA según la documentación | `docs/http-status-codes`: 403 si otro token creó la fila. No probado en vivo (exige token) |
| 32 | `Http` de Laravel: `withToken`, `acceptJson`, `timeout` (02) | VERIFICADA (API pública de Laravel; no consultada hoy) | Documentación de Laravel HTTP Client |
| 33 | Resend por SMTP (02) | VERIFICADA | `resend.com/docs/send-with-smtp`: host `smtp.resend.com`, puertos 25/465/587/2465/2587, usuario `resend`, clave = API key |
| 34 | DataTables 2 depende de jQuery (02) / "sin jQuery desde la v2" (00) | 02 VERIFICADA, 00 FALSA | `npm view datatables.net@2.3.8 dependencies` → `jquery >=1.7` |
| 35 | `datatables.net-dt` latest = 3.1.4; última 2.x = 2.3.8 (04) | VERIFICADA | `npm view` dist-tags y versiones |
| 36 | Opciones de idioma usadas (`aria.orderableReverse`, etc.) y clases `dt-*` (04 §8) | VERIFICADA (código) | `dataTables.mjs` 2.3.8: `orderableReverse`, `dt-paging-button`, `dt-column-order`, `aria-sort` |
| 37 | DataTables y Chart.js no inyectan `<style>` ni atributos `style` en el HTML (04 §11, CSP) | VERIFICADA parcialmente | `grep` sobre `chart.js@4.5.1` y `dataTables.mjs` 2.3.8: 0 `createElement('style')` y 0 `style="` literales. Falta probarlo en el navegador con la CSP real |
| 38 | Chart.js 4 por npm (02) | VERIFICADA | `chart.js` latest 4.6.1 |
| 39 | Fontsource 5.3.0 y la familia "Atkinson Hyperlegible Next Variable" (04) | VERIFICADA | `npm view` 5.3.0 en los tres paquetes; `index.css` declara ese `font-family` |
| 40 | Variables de `@theme` disponibles en `:root` para JS (04 §9) | FALSA (solo se emiten las usadas) | Documentación de Tailwind, "Generating all CSS variables" |
| 41 | 28 pares de contraste, 0 fallas (04) | VERIFICADA | `node specs/design/contrast.mjs` → tabla idéntica, "28 pares medidos, 0 fallas", exit 0 |
| 42 | deliberate `--strict`: 0/0/0 (04) | VERIFICADA | `check.mjs prototype.html --strict` → "0 error 0 warn 0 advisory", exit 0. El checker avisa que solo lee el texto fuente |
| 43 | Sin scroll horizontal a 320 y 375 px (04) | NO VERIFICADA | No hay captura ni registro; no lo reproduje |
| 44 | "La imagen nueva falla → excepción" (02) | FALSA por defecto | `config/filesystems.php`: `'throw' => false` |
| 45 | `DB::afterCommit` existe (02) | VERIFICADA | `ManagesTransactions.php` línea 354 |
| 46 | `laravel.helx.page` resuelve a la VPS (01 H15) | VERIFICADA (DNS) | `nslookup` → 157.137.195.173. La app no está desplegada |
| 47 | Herd Lite / php.new sirve con PostgreSQL (00) | FALSA | `~/.claude/CLAUDE.md` línea 228: Herd Lite "no trae `pdo_pgsql`" |
| 48 | Breeze retirado en favor de los starter kits (02) | NO VERIFICADA hoy | No consultada en esta sesión |
| 49 | Disponibilidad de Ampere A1 y plazo de la VM Intel (02) | NO VERIFICABLE | Depende de la consola de Oracle |

---

## 3. Cobertura de la rúbrica

| Ítem de la rúbrica | Lo cubre | Estado |
| :--- | :--- | :--- |
| Laravel 12 | 02 §1, §5 (gate `artisan --version`) | OK |
| Equipo de 3 | 00 supuesto 4 | **Hueco**: sin reparto por persona (A4) |
| Vistas que heredan maestras | 00 §2, 02 §1, 04 §7 (`layouts.guest`, `layouts.app`) | OK |
| BD MySQL/PostgreSQL/SQL Server | 02 §1 (PostgreSQL 17) | OK |
| Landing page | H1, 04 §7 | OK |
| Formulario propio de registro y login | H2, H3 (Fortify + Blade) | OK; ajustar `home` (M2) |
| Rutas protegidas | H5, 03 §1 | OK; 403/404 inconsistente (M1) |
| BD con migraciones | 00 §4, 05 pendiente | OK (la Etapa 5 puede fusionarse con las migraciones) |
| Modelos y controladores coherentes | 02 §2 | OK |
| Restaurar contraseña | H4, 02 §6.1 | **Riesgo**: enumeración (A1), SMTP sin DNS (A5) |
| Dashboard con usuario activo | H6 | OK |
| Menú: logout correcto | H3, 03 §4 (`no-store`) | OK |
| Menú: DataTables de dos tablas | H9 | OK; CSS sin probar (M6) |
| Menú: editar perfil | H10 | OK |
| Al menos un chart | H11 | OK; colores rotos (A2). Sobra el segundo gráfico |
| CRUD de al menos dos tablas | H7, H8 | OK |
| Ruta API consumible desde fuera | H13 | OK |
| Informe PDF | H12 | OK. Sobra el segundo PDF |
| Subir imágenes | H7, H8, H10 | **Riesgo** en producción: A3 y A6 |
| Consumir REST con GET/POST/PUT/DELETE | H14 | **BLOQUEANTE** B1 (PUT y DELETE sobre filas ajenas → 403) |
| Tokens, cabeceras y autorización | H13 (servidor), H14 (cliente), recuadro técnico | OK |
| Publicado en internet | H15, 02 §4 | **Riesgo**: A3, A5 |
| Copia en GitHub | H15, 02 §6.3 | OK (falta nombre del repo, menor) |

Ningún ítem de la rúbrica queda sin historia. Los huecos son de ejecución, no de alcance.

---

## 4. Lo que sobra (recortes concretos)

La rúbrica pide "al menos uno" en chart, PDF y API. Todo lo que pasa de uno es trabajo sin puntos, con más superficie que defender.

1. **Segundo gráfico (álbumes por década)**: fuera. Queda "álbumes por género" con su tabla de datos. Ahorra una consulta, un canvas, una tabla y su test.
2. **Segundo PDF (catálogo completo)**: fuera. Queda el PDF por artista, que además lleva imagen, el caso más difícil.
3. **Tokens de API por dos vías más revocación más `last_used_at` más `DELETE /tokens/current`**: dejar la creación en `/profile` (lo más claro para la demo) y `POST /api/v1/tokens`. Cortar `DELETE /api/v1/tokens/current` y la columna "último uso".
4. **Respaldos sin JS** (página de confirmación de borrado, "la tabla sigue siendo tabla sin JS" como requisito): fuera (M7).
5. **Seis páginas de error**: dejar 403, 404, 419 y 500 heredando la maestra. 429 y 503 quedan con las de Laravel.
6. **CSP estricta propia**: cero puntos en la rúbrica y alto riesgo de pantallas en blanco (Vite, Fontsource, DataTables) si nadie la prueba en producción hasta tarde. Mantenerla solo si entra en el walking skeleton de la semana 1 (A5). Si no, desplegarla como `Content-Security-Policy-Report-Only`.
7. **Plan de migración a ARM, cron de respaldo con retención de 7 días, RPO**: para una demo basta un `pg_dump` manual antes del ensayo y otro antes de la sustentación. La migración a ARM solo existe porque vence la VM: resolver el host primero (A5) y borrar lo demás.
8. **Etapa 5 como documento aparte**: son tres tablas (`users`, `artists`, `albums`) más `personal_access_tokens`. Las migraciones son el esquema; escribirlas directamente.
9. **04_DESIGN_BRIEF (430 líneas)**: es sobrediseño para 3 estudiantes, y lo que sobra es el proceso, no la paleta. Recortar a una hoja implementable:
   - Fuera para quien implementa: "Hoja de decisión", "Pasada de crítica", "Ruta de Hallmark", "Los 3 diales". No ayudan a escribir ni una vista y no se pueden defender en clase.
   - Motion: de 5 momentos a 2 reglas (hover y `:active` de 90-140 ms; `prefers-reduced-motion` desactiva todo). Fuera el aro giratorio y el `scale` del diálogo.
   - Tipografía: de 3 familias a 2. Fuera Atkinson Hyperlegible Mono; `ui-monospace` alcanza para el token y el recuadro técnico.
   - La galleta: un solo componente con iniciales y un tamaño por clase. Fuera la variante `--empty` y el `sm` sin iniciales.
   - La matriz de 14 componentes × 9 estados: quedarse con botón, input, flash, tabla y estado vacío. El resto sale del mismo patrón.
   - `contrast.mjs` ya cumplió: 0 fallas. No hace falta como compuerta permanente si la paleta no cambia.
   - Lo que **sí** vale y se queda: los tokens OKLCH (§2 y §11, con la corrección de A2), las reglas de imagen 1:1 y el estado vacío honesto, los hex espejo para dompdf y la configuración de DataTables (§8).
10. **Recuadro técnico de GoRest**: se queda. Es la pieza que mejor demuestra "tokens, cabeceras y autorización" en 30 segundos. Lo único que sobra es medir los milisegundos.

---

## 5. Comandos ejecutados (reproducibles)

```text
node specs/design/contrast.mjs                         -> 28 pares, 0 fallas, exit 0
node ~/.claude/skills/deliberate/scripts/check.mjs specs/design/prototype.html --strict
                                                       -> 0 error 0 warn 0 advisory, exit 0
C:\Users\santi\.config\php-8.4\php.exe -v              -> PHP 8.4.27 NTS x64
php.exe -m                                             -> pdo_pgsql, gd, intl, zip, fileinfo; SIN pdo_sqlite
php.exe -r 'gd_info()'                                 -> WebP Support => true
docker exec laravel-pg pg_isready                      -> accepting connections
docker exec laravel-pg psql -U laravel -c '\l'         -> laravel, postgres (sin laravel_test)
curl https://gorest.co.in/public/v2/users              -> 200, x-pagination-total 2996, limit 10
curl -X POST .../users (sin token)                     -> 401 {"message":"Authentication failed"}
curl -X POST .../users (Bearer inválido)               -> 401 {"message":"Invalid token"}
curl -X PUT|DELETE .../users/8683835 (sin token)       -> 401 (ruta existe; no se modificó nada)
nslookup laravel.helx.page                             -> 157.137.195.173
npm view datatables.net@2.3.8 dependencies             -> {"jquery": ">=1.7"}
```

Fuentes leídas: `laravel/fortify` 1.x (`routes/routes.php`, `PasswordResetLinkController`, `FailedPasswordResetLinkRequestResponse`, `stubs/fortify.php`, `stubs/FortifyServiceProvider.php`, `composer.json`), `laravel/laravel` 12.x (`bootstrap/app.php`, `config/auth.php`, `config/filesystems.php`, `phpunit.xml`, `.env.example`, `package.json`), `laravel/framework` 12.x (`Middleware.php`, `ApiInstallCommand.php`, `ManagesTransactions.php`, `InteractsWithInput.php`, `RedirectIfAuthenticated.php`, `Auth/Access/Response.php`), `laravel/sanctum` 4.x (`Guard.php`), `dompdf/dompdf` (`Helpers.php`, `Image/Cache.php`), `barryvdh/laravel-dompdf` (`config/dompdf.php`), `php/frankenphp` (`Dockerfile`, `caddy/frankenphp/Caddyfile`), `mlocati/docker-php-extension-installer`, Docker Hub API, Packagist p2, npm registry, unpkg, `gorest.co.in` (portada, `docs/http-status-codes`, `docs/rate-limiting`, `docs/bearer-tokens`), `tailwindcss.com/docs/theme` y `resend.com/docs/send-with-smtp`.

# Revisión dura de 05_BACKEND_SCHEMA y 06_GATES (más pasada transversal 00-04 y CONTEXT)

> **Estado (2026-10-24)**: aplicado en 01-06 por decisión del dueño; decisiones: reset 15 min, ULID y dos vías de token se mantienen, P8 a pendientes del dueño.

Fecha: 2026-10-24. Alcance: `05`, `06` y su coherencia con `00` a `04`, `CONTEXT.md` y la rúbrica. No se repiten los hallazgos de `REVIEW_00-04.md`, salvo donde la corrección quedó incompleta (marcado "corrección previa incompleta").

**Veredicto**: no hay BLOQUEANTES de alcance, pero hay 8 ALTO que rompen la demo o dejan rúbrica sin observar, y el ledger tiene una compuerta imposible de cumplir (F14). Con los ALTO corregidos el plan sirve; tal como está, el primer deploy (S4, 2026-10-30) falla y un login con mayúscula en el correo falla en vivo.

Conteo: BLOQUEANTE 0 · ALTO 8 · MEDIO 13 · BAJO 9.

Convención de evidencia: "VERIFICADO" = leído en el código fuente de la rama indicada o ejecutado hoy; "NO VERIFICADO" = razonado, sin fuente a mano. Fuentes leídas (raw.githubusercontent.com, 2026-10-24): `laravel/framework` 12.x (`Handler.php`, `Authenticate.php`, `File.php`, `ImageFile.php`, `Unique.php`, `Response.php`, `HasUniqueStringIds.php`, `Compiler.php`, `config/view.php`, `ResetPassword.php`, `Foundation/Testing/DatabaseTransactionsManager.php`, `VerifyCsrfToken.php`), `laravel/laravel` 12.x (`phpunit.xml`, `.env.example`, `bootstrap/app.php`, `config/auth.php`, `config/filesystems.php`), `laravel/fortify` 1.x (rutas, controladores, stubs de acciones, `config/fortify.php`), `laravel/sanctum` 4.x (`Guard.php`, migración, `config/sanctum.php`), `nunomaduro/collision` v8 (`Style.php`), `php/frankenphp` (`docs/config.md`), docs de `postgres` (docker-library) y PostgreSQL 17, y el código de `unlazy` (`gate-check.mjs`, `lib/gates.mjs`, `gate-lint.mjs`) instalado en la máquina.

---

## ALTO

### A1. El correo se guarda en minúsculas por un mecanismo que no existe: el login con mayúscula falla y el duplicado entra al INSERT
- **Dónde**: 05 §1 (`users.email`: "lo normalizan las acciones de Fortify `CreateNewUser` y `UpdateUserProfileInformation`"); 02 §6 (no menciona `lowercase_usernames`).
- **Qué está mal**: las acciones no normalizan nada. Los stubs `CreateNewUser` y `UpdateUserProfileInformation` validan y guardan `$input['email']` tal cual. El `Str::lower` lo hacen los controladores de Fortify (`RegisteredUserController`, `ProfileInformationController`, `PasswordResetLinkController`, `NewPasswordController` y, en el login, el paso `CanonicalizeUsername`), y solo si `config('fortify.lowercase_usernames')` es `true`. El default es `false`. Consecuencias en PostgreSQL (comparación sensible a mayúsculas): se registra `Ana@x.com`, queda guardado `Ana@x.com`, y quien entra con `ana@x.com` recibe "credenciales inválidas"; el celular del integrante capitaliza la primera letra y la demo falla en vivo. Con el spec tal cual leído (normalizar en la acción, después de validar) el `unique` de Laravel compara el valor sin normalizar y el INSERT estalla contra el `UNIQUE` con un 500.
- **Evidencia**: VERIFICADO. `fortify/1.x`: `config/fortify.php` (`'lowercase_usernames' => false`), `Http/Controllers/RegisteredUserController.php:56`, `ProfileInformationController.php:24`, `AuthenticatedSessionController.php:87`, `PasswordResetLinkController.php`, `NewPasswordController.php`; `stubs/CreateNewUser.php` y `stubs/UpdateUserProfileInformation.php` sin `lower`.
- **Corrección**:
  - 05 §1, fila `email`: sustituir "Se guarda en minúsculas (lo normalizan las acciones de Fortify ...)" por "Se guarda en minúsculas: `config/fortify.php` → `'lowercase_usernames' => true` (los controladores de Fortify hacen `Str::lower` antes de validar). `POST /api/v1/tokens` aplica `Str::lower($email)` a mano."
  - 02 §6, lista de `config/fortify.php`: agregar `'lowercase_usernames' => true`.
  - 06, S3: agregar a `tests/Feature/Auth` dos casos: usuario registrado como `ana@x.com` entra con `Ana@X.com`; registrar `ANA@x.com` con `ana@x.com` existente da 422 y no 500.

### A2. La API no devuelve JSON por sí sola: sin `Accept` un 401 es una redirección HTML y el 404 filtra el nombre de la clase
- **Dónde**: 03 §1 ("`Accept: application/json` forzado en el grupo"); 05 §4 ("Cabeceras de toda petición: `Accept: application/json`" y "Un `404` de modelo dice 'No encontrado', sin el nombre de la clase"); 02 §6 (no lista nada de esto); 06 F9.
- **Qué está mal**:
  1. Laravel 12 solo responde JSON si `$request->expectsJson()`. `Authenticate::unauthenticated` redirige a `route('login')` cuando no lo es, y Fortify define `login`, así que `GET /api/v1/artists` sin token desde Postman (que manda `Accept: */*`) devuelve 302 y la página de login, no 401. Ningún spec dice cómo se fuerza JSON; 02 §6 ("Configuración Obligatoria") no lo incluye.
  2. `ModelNotFoundException` se convierte en `NotFoundHttpException($e->getMessage())`, y en producción `convertExceptionToArray` devuelve ese mensaje: `"No query results for model [App\\Models\\Artist] 01J..."`. El spec promete otro texto y nadie lo implementa.
  3. F9 usa tests con `getJson`, que ya trae `Accept: application/json`: la compuerta pasa aunque el comportamiento real con Postman esté roto.
- **Evidencia**: VERIFICADO. `Authenticate.php:99-110` (`$request->expectsJson() ? null : $this->redirectTo($request)`); `Handler.php:679` (ModelNotFound a NotFoundHttpException con el mensaje original), `:820-825` (`shouldReturnJson` cae en `expectsJson()` si no hay callback), `:1038` (mensaje de HttpException en producción).
- **Corrección** (02 §6 y 05 §4, con test en F9):
  ```php
  // bootstrap/app.php
  ->withExceptions(function (Exceptions $exceptions): void {
      $exceptions->shouldRenderJsonWhen(fn (Request $r, Throwable $e) => $r->is('api/*') || $r->expectsJson());
      $exceptions->render(function (NotFoundHttpException $e, Request $r) {
          if ($r->is('api/*')) {
              return response()->json(['message' => 'No encontrado'], 404);
          }
      });
  })
  ```
  `shouldReturnJson` también gobierna el 401 (`unauthenticated`) y el 422 (`convertValidationExceptionToResponse`), así que un solo callback cubre los tres. Agregar `"Unauthenticated."` a `lang/es.json`. F9 debe incluir peticiones con `->get()` (sin `Accept`) que afirmen 401 JSON, 422 JSON y que el cuerpo del 404 no contenga `App\Models`.

### A3. I2 se reparte en tres sitios sin orden definido: una petición a un artista ajeno puede dar 422 o un mensaje distinto del inexistente
- **Dónde**: 05 §2 I2 y §3 (`ignore($this->route('artist'))`); 03 §1 (rutas API con `{id}`); 06 F1.
- **Qué está mal**: el binding implícito de ULID resuelve cualquier artista, ajeno o no, antes de la autorización. En un `PUT /artists/{ajeno}`: si `authorize()` del FormRequest no consulta la Policy (el spec no dice que lo haga), la validación corre primero y devuelve 422 por un registro que no es del usuario; eso revela que existe. Además, en la API el inexistente dice "No query results for model..." y el ajeno dice "Not Found" (el `message` de `denyAsNotFound` cae en `Response::$statusTexts[404]`): dos textos distintos para lo que I2 promete indistinguible.
- **Evidencia**: VERIFICADO. `Handler.php:679-682` (dos rutas de conversión con mensajes distintos); `HasUniqueStringIds.php:54-64` (el binding con ULID inválido lanza ModelNotFound; con ULID válido resuelve sin mirar al usuario). NO VERIFICADO el orden en `ValidatesWhenResolvedTrait` (de memoria: `authorize()` corre antes de las reglas).
- **Corrección**: una sola barrera, el binding con alcance de usuario, y la Policy queda como segunda línea:
  ```php
  // AppServiceProvider::boot()
  Route::bind('artist', fn (string $v) => request()->user()->artists()->findOrFail($v));
  Route::bind('album',  fn (string $v) => request()->user()->albums()->findOrFail($v));
  ```
  (`auth` corre antes de `SubstituteBindings` por la lista de prioridad.) Así ajeno e inexistente producen el mismo `ModelNotFoundException`, antes de FormRequest y Policy. Nombrar el parámetro `{artist}` en 03 §1 y 05 §4 (hoy `{id}`, y las reglas usan `route('artist')`). F1 y F9 deben afirmar que el ajeno y el inexistente devuelven cuerpos idénticos, también con datos inválidos en el PUT. Ojo con la consulta de álbumes: ver M1.

### A4. El `tmpfs` sobre `storage/framework` deja `view.compiled = false` y ninguna vista renderiza (corrección previa incompleta, REVIEW_00-04 A3)
- **Dónde**: 02 §4 (`tmpfs` para `/app/storage/framework` y "el contenedor corre `php artisan optimize` al arrancar").
- **Qué está mal**: el `tmpfs` se monta vacío encima del directorio de la imagen: no existen `framework/views`, `framework/cache/data` ni `framework/sessions`. `config/view.php` del framework define `'compiled' => env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views')))`; `realpath` de una ruta inexistente es `false`, y `Compiler::__construct` lanza `InvalidArgumentException('Please provide a valid cache path.')`. `optimize` (que incluye `view:cache`) falla en el arranque y, si no, cada vista da 500. Lo detecta recién S4 en producción, no un test local.
- **Evidencia**: VERIFICADO. `laravel/framework` 12.x `config/view.php` (líneas finales) y `src/Illuminate/View/Compilers/Compiler.php:73-76`.
- **Corrección** (02 §4): "El entrypoint ejecuta `mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions` antes de `php artisan optimize`" (o fijar `VIEW_COMPILED_PATH=/tmp/views` en el `.env` y crear esa carpeta). Además `LOG_CHANNEL=stderr`: con `storage/logs` en `tmpfs` el log desaparece al reiniciar, y 02 §3 dice que el fallo de SMTP "queda en el log". S4 debe pedir `/login` (ya lo hace) y su `EXPECT` ya cae si hay 500; aun así, un `docker compose up` local con el mismo compose (`read_only` + `tmpfs`) antes del 2026-10-30 lo habría mostrado.

### A5. El reset vence a 60 minutos en el PRD y el App Flow y a 15 en el 05, y ningún gate lo observa
- **Dónde**: 01 H4 ("vence a los 60 minutos"); 03 §1 (fila `/reset-password/{token}`: "vence a los 60 min"); 05 §2 I9 y §7.2 (15 minutos); 06 (sin gate).
- **Qué está mal**: dos valores aprobados para el mismo requisito. Quien escriba el test de H4 desde el PRD afirmará 60.
- **Evidencia**: VERIFICADO el default: `laravel/laravel` 12.x `config/auth.php` (`'expire' => 60, 'throttle' => 60`). El texto del correo usa `config('auth.passwords.users.expire')`, así que cambia solo (`ResetPassword.php:80`).
- **Corrección**: 01 H4 y 03 §1 pasan a "15 minutos" (o el 05 baja a 60 y se borra I9; menos código, pero el 05 ya lo defendió). Agregar a 06 un test en `tests/Feature/Auth` con `Carbon::setTestNow(now()->addMinutes(16))` que afirme que el enlace vencido no cambia la contraseña, y otro que afirme que el enlace usado no sirve dos veces.

### A6. F14 exige "25 filas del seeder" y el seeder tiene 5 artistas y 12 álbumes; además ningún gate corre el seeder
- **Dónde**: 06 F14; 05 §6; 01 §3 ("Seeder de demostración"); 01 §5 (`migrate:fresh --seed` sin pasos manuales, que en 00 §5 es criterio de éxito).
- **Qué está mal**:
  1. 5 artistas y 12 álbumes (3+3+2+2+2) más un segundo usuario con 1 artista. No hay forma de tener 25 filas en ninguna lista; la paginación de DataTables (10 por página) solo se ve con los 12 álbumes. F14 no se puede cumplir tal como está escrita.
  2. Ninguna compuerta ejecuta el seeder. 05 §6 dice que los tests no lo usan; `RefreshDatabase` prueba las migraciones pero no `DemoSeeder` (falla si falta `SEED_DEMO_PASSWORD`, falla por `UNIQUE (user_id, name)` si se corre dos veces). El día de la demo se corre en producción con `--force`.
  3. `SEED_DEMO_PASSWORD` no figura en `.env.example` (02 §6) ni se define su comportamiento en local.
- **Corrección**:
  - F14: "sobre los 12 álbumes del seeder (2 páginas de 10)". Si quieren 25, que `DemoSeeder` use las factories para 13 álbumes de relleno y se diga en 05 §6.
  - Agregar el gate:
    ```text
    - [ ] F15: el seeder de demostración es idempotente y crea el usuario demo con sus 5 artistas y 12 álbumes
      CHECK: php artisan test tests/Feature/DemoSeederTest.php
      EXPECT: /Tests:\s+\d+ passed/
      EVIDENCE: pending
    ```
    con un test que corre el seeder dos veces (`updateOrCreate` por `(user_id, name)` y por `(artist_id, title)`) y afirma los conteos, y que falla en `APP_ENV=production` sin `SEED_DEMO_PASSWORD`.
  - 02 §6 `.env.example`: agregar `SEED_DEMO_PASSWORD=` con valor de desarrollo.

### A7. Ítems de la rúbrica y de las historias sin ninguna compuerta (detalle en la tabla de cobertura)
Los más graves, porque son lo que el docente mira:
1. **Chart en el navegador** (H11): F7 solo prueba que los datos llegan al HTML. Nada verifica que Chart.js dibuje bajo `script-src 'self'`, ni que DataTables y los scripts no generen violaciones de CSP. F14 cubre DataTables, no el gráfico. Una CSP mal puesta deja el chart en blanco en producción y todos los gates en verde.
2. **API consumida desde fuera, en producción** (H13, rúbrica "ruta API consumible desde fuera"): F9 es un test interno; E3 solo graba un video. Ningún gate hace `POST /api/v1/tokens` y `GET /api/v1/artists` contra `https://laravel.helx.page` (que además prueba 401 JSON detrás de Caddy, A2). La colección de Postman de 01 §3 tampoco tiene gate.
3. **GitHub + README** (H15, rúbrica "copia en GitHub"): sin gate. Tampoco "equipos de 3".
4. **Landing** (H1) y **vistas que heredan maestras**: sin gate.
5. **Throttles** de login (5/min), forgot-password (6/min), tokens (5/min; F9 menciona 429 sin decir cuál) y el `Cache-Control: no-store` de "Atrás tras logout" (03 §4): sin gate.
6. **I5, I7, I9**: sin gate (I9 ver A5).
- **Corrección**: gates concretos en la sección "Correcciones de GATES" al final.

### A8. Los gates solo los puede correr el dueño, y la prueba de instalación de los compañeros (mitigación de REVIEW_00-04 A4) desapareció del ledger
- **Dónde**: 06 (G0, P5, P7, P8 usan `C:/Users/santi/.claude/...`, `~/.claude/CLAUDE.md` y `ssh oracle`); 02 §5 riesgo "Un integrante no logra levantar el proyecto"; 06 regla "Nadie marca un gate de otro".
- **Qué está mal**: G0, P5, P7 y P8 solo corren en la máquina del dueño (rutas absolutas, alias SSH con llave propia, archivo personal de reglas). El resto exige PHP 8.4 con `pdo_pgsql`, Docker y la base `laravel_test`, algo que hoy solo existe en esa máquina (02 §7: "En la máquina del usuario ya está todo hecho"). La mitigación del riesgo "no logra levantar el proyecto" era "prueba de instalación la primera semana", y ninguna compuerta la exige. Los gates `F` asignados a A y B no pueden ser verificados por A y B si no tienen entorno. Además `unlazy` guarda las aprobaciones por máquina (`~/.unlazy/approved`).
- **Corrección**: agregar a la fase S:
  ```text
  - [ ] S8: los tres integrantes corren la suite en su propia máquina contra su PostgreSQL local (evidencia: la línea "Tests: N passed" de cada uno, con fecha, hasta 2026-10-28)
    EVIDENCE: pending
  ```
  y mover P7/P8 a una sección "Solo el dueño" (o abandonar P8, ver M9). Declarar en 06 qué gates corre cada quien.

---

## MEDIO

### M1. `User::albums()` como `hasManyThrough` hace JOIN con `artists`: `genre`, `created_at`, `id` quedan ambiguos en PostgreSQL
- **Dónde**: 05 §1 (desviación del TRD) e I2 ("consultas desde `$user->albums()`"); 03 §2 (estadísticas por género).
- **Qué está mal**: `albums` y `artists` comparten `id`, `genre`, `created_at`, `updated_at`. Una consulta como `$user->albums()->groupBy('genre')`, `->orderBy('created_at')`, `->latest()` o `->where('genre', ...)` genera `column reference "genre" is ambiguous` (error de PostgreSQL, no de SQLite). El gráfico de `/stats` es la ruta más expuesta. HasManyThrough hace `inner join artists on artists.id = albums.artist_id where artists.user_id = ?`. NO VERIFICADO contra el código de `HasManyThrough` hoy, pero es el diseño documentado de la relación.
- **Corrección**: en 05 §2 I2 agregar "consultas agregadas y ordenadas sobre `$user->albums()` califican siempre la columna (`albums.genre`)". Para `/stats`: `Album::whereIn('artist_id', $user->artists()->select('id'))->selectRaw('genre, count(*) as total')->groupBy('genre')`. F7 corre contra PostgreSQL, así que lo atrapa, pero conviene que el spec lo diga antes.

### M2. El throttle de `/forgot-password` no tiene mecanismo (corrección previa incompleta, REVIEW_00-04 A1)
- **Dónde**: 02 §3 ("se agrega `throttle:6,1` a esa ruta"); 03 §1.
- **Qué está mal**: la ruta POST de Fortify solo trae `guest`; no hay hook por ruta. La corrección previa propuso `fortify.middleware`, que aplica a **todas** las rutas de Fortify: a 6 páginas por minuto por IP cada `GET /login` y `/register` empezarían a dar 429 (en un salón con una sola IP de salida, durante la demo). NO VERIFICADO el enfoque alternativo.
- **Evidencia**: VERIFICADO `fortify/1.x/routes/routes.php:64-67` (`password.email` solo con `guest`).
- **Corrección**: fijar un mecanismo y probarlo con `php artisan route:list --name=password.email -v`. Propuesta mínima: en `AppServiceProvider::boot`, `$this->app->booted(fn () => Route::getRoutes()->getByName('password.email')?->middleware('throttle:6,1'));`. Agregar un test en `tests/Feature/Auth` que haga 7 POST y afirme 429 en el séptimo, y que `GET /login` 10 veces siga dando 200.

### M3. El correo de reset y varios mensajes de Fortify salen en inglés; falta `MAIL_FROM_ADDRESS` (corrección previa incompleta, REVIEW_00-04 M3)
- **Dónde**: 02 §6 (idioma: solo `lang/es/{validation,auth,passwords,pagination}.php`); 06 S5, F11.
- **Qué está mal**: la notificación de reset usa `Lang::get('Reset Password Notification')`, `Lang::get('You are receiving this email because...')`, etc. (claves JSON). El stub `UpdateUserPassword` usa `__('The provided password does not match your current password.')`. Esas claves viven en `lang/es.json`, que 02 §6 no menciona. El correo real de S5, el de la demo, llega en inglés. Además `.env.example` de Laravel trae `MAIL_FROM_ADDRESS="hello@example.com"`: Resend rechaza remitentes fuera del dominio verificado y el reset da 500 (02 §3: "si el SMTP falla, 500"). `MAIL_FROM_ADDRESS` no figura en 02 §6 ni en el compose.
- **Evidencia**: VERIFICADO `ResetPassword.php:77-81`; `fortify/1.x/stubs/UpdateUserPassword.php`; `laravel/laravel` 12.x `.env.example:56`.
- **Corrección**: 02 §6: agregar `lang/es.json` (por `laravel-lang/common`, o a mano con las 5 cadenas del reset y las de Fortify que se usen) y `MAIL_FROM_ADDRESS="no-reply@helx.page"`, `MAIL_FROM_NAME="Discoteca"`. S5: la evidencia incluye "asunto y cuerpo en español" y "el enlace empieza con `https://laravel.helx.page/reset-password/`". F11: agregar el asunto del correo vía `Notification::fake()` + `toMail`.

### M4. I8: el test de F6 no puede observar sesiones con el driver de pruebas, y `UpdateUserPassword` no rota el `remember_token`
- **Dónde**: 05 §2 I8; 06 F6 y "Seams" (punto 4); 02 §6 (`phpunit.xml`).
- **Qué está mal**:
  1. El `phpunit.xml` de Laravel 12 fija `SESSION_DRIVER=array` y `CACHE_STORE=array`. Con eso la tabla `sessions` nunca se llena en los tests; el test de F6 solo puede insertar filas a mano y afirmar que la acción las borra: prueba el `DELETE`, no que el driver `database` asocie `user_id`.
  2. El stub `UpdateUserPassword` solo guarda el hash; `CompletePasswordReset` sí rota `remember_token`, pero solo en el reset. Si el login ofrece "Recordarme", una cookie de recuerdo robada sobrevive al cambio de contraseña desde el perfil, contra el espíritu de I8.
- **Evidencia**: VERIFICADO `laravel/laravel` 12.x `phpunit.xml` (`SESSION_DRIVER=array`, `CACHE_STORE=array`, `DB_CONNECTION=sqlite`), `fortify/1.x/src/Actions/CompletePasswordReset.php`, `stubs/UpdateUserPassword.php`.
- **Corrección**: 05 I8: "`UpdateUserPassword` y `ResetUserPassword` borran las filas de `sessions` del usuario salvo la actual, ejecutan `$user->setRememberToken(Str::random(60))` y `$user->tokens()->delete()`". 02 §6 `phpunit.xml`: agregar `SESSION_DRIVER=database` si se quiere probar de verdad; si no, declarar que F6 prueba la acción y agregar una comprobación manual en P6 (dos navegadores, cambiar contraseña en uno, el otro cae al login).

### M5. 05 §5 #19 justifica mal la defensa de subidas: FrankenPHP sí ejecuta cualquier `.php` bajo la raíz
- **Dónde**: 05 §5, fila 19 ("FrankenPHP no ejecuta PHP fuera de `public/index.php`"); 06 (I7 sin gate).
- **Qué está mal**: la configuración de `php_server` incluye `@phpFiles path *.php` y `try_files {path} {path}/index.php index.php`: ejecuta cualquier `.php` existente bajo `public/`, y `public/storage` es un symlink al directorio de subidas. Lo que protege hoy es otra cosa: validación por contenido (`mimes`), nombre aleatorio y extensión derivada del MIME en `store()`. Si algún día se cambia a `storeAs` con el nombre original, el RCE aparece y nada lo detecta.
- **Evidencia**: VERIFICADO `php/frankenphp` `docs/config.md` (equivalencia de `php_server`).
- **Corrección**: reescribir la fila 19: "Tipo por contenido, 2 MB, 4000 px, nombre aleatorio y extensión derivada del MIME (`store()`), nunca `storeAs` con nombre del cliente. FrankenPHP ejecuta cualquier `.php` bajo `public/`: la defensa es que nunca se escribe un `.php` ahí." Agregar a F3 un caso que suba un archivo `evil.php.jpg` con contenido de imagen válido y afirme que la ruta guardada coincide con `^(artists|covers|avatars)/[A-Za-z0-9]{40}\.(jpg|png|webp)$` y distinta del nombre original (cubre I7).

### M6. El rol `app` sin superusuario: el spec no dice cómo se crea, y la restauración a la A1 lo rompe
- **Dónde**: 05 §1 ("Usuario de base de datos en producción"), §5 fila 30; 02 §4 (migración a ARM con `pg_dump -Fc` / `pg_restore`).
- **Qué está mal**:
  1. Los scripts de `/docker-entrypoint-initdb.d` solo corren con el directorio de datos vacío; si el volumen `laravel-pgdata` ya existe (por una prueba previa de S), el script se omite en silencio y la app corre como superusuario. Ningún gate lo detecta.
  2. El script debe ser `.sh` (necesita la contraseña de `app`, que un `.sql` no puede leer del entorno) y el servicio `db` debe recibir esa variable en el compose; el spec no lo menciona.
  3. `POSTGRES_DB=laravel` ya lo crea el entrypoint como superusuario antes del script; para que `app` sea "dueño de la base" el script debe hacer `ALTER DATABASE laravel OWNER TO app`. En PostgreSQL 15+ el esquema `public` pertenece a `pg_database_owner`, así que basta con ser dueño de la base; un `GRANT ALL ON DATABASE` a secas no da `CREATE` en `public` y `migrate` falla con "permission denied for schema public".
  4. `pg_restore` ejecutado por el superusuario crea los objetos a nombre del superusuario; `app` no los puede leer. Hace falta `pg_restore --no-owner --role=app` (o `ALTER ... OWNER`). Es justo el paso del 2026-11-20 con presión de tiempo.
- **Evidencia**: VERIFICADO docs de la imagen `postgres` ("only run if ... data directory ... empty"); PostgreSQL 17, "The Public Schema" (el privilegio `CREATE` sobre `public` ya no es público desde la 15). NO VERIFICADO el punto 4 en ejecución.
- **Corrección**: 05 §1, párrafo final: "`initdb.d/10-app.sh`: `CREATE ROLE app LOGIN PASSWORD :'pw'` (con `psql -v pw="$APP_DB_PASSWORD"`), `ALTER DATABASE laravel OWNER TO app`. Solo corre con volumen nuevo: si el volumen existe, crear el rol a mano." Agregar a 06 (fase P):
  ```text
  - [ ] P9: la app corre en producción con un rol que no es superusuario
    CHECK: ssh -o BatchMode=yes oracle "docker exec laravel-db psql -U app -d laravel -tAc 'select rolsuper from pg_roles where rolname=current_user'"
    EXPECT: /^f\s*$/m
    EVIDENCE: pending
  ```
  (ajustar el nombre del contenedor) y un ensayo de restauración (M11).

### M7. Imágenes de hasta 4000 x 4000 px entran enteras al PDF: memoria, tamaño y el límite de 3 s
- **Dónde**: 05 §3 (`maxWidth(4000)->maxHeight(4000)`); 02 §3 (PDF en menos de 3 s); 02 §4 (`mem_limit: 512m`); 04 §9 (PDF); 04 §10 (WebP pendiente).
- **Qué está mal**: dompdf decodifica con GD (16 Mpx x 4 bytes = 64 MB por imagen, más copias) y embebe la imagen sin redimensionar. Un artista con 10 portadas de 4000 px acerca el PDF a los 512 MB del contenedor y supera 3 s. NO VERIFICADO el consumo real; es un riesgo de dimensionamiento. Además "WebP en el PDF" (04 §10 y §11.2) está marcado "pendiente para el GATES" y 06 no lo recoge.
- **Corrección**: bajar el tope a `maxWidth(2000)->maxHeight(2000)` en 05 §3 (basta para fichas de 40 y 120 px y para la grilla de 240 px), y agregar a F8 una portada WebP de 2000 px en el PDF (el test afirma `%PDF` y que la respuesta tarda menos de 3 s en el entorno del test; P6 repite con foto real).

### M8. Dos cosas del esquema que cambian después de S: `users.photo_path` y la regla de "no editar migraciones desplegadas"
- **Dónde**: 05 §1 (`users`: "migración del esqueleto + una columna"); 02 §5 (esqueleto en producción el 2026-10-30).
- **Qué está mal**: si `photo_path` se agrega editando `0001_01_01_000000_create_users_table.php` y esa migración ya corrió en producción en S, la columna nunca llega y el perfil da 500. Hay que decidirlo ahora.
- **Corrección**: 05 §1: "`photo_path` va en una migración nueva (`add_photo_path_to_users_table`); ninguna migración que ya corrió en producción se edita. Hasta 2026-11-14 se permite `migrate:fresh` en producción; después, nunca." Alternativa: crear las 3 tablas y la columna antes del 2026-10-30.

### M9. Compuertas que pasarían con el resultado roto
- **`EXPECT: /Tests:\s+\d+ passed/`** (F1 a F12, S2, S3, P1): VERIFICADO el formato real: Collision imprime `Tests:    N passed (M assertions)` y, si algo falla, `Tests:    1 failed, 4 passed` (el regex no casa porque `\d+` va seguido de `failed`; `skipped`, `todo` y `incomplete` también lo rompen, lo cual es sano). El problema es el otro lado: pasa con un solo test. Un archivo `ProfileTest.php` con un test trivial cumple F6 que lista 6 comportamientos. Corrección: exigir un mínimo por gate, por ejemplo F6 `EXPECT: /Tests:\s+(?:[6-9]|\d{2,}) passed/`, con el mínimo igual al número de comportamientos del título; y partir `ProfileTest` en `ProfileTest`, `PasswordTest` y `TokenUiTest` (F6 mezcla H10 y parte de H13).
- **P5** (`/0 error\S*\s+\S*0 warn/`): VERIFICADO ejecutándolo hoy con `--strict` sobre `specs/design/prototype.html`: la salida lleva códigos ANSI y el regex casa. Pero también casa con "10 errors" (contiene "0 error") y con "0 files" (el checker sale 0 con una ruta inexistente). Corrección: `EXPECT: /[1-9]\d* files\S*\s+\S*\b0 error\S*\s+\S*\b0 warn/`, agregar `resources/views` (el brief prohíbe `style=""` en vistas) y verificar que las carpetas existan.
- **P8**: VERIFICADO ejecutándolo hoy: la primera mitad (`/Laravel[^\n]*laravel\.helx\.page/i`) ya es verdadera con el `CLAUDE.md` actual, porque la sección "Proyecto Laravel UNI" tiene "Laravel 12 ... `https://laravel.helx.page`" en una línea; no prueba el registro en "Apps desplegadas". Solo la segunda mitad (`8000`) es informativa. Además es mantenimiento de infraestructura personal, no un entregable del curso: pasar a lista de pendientes del dueño, no al ledger.
- **P7**: `find ... -name '*.dump' -mmin -1560` acepta un archivo vacío y la extensión `.dump` no está definida en ningún spec (02 §4 solo dice `pg_dump -Fc`). Corrección: definir `~/backups/laravel/laravel-YYYYmmdd.dump` y la imagen en `laravel-uploads-YYYYmmdd.tar.gz` en 02 §4, y `find ... -size +10k` (más una comprobación del `.tar.gz`). La cita con comillas es válida en `cmd.exe` (VERIFICADO el razonamiento: `|` y `&&` van dentro de comillas dobles y `~` lo expande el shell remoto); falta que la llave SSH esté en ese PC.
- **G0**: `LINT OK` casa por subcadena (VERIFICADO: hoy imprime `LINT OK (12 warning(s))`, salida 0). Bien; pero 8 de 33 gates son manuales (S5, S7, F13, F14, P6, E1, E2, E3) y los 12 avisos del lint salen de ellos (8 por "manual-gate", 4 por "unmeasured-number").
- **S4**: el `CHECK` depende de `scripts/gates/smoke.mjs`, que no existe y que decide por sí mismo si imprime "smoke passed". Especificar en 06 qué verifica: 200 en `/up` y `/login`, las cuatro cabeceras con sus valores exactos de 05 §5, y `Set-Cookie` con `Secure; HttpOnly; SameSite=Lax` (02 §3 lo exige y nada lo observa), y que salga con código distinto de 0 en cualquier falla.
- **S6**: `gitleaks` no está en la lista de entorno de 02 §7 y la regla "solo php, node, npm, composer, ssh y gitleaks en el PATH" lo da por instalado. NO VERIFICADO que `gitleaks git` exista en la versión que instalen (el subcomando es de la 8.19 en adelante, de memoria).
- **"una ruta de tests inexistente sale con error"** (regla de 06): NO VERIFICADO el código de salida de PHPUnit 11; no importa, porque sin la línea `Tests:` el regex no casa.

### M10. Glosario contra specs
- "Ficha" en `CONTEXT.md` es el PDF; en 03 §2 ("Ficha de artista `/artists/{artist}`") y 04 §7 es la pantalla `show`. Dos conceptos con una palabra. Corrección: la pantalla `show` se llama "detalle del artista"; "ficha" queda para el PDF.
- `CONTEXT.md` prohíbe "usuario de GoRest"; 01 H14, 03 §1 y 06 F10 lo usan en el texto. Elegir uno ("usuario externo") y aplicarlo en 01, 03 y 04.
- `CONTEXT.md` pide evitar "informe": la ruta es `/reports/artists/{artist}` (03 §1, 05 §4). Mover a `/artists/{artist}/ficha.pdf` o aceptar la excepción por escrito en el glosario.
- 04 §7 landing: "Tu colección de discos, en orden." usa "colección" (permitido solo como informal) y "discos" (el glosario lo lista como a evitar para Álbum).

### M11. Calendario: el cambio de host cae dentro de la ventana congelada y no tiene gates ni ensayo
- **Dónde**: 02 §5 (VM Intel vence hacia 2026-11-20); 06 S7 (solo "el host existirá"); 06 Fase P congelada el 2026-11-14; ensayo E2 el 2026-11-18.
- **Qué está mal**: si la migración a la A1 o al plan B ocurre después del 2026-11-14, rompe la congelación y los gates P1 a P7 (que corrieron en el host viejo) pierden valor; si ocurre después del ensayo del 2026-11-18, la demo corre en un host que nadie ensayó. Cambia la IP (hay que mover los registros A y el `HostName` del alias SSH, según `CLAUDE.md`), y nadie ha restaurado nunca un `pg_dump` de esta app (M6.4).
- **Corrección**: fijar en 02 §5 "migración de host: a más tardar 2026-11-12; ensayo E2 y gates P1 a P7 se repiten sobre el host nuevo". Agregar a 06:
  ```text
  - [ ] P10: un respaldo de producción se restaura en una base vacía y la app arranca con sus imágenes (evidencia: comando de restauración, conteo de artistas y álbumes antes y después, una portada visible)
    EVIDENCE: pending
  ```
  y bajar S7 a un `EVIDENCE` con fecha límite 2026-10-30 y decisión escrita (A1, PAYG o plan B).

### M12. Huecos y errores menores de contrato en 05 §3 y §4
- Falta la validación del registro (`CreateNewUser`: nombre, correo, contraseña con `confirmed`), del reset (`ResetUserPassword`) y de `POST /api/v1/tokens` (`email`, `password`, `device_name`). §3 solo lista perfil, contraseña de perfil, token de UI y GoRest. Las reglas del registro y las de `current_password` no deben divergir.
- El ejemplo JSON `"created_at": "2026-10-30T15:04:05Z"` no es lo que serializa Laravel: `Carbon::jsonSerialize` da `2026-10-30T15:04:05.000000Z` (con microsegundos; VERIFICADO de memoria del comportamiento de Laravel 7+, NO VERIFICADO en código hoy). Un test que copie el ejemplo falla. Corrección: cambiar el ejemplo o usar `->toIso8601ZuluString()` en el recurso y decirlo.
- `PUT /api/v1/artists/{artist}` "reemplazo completo": con `$artist->update($request->validated())` los campos nulables omitidos (`country`, `bio`) no se borran. Decidir: `PUT` los pone a `null` si faltan (usar `$request->safe()->merge([...])` con nulos explícitos) y escribirlo en F9.
- 05 §3 hace `now()->year + 1` en la regla `between`; con `APP_TIMEZONE=UTC` es correcto, nada que hacer; solo dejarlo anotado si alguien cambia la zona.

### M13. "Usuario borrado manualmente en la BD: sus tokens caen en cascada" es falso, e I5 es código sin llamador
- **Dónde**: 03 §4 (último punto); 05 §2 I5 ("borrado en el modelo `User::deleting`").
- **Qué está mal**: `personal_access_tokens` usa `morphs('tokenable')`, sin FK. Un `DELETE FROM users` en `psql` no dispara eventos de Eloquent: los tokens quedan huérfanos (inofensivos: `Sanctum\Guard` descarta un token cuyo `tokenable` es `null`, VERIFICADO en `Guard.php`) y las filas de `sessions` tampoco tienen FK. Y la app no ofrece borrar la cuenta (ni 01 ni 03), así que `User::deleting` no tiene llamador: es código sin uso.
- **Evidencia**: VERIFICADO `sanctum/4.x` migración (`$table->morphs('tokenable')`) y `Guard.php:58-66`.
- **Corrección**: 03 §4: borrar la frase de los tokens ("sus artistas y álbumes caen en cascada; los tokens huérfanos no autentican"). 05 I5: reducir a "artistas y álbumes en cascada por FK" y quitar `User::deleting`.

---

## BAJO

1. **B1** `stateful => []` (05 §1) no hace lo que dice: solo afecta al middleware `EnsureFrontendRequestsAreStateful`, que esta app no usa. Lo que impide la cookie en la API es que el grupo `api` no tiene `StartSession`. Pero `Sanctum\Guard` consulta primero el guard `web` (VERIFICADO `Guard.php:36-42`): en tests, `actingAs($user)` autentica también la API. Los tests de F9 deben usar tokens reales (`createToken` + `withToken`) y no `actingAs`/`Sanctum::actingAs`, o "401 sin token" y la expiración de 30 días no se prueban.
2. **B2** `upload_max_filesize=4M` con `post_max_size=8M` (02 §4): un archivo de 4 a 8 MB da el error genérico de subida fallida, no el mensaje de 2 MB. Poner `upload_max_filesize=7M`.
3. **B3** 03 §2 lista GoRest sin paginar; `GET /public/v2/users` devuelve 10 por defecto. VERIFICADO hoy: el filtro por subcadena funciona (`?email=kozey.test` devolvió los usuarios con ese dominio y `?email=discoteca.test` devolvió `[]`). Después de muchas demos del equipo, los más viejos quedan fuera de la primera página: agregar `per_page=100` o aceptar el techo.
4. **B4** 02 §3 lista tres cabeceras; 05 §5 y S4 exigen cinco (`X-Frame-Options`, HSTS). Alinear 02 §3 con 05 §5.
5. **B5** S3 dice "sin enumerar correos", pero H2 exige mostrar el error si el correo ya existe: el registro sí lo revela. Reescribir el título: "login y olvidé sin enumerar".
6. **B6** `composer audit` (P3): NO VERIFICADO si la versión de Composer instalada falla con paquetes abandonados; si sí, el `EXPECT` no casa aunque no haya avisos de seguridad.
7. **B7** E1 habla de "los 17 ítems de la rúbrica"; ningún documento los enumera (REVIEW_00-04 §3 lista 22 filas). Agregar la lista numerada al principio de `docs/GUION.md` o al PRD §4.
8. **B8** Gates sin responsable: S1 a S7 ("C con A" en el encabezado), F13, F14, P1 a P8 y E2. La regla "nadie marca un gate de otro" no se puede aplicar.
9. **B9** `route:cache` (dentro de `optimize`) falla si alguna ruta es un closure; el spec dice controladores, pero la landing `Route::get('/', fn ...)` es el candidato típico: usar `Route::view` o un controlador.

---

## Sobrediseño que todavía queda para 28 días y 3 personas

- **ULID + `foreignUlid` + `HasUlids` + regla `ulid`** (05 §1, fila 14 de §5): con binding por usuario (A3) y 404 en lo ajeno, un id secuencial no filtra nada. Cuesta tipos `char(26)`, FKs de texto y validación extra. Es consistente y funciona (VERIFICADO: `foreignUlid` y `HasUlids` componen bien con `Rule::exists/unique` y con Sanctum, cuyo morph apunta a `users.id` bigint), así que cámbienlo solo si algo se complica.
- **Rol `app` sin superusuario** (fila 30): útil en producción real, pero tiene tres costos concretos (M6). Si el tiempo aprieta, es lo primero que se sacrifica; anotar el techo en 05 con un comentario `ponytail:`.
- **P3, P4, P5, P8 y S6 como gates** de un trabajo universitario: P5 y P8 no están en la rúbrica; P8 es infraestructura personal.
- **I8 (revocar sesiones y tokens) e I9 (15 minutos)**: bien defendibles, pero son código y tests extra que la rúbrica no pide; si hay que recortar, I9 se baja al default de 60 y se borra (también resuelve A5).
- **Matriz de 31 vulnerabilidades de `/backend-hardening`** en 05 §5: el documento gasta 25 filas; la mitad son "no aplica". Basta con las 12 que sí aplican.
- **Dos vías de token** (UI y `POST /api/v1/tokens`) más el recuadro técnico de GoRest: cada una necesita tests y defensa oral; la UI de tokens ya basta para la demo y el endpoint cubre la parte "consumible desde fuera". Mantener las dos es defendible; si falta tiempo, el endpoint de login por API es lo que sobra.
- **Sección CORS** en 05 §4: el `config/cors.php` por defecto del framework ya cubre `api/*`; la rúbrica no necesita navegador de otro origen.

---

## Tabla de cobertura de GATES

Leyenda: OK = hay un gate que lo observa; PARCIAL = lo observa a medias (se dice qué falta); HUECO = ninguna compuerta.

### Historias del PRD

| Historia | Gate | Estado |
| :--- | :--- | :--- |
| H1 Landing | ninguno | HUECO |
| H2 Registro | S3 | PARCIAL (falta correo duplicado con mayúsculas, A1) |
| H3 Login/logout | S3 | PARCIAL (faltan throttle 5/min y `Cache-Control: no-store`) |
| H4 Olvidé | S3, S5 | PARCIAL (faltan vencimiento, un solo uso, throttle 6/min, idioma del correo: A5, M2, M3) |
| H5 Rutas protegidas | F1 | OK (con la reserva de A3) |
| H6 Dashboard | F2 | OK |
| H7 CRUD artistas | F3, F14 | OK |
| H8 CRUD álbumes | F4 | OK |
| H9 DataTables | F5, F14 | PARCIAL (F14 no verifica el español de la tabla y tiene la cifra imposible, A6) |
| H10 Perfil | F6 | PARCIAL (sesiones: M4; mezclado con tokens de UI) |
| H11 Gráfico | F7 | PARCIAL (no se dibuja en navegador, A7.1) |
| H12 PDF | F8, P6 | PARCIAL (WebP y tamaño, M7) |
| H13 API propia | F9, F6 | PARCIAL (no se ejerce en producción desde fuera, A7.2; F9 con `getJson`, A2) |
| H14 GoRest | F10, F13 | OK |
| H15 Publicación | S4 | PARCIAL (falta GitHub y README) |

### Invariantes del 05

| Invariante | Gate | Estado |
| :--- | :--- | :--- |
| I1 FKs `NOT NULL` | ninguno directo | HUECO (indirecto por F3/F4) |
| I2 Lo ajeno da 404 | F1 | OK (más A3) |
| I3 `artist_id` ajeno | F4 | OK |
| I4 Cascada + imágenes tras commit | F3 | PARCIAL (no dice que se borren las portadas de los álbumes del artista) |
| I5 Cascada de tokens | ninguno | HUECO (y es código sin llamador, M13) |
| I6 Lista de géneros | F4 | PARCIAL (el género del artista no figura en F3) |
| I7 Nombre aleatorio | ninguno | HUECO (M5) |
| I8 Sesiones y tokens | F6 | PARCIAL (M4) |
| I9 Reset a 15 min y un solo uso | ninguno | HUECO (A5) |

### Rúbrica

| Ítem | Gate | Estado |
| :--- | :--- | :--- |
| Laravel 12 | S1 | OK |
| Equipo de 3 | ninguno | HUECO (`git shortlog -sn` con 3 autores, más S8) |
| Vistas que heredan maestras | ninguno | HUECO |
| BD PostgreSQL | S2 | OK |
| Landing | ninguno | HUECO |
| Registro y login propios | S3 | OK |
| Rutas protegidas | F1 | OK |
| BD por migraciones | ninguno directo | HUECO (`migrate:fresh --seed` sin gate; `RefreshDatabase` cubre migraciones, no el seeder) |
| Modelos y controladores coherentes | n/a | no observable por tests |
| Restaurar contraseña | S3, S5 | PARCIAL |
| Dashboard con usuario activo | F2 | OK |
| Menú con logout | F2, S3 | PARCIAL (`no-store`) |
| DataTables en dos tablas | F5, F14 | PARCIAL |
| Editar perfil | F6 | OK |
| Al menos un chart | F7 | PARCIAL (A7.1) |
| CRUD de dos tablas | F3, F4 | OK |
| API consumible desde fuera | F9 | PARCIAL (A7.2) |
| Descargar PDF | F8, P6 | OK |
| Subir imágenes | F3, F4, F6, P6 | OK |
| REST con GET/POST/PUT/DELETE | F10, F13 | OK |
| Tokens, cabeceras y autorización | F9, F10 (`Http::assertSent`) | OK |
| Publicado en internet | S4, S5, P6 | OK |
| Copia en GitHub | ninguno | HUECO |

### 02 §6 "Configuración Obligatoria" (el TRD afirma "Cada línea tiene su test o compuerta")

| Línea | Gate | Estado |
| :--- | :--- | :--- |
| `fortify.home = /dashboard` | S3 (implícito) | PARCIAL: S3 no dice que el login aterrice en `/dashboard` |
| Features de Fortify sin 2FA ni passkeys | ninguno | HUECO |
| `lowercase_usernames` (A1) | ninguno | HUECO |
| `filesystems.public.throw = true` | ninguno | HUECO |
| Idioma (`lang/es`) | F11 | PARCIAL (falta `es.json`, M3) |
| `phpunit.xml` en pgsql | S2 | OK |
| `.env.example` (pgsql, sync, database, `GOREST_TOKEN`) | ninguno | HUECO |
| Vistas de error 403/404/413/419/500 | F12 (solo "sin trazas") | HUECO para las vistas propias |
| `Accept` JSON forzado en la API (A2) | ninguno | HUECO |
| `uploads.ini`, `storage:link`, `chown`, `tmpfs`, healthcheck | S4, P6 | PARCIAL (el `tmpfs` rompe las vistas, A4) |
| Cookies `Secure; HttpOnly; SameSite=Lax` | ninguno | HUECO (S4 no las mira) |

---

## Correcciones de GATES (texto listo para pegar)

```text
- [ ] S8: los tres integrantes corren la suite en su propia máquina contra su PostgreSQL local (evidencia: la línea "Tests: N passed" de cada uno, con fecha, hasta 2026-10-28)
  EVIDENCE: pending

- [ ] S9: el repositorio en GitHub tiene README con instrucciones de instalación y al menos 3 autores distintos entre sus commits
  CHECK: git shortlog -sn --all
  EXPECT: /^\s*\d+\s+\S.*\r?\n\s*\d+\s+\S.*\r?\n\s*\d+\s+\S/m
  EVIDENCE: pending

- [ ] F15: el seeder de demostración es idempotente y crea el usuario demo con sus 5 artistas y 12 álbumes
  CHECK: php artisan test tests/Feature/DemoSeederTest.php
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] F16: en producción la API responde JSON sin Accept explícito y con token real (evidencia: salida de curl de POST /api/v1/tokens, GET /api/v1/artists con y sin Bearer contra https://laravel.helx.page, y la colección de Postman exportada en docs/)
  EVIDENCE: pending

- [ ] F17: en producción el gráfico se dibuja y la consola del navegador no tiene violaciones de CSP en /stats, /artists y /albums (evidencia: captura de /stats con el gráfico y de la consola vacía)
  EVIDENCE: pending

- [ ] F18: login y olvidé tienen límite de intentos y el logout no deja páginas en caché
  CHECK: php artisan test tests/Feature/ThrottleAndCacheTest.php
  EXPECT: /Tests:\s+(?:[4-9]|\d{2,}) passed/
  EVIDENCE: pending

- [ ] P9: la app corre en producción con un rol de base de datos que no es superusuario
  CHECK: ssh -o BatchMode=yes oracle "docker exec laravel-db psql -U app -d laravel -tAc 'select rolsuper from pg_roles where rolname=current_user'"
  EXPECT: /^f\s*$/m
  EVIDENCE: pending

- [ ] P10: un respaldo de producción se restaura en una base vacía y la app arranca con sus imágenes (evidencia: comando de restauración, conteo de artistas y álbumes antes y después, una portada visible)
  EVIDENCE: pending
```

Notas de uso: ajustar nombres de contenedor en P9. El `EXPECT` de S9 asume la salida de `git shortlog -sn` (una línea por autor); si el equipo usa alias de correo distintos, `.mailmap`. `ThrottleAndCacheTest` agrupa: 6 intentos de login fallidos dan 429, 7 POST a `/forgot-password` dan 429 y `GET /login` repetido no, `Cache-Control` con `no-store` en `/dashboard`, y `logout` seguido de `GET /dashboard` redirige al login.

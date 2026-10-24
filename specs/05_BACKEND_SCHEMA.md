---
document: 05_BACKEND_SCHEMA
stage: 5
status: APPROVED
version: 1.1.0
last_updated: 2026-10-24
approved_by: human
depends_on: [02_TRD, 03_APP_FLOW]
---

# 05. Backend Schema y Contratos

> v1.1.0 (2026-10-24): corregido según `REVIEW_05-06.md` (A1, A2, A3, A6, M1, M4, M5, M6, M7, M8, M12, M13, B1, sobrediseño de §5).

Documento corto por decisión del TRD §8.4: las migraciones son la fuente de verdad; aquí van las columnas, las invariantes, la validación y los contratos que las migraciones y los tests tienen que respetar. Glosario del dominio en `CONTEXT.md` (raíz del repo).

## 1. Esquema (PostgreSQL 17)

Tablas propias: `users`, `artists`, `albums`. De Laravel y Sanctum, sin cambios: `password_reset_tokens`, `sessions`, `cache`, `jobs`, `personal_access_tokens`. Todas las marcas de tiempo son `timestamp` en UTC (`APP_TIMEZONE=UTC`); la zona `America/Bogota` se aplica solo al mostrar.

**Regla de migraciones**: ninguna migración que ya corrió en producción se edita; un cambio de esquema va en una migración nueva. Hasta el 2026-11-14 se permite `migrate:fresh` en producción; después, nunca.

### `users` (migración del esqueleto + `add_photo_path_to_users_table`)

| Columna | Tipo | Reglas |
| :--- | :--- | :--- |
| `id` | `bigint` identity | PK. Nunca aparece en una URL. |
| `name` | `varchar(120)` | `NOT NULL` |
| `email` | `varchar(255)` | `NOT NULL`, `UNIQUE`. Se guarda en minúsculas: `config/fortify.php` → `'lowercase_usernames' => true` (los controladores de Fortify hacen `Str::lower` antes de validar, así que el `unique` compara el valor ya normalizado). `POST /api/v1/tokens` aplica `Str::lower($email)` a mano. |
| `password` | `varchar(255)` | Hash bcrypt (default de Laravel 12, `BCRYPT_ROUNDS=12`). |
| `photo_path` | `varchar(255)` | Nullable. Ruta relativa en el disco `public` (`avatars/<aleatorio>.<ext>`). Va en la migración nueva `add_photo_path_to_users_table`, no editando la del esqueleto, que ya corre en producción desde la fase S. |
| `remember_token`, `created_at`, `updated_at` | | Del esqueleto. |

### `artists`

| Columna | Tipo | Reglas |
| :--- | :--- | :--- |
| `id` | `char(26)` ULID | PK (`HasUlids`). Va en URLs y en la API: no es secuencial ni adivinable. |
| `user_id` | `bigint` | `NOT NULL`, FK a `users.id` `ON DELETE CASCADE`. |
| `name` | `varchar(120)` | `NOT NULL`. `UNIQUE (user_id, name)`: un usuario no repite artista. Distingue mayúsculas (techo conocido; ver §5). |
| `country` | `varchar(80)` | Nullable, texto libre. |
| `genre` | `varchar(30)` | `NOT NULL`, valor de la lista cerrada de géneros (§2). |
| `bio` | `text` | Nullable; la validación limita a 1000 caracteres. |
| `photo_path` | `varchar(255)` | Nullable (`artists/<aleatorio>.<ext>`). |
| `created_at`, `updated_at` | `timestamp` | |

Índices: el `UNIQUE (user_id, name)` cubre las consultas por `user_id` (columna líder), así que no hace falta otro índice.

### `albums`

| Columna | Tipo | Reglas |
| :--- | :--- | :--- |
| `id` | `char(26)` ULID | PK. |
| `artist_id` | `char(26)` | `NOT NULL`, FK a `artists.id` `ON DELETE CASCADE`. Índice. |
| `title` | `varchar(160)` | `NOT NULL` |
| `year` | `smallint` | `NOT NULL`, `CHECK (year BETWEEN 1900 AND 2100)`. La validación además exige `<= año actual + 1`. |
| `genre` | `varchar(30)` | `NOT NULL`, lista cerrada (§2). Índice `(genre)` no hace falta: el gráfico agrupa sobre los álbumes de un usuario (decenas de filas). |
| `cover_path` | `varchar(255)` | Nullable (`covers/<aleatorio>.<ext>`). |
| `created_at`, `updated_at` | `timestamp` | |

**Desviación del TRD §2**: `albums` **no** lleva `user_id`. El dueño de un álbum es el dueño de su artista; `User::albums()` es un `hasManyThrough(Album::class, Artist::class)`. Así no existe una columna redundante que pueda contradecir a la del artista, y no hace falta una FK compuesta para impedirlo. El TRD §2 se corrige al aprobar este documento.

### `personal_access_tokens` (Sanctum)

Sin cambios de esquema. `config/sanctum.php`: `expiration => 43200` (30 días; el default `null` nunca vence). Un token por creación, sin *abilities* finas (`['*']`). La API no acepta la cookie de sesión porque el grupo `api` no tiene `StartSession`; `stateful` queda con su default (solo lo lee `EnsureFrontendRequestsAreStateful`, que esta app no usa). Ojo en los tests: `Sanctum\Guard` consulta primero el guard `web`, así que `actingAs($user)` también autentica la API. Los tests de la API usan tokens reales (`createToken` + `withToken`), nunca `actingAs` ni `Sanctum::actingAs`; si no, "401 sin token" y el vencimiento a 30 días no se prueban.

### Usuario de base de datos en producción

La imagen `postgres` crea `POSTGRES_USER` como **superusuario**. En producción la app no usa ese rol: se conecta con el rol `app`, sin `SUPERUSER`, `CREATEDB` ni `CREATEROLE`, dueño de la base `laravel`. El superusuario queda solo para `pg_dump`, `pg_restore` y mantenimiento. En local se usa el rol `laravel` del contenedor de desarrollo.

- El repo lleva `docker/postgres/10-app.sh`, montado de solo lectura en `/docker-entrypoint-initdb.d/10-app.sh` del servicio `db` (02 §4), que recibe `APP_DB_PASSWORD` desde el `.env` del compose:
  ```sh
  #!/bin/sh
  set -eu
  psql -v ON_ERROR_STOP=1 -v pw="$APP_DB_PASSWORD" -U "$POSTGRES_USER" -d "$POSTGRES_DB" <<'SQL'
  CREATE ROLE app LOGIN PASSWORD :'pw';
  ALTER DATABASE laravel OWNER TO app;
  SQL
  ```
  Tiene que ser `.sh`: un `.sql` no puede leer la contraseña del entorno. Desde PostgreSQL 15 el esquema `public` pertenece a `pg_database_owner`, así que ser dueño de la base basta para que `migrate` cree tablas; un `GRANT ALL ON DATABASE` a secas no da `CREATE` en `public` y `migrate` falla con "permission denied for schema public".
- Los scripts de `initdb.d` solo corren con el volumen `laravel-pgdata` vacío. Si el volumen ya existe (por una prueba previa), el script se omite en silencio: se crea el rol a mano con el mismo SQL (`docker exec -it laravel-db psql -U <superusuario> -d laravel`). P9 detecta si la app quedó corriendo como superusuario.
- Restaurar un respaldo (migración de host, P10): crear antes el rol `app` y después `pg_restore --no-owner --role=app -U <superusuario> -d laravel <archivo>.dump`. Sin `--no-owner --role=app` los objetos quedan a nombre del superusuario y la app no los puede leer.
- **Techo conocido (`ponytail`)**: si el tiempo aprieta, es lo primero que se sacrifica. La app pasa a usar el superusuario, P9 se abandona con `ABANDON:` y la defensa queda en que el puerto de la base no se publica.

## 2. Reglas de Dominio (invariantes)

| # | Invariante | Dónde se garantiza |
| :--- | :--- | :--- |
| I1 | Un artista pertenece a un solo usuario; un álbum, a un solo artista. | FKs `NOT NULL`. |
| I2 | Un usuario solo ve y modifica lo suyo; lo ajeno responde 404, con el mismo cuerpo que lo inexistente. | Barrera principal: `Route::bind` de `{artist}` y `{album}` con alcance de usuario (código abajo). Ajeno e inexistente lanzan el mismo `ModelNotFoundException` antes del FormRequest y de la Policy, así que un `PUT` ajeno con datos inválidos da 404 y no 422. Segunda línea: Policies con `Response::denyAsNotFound()`. |
| I3 | Un álbum no se puede asignar a un artista ajeno. | Validación `Rule::exists('artists', 'id')->where('user_id', $user->id)`. |
| I4 | Borrar un artista borra sus álbumes y, después del commit, su foto y las portadas de sus álbumes. | `ON DELETE CASCADE` + `DB::afterCommit` (el modelo junta las rutas antes de borrar). |
| I5 | Borrar un usuario borra sus artistas y álbumes. | `ON DELETE CASCADE` en `artists.user_id` y `albums.artist_id`. La app no ofrece borrar cuentas. Si alguien borra un usuario en `psql`, sus filas de `personal_access_tokens` (morph, sin FK) y de `sessions` quedan huérfanas y no autentican (`Sanctum\Guard` descarta un token sin `tokenable`). |
| I6 | El género sale de una lista cerrada, para que el gráfico no parta "Rock" y "rock". | `config/discoteca.php` → `genres`: Rock, Pop, Hip hop, Electrónica, Jazz, Clásica, Salsa, Reggaetón, Vallenato, Otro. Validación `Rule::in` en artistas y álbumes. |
| I7 | Ninguna imagen se guarda con el nombre del cliente. | `store('artists', 'public')` genera un nombre aleatorio de 40 caracteres y deriva la extensión del MIME; nunca `storeAs` con el nombre del cliente. `throw => true` en el disco. |
| I8 | Cambiar o restablecer la contraseña cierra las otras sesiones, invalida el "Recordarme" y revoca los tokens de API. | Acciones `UpdateUserPassword` y `ResetUserPassword` de Fortify: borran las filas de `sessions` del usuario salvo la actual, ejecutan `$user->setRememberToken(Str::random(60))` y `$user->tokens()->delete()`. Los tests corren con `SESSION_DRIVER=array`: F20 inserta filas en `sessions` y afirma que la acción las borra; la prueba con dos navegadores va en P6. |
| I9 | El enlace de restablecimiento vence a los 15 minutos y sirve una sola vez. | `config/auth.php` → `passwords.users.expire = 15` (el default es 60); el broker borra el token al usarlo. El texto del correo toma el valor de la configuración. |

```php
// AppServiceProvider::boot(). 'auth' corre antes de SubstituteBindings por la prioridad de middleware.
Route::bind('artist', fn (string $v) => request()->user()->artists()->findOrFail($v));
Route::bind('album',  fn (string $v) => request()->user()->albums()->findOrFail($v));
```

Consultas sobre `$user->albums()`: el `hasManyThrough` hace JOIN con `artists`, que comparte `id`, `genre`, `created_at` y `updated_at`. Toda consulta agregada, filtrada u ordenada califica la columna (`albums.genre`, `albums.created_at`); si no, PostgreSQL responde `column reference "genre" is ambiguous`. Para `/stats`: `Album::whereIn('artist_id', $user->artists()->select('id'))->selectRaw('genre, count(*) as total')->groupBy('genre')`.

## 3. Validación (FormRequests)

Mensajes en español por `lang/es` y `lang/es.json`. Todas las reglas corren en el servidor; el HTML puede repetirlas con `required`/`maxlength` solo como ayuda.

```php
// Imagen: compartida por artista, álbum y perfil.
// mimes/types se decide por el contenido (fileinfo), no por la extensión que manda el cliente.
// 2000 px bastan para la ficha PDF y la grilla, y acotan la memoria de dompdf (GD decodifica la imagen entera).
$imagen = ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)
    ->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000))];

// Contraseña nueva: la misma regla en registro, reset y perfil.
$password = ['required', 'string', Password::min(8), 'confirmed'];

// Registro (acción CreateNewUser de Fortify). El correo llega en minúsculas (lowercase_usernames).
'name'     => ['required', 'string', 'max:120'],
'email'    => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users')],
'password' => $password,

// Reset (acción ResetUserPassword). Fortify valida token y email antes.
'password' => $password,

// StoreArtistRequest / UpdateArtistRequest (web)
'name'    => ['required', 'string', 'max:120',
              Rule::unique('artists')->where('user_id', $this->user()->id)->ignore($this->route('artist'))],
'country' => ['nullable', 'string', 'max:80'],
'genre'   => ['required', Rule::in(config('discoteca.genres'))],
'bio'     => ['nullable', 'string', 'max:1000'],
'photo'   => $imagen,

// StoreAlbumRequest / UpdateAlbumRequest
'artist_id' => ['required', 'ulid', Rule::exists('artists', 'id')->where('user_id', $this->user()->id)],
'title'     => ['required', 'string', 'max:160'],
'year'      => ['required', 'integer', 'between:1900,' . (now()->year + 1)],
'genre'     => ['required', Rule::in(config('discoteca.genres'))],
'cover'     => $imagen,

// Perfil (acción UpdateUserProfileInformation de Fortify, error bag 'updateProfileInformation')
'name'  => ['required', 'string', 'max:120'],
'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users')->ignore($user->id)],
'photo' => $imagen,

// Contraseña (acción UpdateUserPassword, error bag 'updatePassword')
'current_password' => ['required', 'string', 'current_password:web'],
'password'         => $password,

// Token desde la UI (/profile/tokens)
'token_name' => ['required', 'string', 'max:60'],

// POST /api/v1/tokens. El controlador aplica Str::lower($email) antes de buscar al usuario.
'email'       => ['required', 'string', 'email:rfc'],
'password'    => ['required', 'string'],
'device_name' => ['required', 'string', 'max:60'],

// GoRest (formularios de /external/users); PUT exige los 4 campos
'name'   => ['required', 'string', 'max:100'],
'email'  => ['required', 'email:rfc', 'max:150', 'ends_with:@discoteca.test'],
'gender' => ['required', Rule::in(['male', 'female'])],
'status' => ['required', Rule::in(['active', 'inactive'])],
```

`now()->year + 1` usa `APP_TIMEZONE=UTC`; si alguien cambia la zona, se revisa la regla.

API: las mismas reglas de artista sin `photo` (la API no maneja archivos; devuelve `photo_url`). `PUT` exige el objeto completo (`name` y `genre` obligatorios), coherente con su semántica de reemplazo: los campos nulables que falten (`country`, `bio`) se guardan como `null` (`$artist->update(['country' => null, 'bio' => null, ...$request->validated()])`). F9 lo prueba.

## 4. Contratos de la API (`/api/v1`)

La API responde JSON aunque la petición no mande `Accept: application/json` (Postman manda `*/*`): `bootstrap/app.php` lo fuerza para `api/*` (02 §6), y eso cubre el 401, el 404 y el 422. Las rutas protegidas exigen `Authorization: Bearer <token>`. Fechas ISO-8601 en UTC con el formato por defecto de Laravel (microsegundos incluidos).

**Recurso `Artist`**:
```json
{
  "id": "01JBX3K8Q4V9W2M6N7P5R1S0TA",
  "name": "Soda Stereo",
  "country": "Argentina",
  "genre": "Rock",
  "bio": null,
  "photo_url": null,
  "albums_count": 3,
  "created_at": "2026-10-30T15:04:05.000000Z",
  "updated_at": "2026-10-30T15:04:05.000000Z"
}
```

| Método y ruta | Cuerpo | Éxito | Errores |
| :--- | :--- | :--- | :--- |
| `POST /tokens` | `{"email", "password", "device_name"}` | `201 {"token": "1|..."}` | `422` mensaje genérico ("Las credenciales no son válidas"); `429` |
| `GET /artists?page=N` | | `200 {"data": [Artist], "links": {...}, "meta": {...}}` (15 por página, paginador de Laravel) | `401` |
| `POST /artists` | `{"name", "country", "genre", "bio"}` | `201 {"data": Artist}` + cabecera `Location` | `401`, `422 {"message", "errors": {campo: [..]}}` |
| `GET /artists/{artist}` | | `200 {"data": Artist}` | `401`, `404` (inexistente o ajeno) |
| `PUT /artists/{artist}` | objeto completo | `200 {"data": Artist}` | `401`, `404`, `422` |
| `DELETE /artists/{artist}` | | `204` sin cuerpo | `401`, `404` |

Formato de error: el de Laravel (`{"message": "..."}` y, en validación, `errors` por campo), traducido (`"Unauthenticated."` en `lang/es.json`). En producción nunca hay traza ni ruta interna (`APP_DEBUG=false`). Un `404` en `api/*` responde `{"message": "No encontrado"}`, idéntico para el inexistente y el ajeno (binding de §2 más el `render` de `NotFoundHttpException` en `bootstrap/app.php`), sin el nombre de la clase.

**CORS**: `config/cors.php` deja `paths => ['api/*']`, `allowed_origins => ['*']` y `supports_credentials => false`. Es seguro porque la API no usa cookies: el token viaja en una cabecera que el navegador no adjunta solo.

**PDF**: `GET /reports/artists/{artist}` (web, sesión) → `200 application/pdf`, `Content-Disposition: attachment; filename="artista-<slug>.pdf"`. La ruta usa `reports` por convención técnica; en el producto el documento es la ficha (`CONTEXT.md`). La foto se pasa a dompdf como ruta de disco (`Storage::disk('public')->path(...)`).

## 5. Seguridad (`/backend-hardening`)

Perfil mixto: las rutas web son **Perfil B** (SSR con cookie de sesión: el riesgo es CSRF y secuestro de sesión) y la API es **Perfil A** (Bearer: el riesgo es la filtración del token). Solo se lista lo que exige código o configuración del equipo.

| # | Vulnerabilidad | Cómo queda cubierta |
| :--- | :--- | :--- |
| 1 | Secretos en git | `.gitignore` con `.env*` salvo `.env.example`; `gitleaks git` antes del primer push (S6). |
| 4, 13 | Permisos solo en la UI / IDOR | Middleware `auth` en todo el grupo; `Route::bind` con alcance de usuario y Policies con `denyAsNotFound` (I2). Tests de acceso cruzado por recurso, web y API (F1, F9). |
| 5, 29 | Fuerza bruta | Login 5/min por correo e IP (Fortify); `POST /forgot-password` 6/min por IP (middleware agregado a la ruta `password.email`, 02 §3); `POST /api/v1/tokens` 5/min. El contador vive en la caché de base de datos, que sobrevive reinicios. F18 y F9. |
| 7 | Validación en el servidor | FormRequests de §3 en cada mutación, web y API. |
| 8 | XSS | Blade escapa con `{{ }}`; prohibido `{!! !!}` con datos del usuario. La bio se muestra con `nl2br(e($bio))`. CSP `script-src 'self'`. |
| 14 | IDs predecibles | ULID en `artists` y `albums`; `users.id` nunca sale en URLs ni en la API. |
| 15, 18 | Cabeceras | Middleware de producción: CSP del TRD, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, `Strict-Transport-Security: max-age=31536000`. El TLD `.page` ya está en la lista de precarga HSTS de los navegadores. F12 y S4. |
| 17 | Fugas de errores | `APP_DEBUG=false`, páginas de error propias, JSON limpio en la API sin nombres de clase (§4). |
| 19 | Subidas | Tipo por contenido, 2 MB, 2000 px, nombre aleatorio y extensión derivada del MIME (`store()`), nunca `storeAs` con el nombre del cliente (I7). FrankenPHP ejecuta cualquier `.php` bajo `public/`, y `public/storage` apunta a las subidas: la defensa es que nunca se escribe un `.php` ahí. F3 sube `evil.php.jpg`. |
| 23, 24 | Sesiones vivas tras cambiar contraseña; enlaces de reset | I8 e I9. |
| 25 | Enumeración de usuarios | Login con mensaje genérico (Fortify); "olvidé" con respuesta idéntica (TRD §3); `POST /api/v1/tokens` con 422 genérico. El registro sí revela que un correo existe (H2 lo exige). |
| 28 | Cuerpos enormes | `upload_max_filesize=7M`, `post_max_size=8M`; vista 413. |
| 30 | Usuario de BD con privilegios | Rol `app` sin superusuario en producción (§1). P9. |

No aplican o los resuelve Laravel sin código propio: 2, 3, 6, 9, 10, 11, 12, 16, 20, 21, 22, 26, 27 y 31 (sin claves de servicio en el cliente ni Supabase/RLS; solo Eloquent con parámetros; bcrypt de Laravel; la web no guarda tokens en el navegador; sin panel de administración; CORS en §4; sin webhooks entrantes; `redirect()->intended()` y CSRF de Laravel; PHP sin pool propio; sin saldos ni IA; la única URL saliente es la fija de GoRest).

**Techo conocido (`ponytail`)**: el `UNIQUE (user_id, name)` distingue mayúsculas: "Queen" y "queen" pueden convivir. Subir a un índice sobre `lower(name)` si molesta en la demo.

## 6. Datos de Demostración (seeder)

- Usuario `demo@discoteca.test`. La contraseña sale de `SEED_DEMO_PASSWORD` del `.env`; `.env.example` trae un valor de desarrollo. Si falta con `APP_ENV=production`, el seeder lanza una excepción en vez de usar un valor por defecto.
- Artistas y álbumes reales (discografías públicas), sin fotos (se muestra la galleta con iniciales; no se siembran imágenes con derechos de autor): 5 artistas y 12 álbumes. Con 10 filas por página, la lista de álbumes muestra 2 páginas (F14).
  - Soda Stereo (Argentina, Rock): Signos 1986, Canción Animal 1990, Sueño Stereo 1995.
  - Shakira (Colombia, Pop): Pies Descalzos 1995, ¿Dónde Están los Ladrones? 1998, Laundry Service 2001.
  - Carlos Vives (Colombia, Vallenato): Clásicos de la Provincia 1993, La Tierra del Olvido 1995.
  - Queen (Reino Unido, Rock): A Night at the Opera 1975, News of the World 1977.
  - Daft Punk (Francia, Electrónica): Discovery 2001, Random Access Memories 2013.
- Un segundo usuario con un artista propio, para que la demo muestre que no se ve lo ajeno.
- Idempotente: `updateOrCreate` por correo en usuarios, por `(user_id, name)` en artistas y por `(artist_id, title)` en álbumes. Correrlo dos veces deja los mismos conteos (el día de la demo se corre en producción con `--force`).
- La factory de tests (`ArtistFactory`, `AlbumFactory`) genera datos aleatorios aparte; los tests de funcionalidad no usan el seeder. `DemoSeederTest` (F15) lo corre dos veces, afirma los conteos y afirma que falla en producción sin `SEED_DEMO_PASSWORD`.

## 7. Supuestos a Validar

1. **Lista cerrada de géneros** (I6) con los 10 de la tabla, pensados para un salón en Colombia. Cambiarla después es editar `config/discoteca.php`, pero los álbumes ya guardados con un género retirado quedarían fuera de la lista.
2. **Reset de 15 minutos y revocación de tokens al cambiar contraseña** (I8, I9): es más estricto que el default de Laravel (60 minutos, nada se revoca). Si el correo de Resend tarda en llegar durante la demo, 15 minutos siguen sobrando.

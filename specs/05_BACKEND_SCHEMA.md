---
document: 05_BACKEND_SCHEMA
stage: 5
status: APPROVED
version: 1.0.0
last_updated: 2026-10-24
approved_by: human
depends_on: [02_TRD, 03_APP_FLOW]
---

# 05. Backend Schema y Contratos

Documento corto por decisión del TRD §8.4: las migraciones son la fuente de verdad; aquí van las columnas, las invariantes, la validación y los contratos que las migraciones y los tests tienen que respetar. Glosario del dominio en `CONTEXT.md` (raíz del repo).

## 1. Esquema (PostgreSQL 17)

Tablas propias: `users`, `artists`, `albums`. De Laravel y Sanctum, sin cambios: `password_reset_tokens`, `sessions`, `cache`, `jobs`, `personal_access_tokens`. Todas las marcas de tiempo son `timestamp` en UTC (`APP_TIMEZONE=UTC`); la zona `America/Bogota` se aplica solo al mostrar.

### `users` (migración del esqueleto + una columna)

| Columna | Tipo | Reglas |
| :--- | :--- | :--- |
| `id` | `bigint` identity | PK. Nunca aparece en una URL. |
| `name` | `varchar(120)` | `NOT NULL` |
| `email` | `varchar(255)` | `NOT NULL`, `UNIQUE`. Se guarda en minúsculas (lo normalizan las acciones de Fortify `CreateNewUser` y `UpdateUserProfileInformation`). |
| `password` | `varchar(255)` | Hash bcrypt (default de Laravel 12, `BCRYPT_ROUNDS=12`). |
| `photo_path` | `varchar(255)` | Nullable. Ruta relativa en el disco `public` (`avatars/<aleatorio>.<ext>`). |
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

Sin cambios de esquema. `config/sanctum.php`: `expiration => 43200` (30 días; el default `null` nunca vence) y `stateful => []` (la API solo acepta Bearer, nunca la cookie de sesión). Un token por creación, sin *abilities* finas (`['*']`).

### Usuario de base de datos en producción

La imagen `postgres` crea `POSTGRES_USER` como **superusuario**. En producción la app no usa ese rol: un script en `/docker-entrypoint-initdb.d/` crea el rol `app` sin `SUPERUSER`, `CREATEDB` ni `CREATEROLE`, dueño de la base `laravel`. El superusuario queda solo para `pg_dump` y mantenimiento. En local se usa el rol `laravel` del contenedor de desarrollo.

## 2. Reglas de Dominio (invariantes)

| # | Invariante | Dónde se garantiza |
| :--- | :--- | :--- |
| I1 | Un artista pertenece a un solo usuario; un álbum, a un solo artista. | FKs `NOT NULL`. |
| I2 | Un usuario solo ve y modifica lo suyo; lo ajeno responde 404. | Consultas desde `$user->artists()` / `$user->albums()` y Policies con `Response::denyAsNotFound()`. |
| I3 | Un álbum no se puede asignar a un artista ajeno. | Validación `Rule::exists('artists', 'id')->where('user_id', $user->id)`. |
| I4 | Borrar un artista borra sus álbumes y, después del commit, sus imágenes. | `ON DELETE CASCADE` + `DB::afterCommit` (el modelo junta las rutas antes de borrar). |
| I5 | Borrar un usuario borra sus artistas, álbumes y tokens. | Cascada en `artists.user_id` y en `personal_access_tokens` (morph, borrado en el modelo `User::deleting`). |
| I6 | El género sale de una lista cerrada, para que el gráfico no parta "Rock" y "rock". | `config/discoteca.php` → `genres`: Rock, Pop, Hip hop, Electrónica, Jazz, Clásica, Salsa, Reggaetón, Vallenato, Otro. Validación `Rule::in`. |
| I7 | Ninguna imagen se guarda con el nombre del cliente. | `store('artists', 'public')` genera el nombre; `throw => true` en el disco. |
| I8 | Cambiar o restablecer la contraseña cierra las otras sesiones y revoca los tokens de API. | Acciones `UpdateUserPassword` y `ResetUserPassword` de Fortify: borran las filas de `sessions` del usuario salvo la actual y `$user->tokens()->delete()`. |
| I9 | El enlace de restablecimiento vence a los 15 minutos y sirve una sola vez. | `config/auth.php` → `passwords.users.expire = 15` (el default es 60); el broker borra el token al usarlo. |

## 3. Validación (FormRequests)

Mensajes en español por `lang/es`. Todas las reglas corren en el servidor; el HTML puede repetirlas con `required`/`maxlength` solo como ayuda.

```php
// Imagen: compartida por artista, álbum y perfil.
// mimes/types se decide por el contenido (fileinfo), no por la extensión que manda el cliente.
$imagen = ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)
    ->dimensions(Rule::dimensions()->maxWidth(4000)->maxHeight(4000))];

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
'password'         => ['required', 'string', Password::min(8), 'confirmed'],

// Token desde la UI (/profile/tokens)
'token_name' => ['required', 'string', 'max:60'],

// GoRest (formularios de /external/users); PUT exige los 4 campos
'name'   => ['required', 'string', 'max:100'],
'email'  => ['required', 'email:rfc', 'max:150', 'ends_with:@discoteca.test'],
'gender' => ['required', Rule::in(['male', 'female'])],
'status' => ['required', Rule::in(['active', 'inactive'])],
```

API: las mismas reglas de artista sin `photo` (la API no maneja archivos; devuelve `photo_url`). `PUT` exige el objeto completo (`name` y `genre` obligatorios), coherente con su semántica de reemplazo.

## 4. Contratos de la API (`/api/v1`)

Cabeceras de toda petición: `Accept: application/json`. Las protegidas, además, `Authorization: Bearer <token>`. Respuestas en JSON, fechas ISO-8601 en UTC.

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
  "created_at": "2026-10-30T15:04:05Z",
  "updated_at": "2026-10-30T15:04:05Z"
}
```

| Método y ruta | Cuerpo | Éxito | Errores |
| :--- | :--- | :--- | :--- |
| `POST /tokens` | `{"email", "password", "device_name"}` | `201 {"token": "1|..."}` | `422` mensaje genérico ("Las credenciales no son válidas"); `429` |
| `GET /artists?page=N` | | `200 {"data": [Artist], "links": {...}, "meta": {...}}` (15 por página, paginador de Laravel) | `401` |
| `POST /artists` | `{"name", "country", "genre", "bio"}` | `201 {"data": Artist}` + cabecera `Location` | `401`, `422 {"message", "errors": {campo: [..]}}` |
| `GET /artists/{id}` | | `200 {"data": Artist}` | `401`, `404` (inexistente o ajeno) |
| `PUT /artists/{id}` | objeto completo | `200 {"data": Artist}` | `401`, `404`, `422` |
| `DELETE /artists/{id}` | | `204` sin cuerpo | `401`, `404` |

Formato de error: el de Laravel (`{"message": "..."}` y, en validación, `errors` por campo), traducido. En producción nunca hay traza ni ruta interna (`APP_DEBUG=false`). Un `404` de modelo dice "No encontrado", sin el nombre de la clase.

**CORS**: `config/cors.php` deja `paths => ['api/*']`, `allowed_origins => ['*']` y `supports_credentials => false`. Es seguro porque la API no usa cookies: el token viaja en una cabecera que el navegador no adjunta solo.

**PDF**: `GET /reports/artists/{artist}` (web, sesión) → `200 application/pdf`, `Content-Disposition: attachment; filename="artista-<slug>.pdf"`. La foto se pasa a dompdf como ruta de disco (`Storage::disk('public')->path(...)`).

## 5. Seguridad (`/backend-hardening`)

Perfil mixto: las rutas web son **Perfil B** (SSR con cookie de sesión: el riesgo es CSRF y secuestro de sesión) y la API es **Perfil A** (Bearer: el riesgo es la filtración del token). Solo se audita lo que escribe el equipo; lo que ya resuelven Laravel o la plataforma no suma puntos.

| # | Vulnerabilidad | Cómo queda cubierta |
| :--- | :--- | :--- |
| 1 | Secretos en git | `.gitignore` con `.env*` salvo `.env.example`; `gitleaks git` antes del primer push (compuerta del GATES). |
| 4, 13 | Permisos solo en la UI / IDOR | Middleware `auth` en todo el grupo; consultas desde la relación del usuario; Policies con `denyAsNotFound`. Tests de acceso cruzado por recurso (web y API). |
| 5, 29 | Fuerza bruta | Login 5/min por correo e IP (Fortify); `/forgot-password` 6/min por IP; `/api/v1/tokens` 5/min. En una sola instancia el contador vive en la caché de base de datos, que sobrevive reinicios. |
| 6 | Inyección SQL | Solo Eloquent y el query builder con parámetros. Prohibido `DB::raw` con datos del usuario. |
| 7 | Validación en el servidor | FormRequests de §3 en cada mutación, web y API. |
| 8 | XSS | Blade escapa con `{{ }}`; prohibido `{!! !!}` con datos del usuario. La bio se muestra con `nl2br(e($bio))`. CSP `script-src 'self'`. |
| 9 | Contraseñas | bcrypt de Laravel. |
| 10 | Tokens en `localStorage` | La web no maneja tokens en el navegador. El token de API se muestra una sola vez para copiarlo; no se guarda en el cliente. |
| 12 | CORS | §4. |
| 14 | IDs predecibles | ULID en `artists` y `albums`; `users.id` nunca sale en URLs ni en la API. |
| 15, 18 | Cabeceras | Middleware de producción: CSP del TRD, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, `Strict-Transport-Security: max-age=31536000`. El TLD `.page` ya está en la lista de precarga HSTS de los navegadores. |
| 17 | Fugas de errores | `APP_DEBUG=false`, páginas de error propias, JSON limpio en la API. |
| 19 | Subidas | §3: tipo por contenido, 2 MB, 4000 px, nombre aleatorio, disco `public` servido como archivos estáticos (FrankenPHP no ejecuta PHP fuera de `public/index.php`). |
| 21 | Redirección abierta | Solo `redirect()->intended()` de Laravel (mismo host) y rutas con nombre. |
| 22 | CSRF | Token CSRF de Laravel en todo formulario; ninguna mutación por GET (logout es POST, borrar es DELETE). |
| 23 | Sesiones vivas tras cambiar contraseña | I8. |
| 24 | Enlaces de reset | I9. |
| 25 | Enumeración de usuarios | Login con mensaje genérico (Fortify); "olvidé" con respuesta idéntica (TRD §3); `POST /api/v1/tokens` con 422 genérico. |
| 28 | Cuerpos enormes | `post_max_size=8M`; vista 413. |
| 30 | Usuario de BD con privilegios | Rol `app` sin superusuario en producción (§1). |
| 31 | SSRF | No aplica: la única URL saliente es la fija de GoRest; ningún usuario aporta URLs. |
| 2, 3, 11, 16, 20, 26, 27 | | No aplican: sin claves de servicio en el cliente, sin Supabase/RLS, sin panel de administración, sin webhooks entrantes, PHP sin pool propio, sin saldos ni precios, sin IA. |

**Techo conocido (`ponytail`)**: el `UNIQUE (user_id, name)` distingue mayúsculas: "Queen" y "queen" pueden convivir. Subir a un índice sobre `lower(name)` si molesta en la demo.

## 6. Datos de Demostración (seeder)

- Usuario `demo@discoteca.test`. La contraseña sale de `SEED_DEMO_PASSWORD` del `.env`; si falta en producción, el seeder falla en vez de usar un valor por defecto.
- Artistas y álbumes reales (discografías públicas), sin fotos (se muestra la galleta con iniciales; no se siembran imágenes con derechos de autor):
  - Soda Stereo (Argentina, Rock): Signos 1986, Canción Animal 1990, Sueño Stereo 1995.
  - Shakira (Colombia, Pop): Pies Descalzos 1995, ¿Dónde Están los Ladrones? 1998, Laundry Service 2001.
  - Carlos Vives (Colombia, Vallenato): Clásicos de la Provincia 1993, La Tierra del Olvido 1995.
  - Queen (Reino Unido, Rock): A Night at the Opera 1975, News of the World 1977.
  - Daft Punk (Francia, Electrónica): Discovery 2001, Random Access Memories 2013.
- Un segundo usuario con un artista propio, para que la demo y los tests muestren que no se ve lo ajeno.
- La factory de tests (`ArtistFactory`, `AlbumFactory`) genera datos aleatorios aparte; el seeder de demostración no se usa en los tests.

## 7. Supuestos a Validar

1. **Lista cerrada de géneros** (I6) con los 10 de la tabla, pensados para un salón en Colombia. Cambiarla después es editar `config/discoteca.php`, pero los álbumes ya guardados con un género retirado quedarían fuera de la lista.
2. **Reset de 15 minutos y revocación de tokens al cambiar contraseña** (I8, I9): es más estricto que el default de Laravel (60 minutos, nada se revoca). Si el correo de Resend tarda en llegar durante la demo, 15 minutos siguen sobrando.

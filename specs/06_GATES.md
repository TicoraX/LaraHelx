---
document: 06_GATES
stage: 6
status: APPROVED
version: 1.1.0
last_updated: 2026-10-24
approved_by: human
depends_on: [04_DESIGN_BRIEF, 05_BACKEND_SCHEMA]
---

# Gates: Discoteca (Laravel 12, sustentación 2026-11-21)

> v1.1.0 (2026-10-24): corregido según `REVIEW_05-06.md` (A1, A2, A3, A5, A6, A7, A8, M2, M3, M4, M5, M6, M7, M9, M10, M11, M12, B1, B5, B7, B8). Nuevos: S8, S9, S10, F15 a F21, P9, P10. P8 sale del ledger a los pendientes del dueño.

Scope: cada ítem de la rúbrica (01 §4) funciona en `https://laravel.helx.page`, probado con tests de feature contra PostgreSQL y con evidencia registrada antes del ensayo del 2026-11-18.

Ledger en formato `/unlazy`. Se corre desde la raíz del repo (las rutas de `unlazy` son de la máquina del dueño; ver "Quién corre qué"):

```text
node C:/Users/santi/.claude/skills/unlazy/scripts/gate-lint.mjs specs/06_GATES.md
node C:/Users/santi/.claude/skills/unlazy/scripts/gate-check.mjs --status specs/06_GATES.md
node C:/Users/santi/.claude/skills/unlazy/scripts/gate-check.mjs --cwd . --approve specs/06_GATES.md
```

Reglas del ledger:
- Un gate runnable cuenta solo si el proceso sale con 0 **y** la salida casa con `EXPECT:`. `php artisan test` imprime `Tests:  N passed` solo cuando todo pasa; si algo falla, se salta o queda incompleto, la línea dice `1 failed, 4 passed` (o `skipped`) y el regex no casa. Sin la línea `Tests:` (ruta de tests inexistente) tampoco casa.
- Cada `EXPECT` de tests exige un **mínimo de tests igual al número de comportamientos del título**: un archivo con un test trivial no cumple un gate que lista seis comportamientos.
- Shell del checker en Windows: `cmd.exe`. Los comandos usan solo `php`, `node`, `npm`, `composer`, `git`, `ssh` y `gitleaks` (8.19 o superior, 02 §7), que deben estar en el PATH.
- Responsable (A, B, C o dueño) según el PRD §6, en el título de cada gate. Nadie marca un gate de otro sin que esa persona lo vea pasar.
- Si el host cambia (a más tardar el 2026-11-12, 02 §5), P1 a P7 y E2 se repiten sobre el host nuevo.

## Quién corre qué

`unlazy` guarda las aprobaciones por máquina (`~/.unlazy/approved`), así que cada integrante aprueba y corre sus gates en su propia máquina, con el entorno de 02 §7 (S8 lo demuestra). Los gates que dependen de la máquina del dueño (rutas absolutas de `~/.claude`, alias `ssh oracle` con su llave) están en la sección "Solo el dueño".

| Quién | Gates |
| :--- | :--- |
| A | S3, F1, F2, F6, F11, F18, F19, F20, F21 |
| B | F3, F4, F5, F8, F14, F15 |
| C | S1, S2, S4, S5, S6, S9, S10, F7, F9, F10, F12, F13, F16, F17, P1, P2, P3, P4, P6, E1, E3 |
| Los tres | S8 (cada uno su línea de evidencia), E2 |
| Dueño | G0, S7, P5, P7, P9, P10 y el redeploy de P6 (todo lo que pasa por `ssh oracle`) |

## Seams de los tests (TDD)

Los tests viven en estas fronteras y en ninguna otra (`/tdd`: solo en seams acordados):
1. **HTTP web**: peticiones a las rutas de 03 §1 con `actingAs`, afirmando estado, redirección, sesión y HTML visible. Nunca se consulta la BD para verificar algo que la respuesta ya muestra.
2. **HTTP API**: peticiones a `/api/v1` con tokens reales (`createToken` + `withToken`), nunca `actingAs` ni `Sanctum::actingAs`: `Sanctum\Guard` consulta primero el guard `web` y el test pasaría sin token (05 §1). Además de `getJson`, peticiones con `->get()`/`->put()` sin `Accept`, porque Postman no lo manda.
3. **Frontera GoRest**: `Http::fake()` sobre `gorest.co.in`, el único mock permitido. Se afirma qué cabeceras salieron (`Http::assertSent`) y cómo se pinta cada respuesta.
4. **Correo**: `Notification::fake()` para el enlace de reset y `toMail` para el asunto y el cuerpo; el correo real se prueba una vez en producción (S5).

`phpunit.xml` deja `SESSION_DRIVER=array`: los tests de sesiones (F20) insertan filas en `sessions` y afirman que la acción las borra; el comportamiento con el driver real se comprueba en P6.

Orden: un test rojo, el código mínimo para ponerlo verde, el siguiente test. Nada de escribir todos los tests primero.

## Fase S: esqueleto publicado (fecha límite 2026-10-30)

- [ ] S1: el proyecto corre Laravel 12 y no la 13 (responsable C)
  CHECK: php artisan --version
  EXPECT: /Laravel Framework 12\.\d+\.\d+/
  EVIDENCE: pending

- [ ] S2: la suite corre contra PostgreSQL: un test afirma el driver pgsql sobre la base laravel_test (responsable C)
  CHECK: php artisan test tests/Feature/DatabaseConnectionTest.php
  EXPECT: /Tests:\s+(?:[1-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] S3: autenticación con vistas propias, login y olvidé sin enumerar (11 tests: las vistas propias de login, registro, olvidé y reset; el registro aterriza en /dashboard; registrar ANA@x.com con ana@x.com existente da error de validación y no 500; el login aterriza en /dashboard; quien se registró como ana@x.com entra con Ana@X.com; el login fallido da un mensaje genérico; el logout invalida la sesión; olvidé responde igual con un correo existente y uno inexistente; el reset cambia la contraseña y la vieja deja de servir; el enlace vencido a los 16 minutos no cambia la contraseña; el enlace usado no sirve dos veces) (responsable A)
  CHECK: php artisan test tests/Feature/Auth
  EXPECT: /Tests:\s+(?:[2-9]\d|1[1-9]|[1-9]\d{2,}) passed/
  EVIDENCE: pending

- [ ] S4: producción responde sana con HTTPS, las cinco cabeceras de seguridad y cookies seguras (script que imprime "smoke passed" solo si cumple todo lo de la lista de abajo) (responsable C)
  CHECK: node scripts/gates/smoke.mjs https://laravel.helx.page
  EXPECT: smoke passed
  EVIDENCE: pending

- [ ] S5: un correo real de restablecimiento llega desde producción por Resend, con remitente no-reply@helx.page, asunto y cuerpo en español y un enlace que empieza con https://laravel.helx.page/reset-password/ (evidencia: id del envío en el panel de Resend, hora de llegada y captura del correo) (responsable C)
  EVIDENCE: pending

- [ ] S6: el historial de git no contiene secretos (responsable C)
  CHECK: gitleaks git --no-banner
  EXPECT: no leaks found
  EVIDENCE: pending

- [ ] S7: decisión escrita sobre el host donde corre la app el 2026-11-21, a más tardar el 2026-10-30: A1 con capacidad, la Intel con Pay As You Go confirmado o el plan B contratado (evidencia: la decisión con fecha y captura de la consola de Oracle o del proveedor) (responsable dueño)
  EVIDENCE: pending

- [ ] S8: los tres integrantes corren la suite en su propia máquina contra su PostgreSQL local (evidencia: la línea "Tests: N passed" de cada uno, con fecha, hasta 2026-10-28) (responsables A, B y C)
  EVIDENCE: pending

- [ ] S9: el repositorio publicado en GitHub tiene un README con la instalación de 02 §7 y al menos 3 autores distintos entre sus commits (responsable C)
  CHECK: node -e "process.exit(/composer install/.test(require('fs').readFileSync('README.md','utf8'))?0:1)" && git shortlog -sn --all
  EXPECT: /^\s*\d+\s+\S.*\r?\n\s*\d+\s+\S.*\r?\n\s*\d+\s+\S/m
  EVIDENCE: pending

- [ ] S10: la configuración obligatoria de 02 §6 está aplicada (4 tests: no existen rutas de 2FA ni de passkeys; el disco public lanza excepción al fallar una escritura; .env.example declara pgsql, sync, database, GOREST_TOKEN, SEED_DEMO_PASSWORD y MAIL_FROM_ADDRESS; ninguna ruta es un closure) (responsable C)
  CHECK: php artisan test tests/Feature/ConfigTest.php
  EXPECT: /Tests:\s+(?:[4-9]|[1-9]\d+) passed/
  EVIDENCE: pending

### Qué verifica `scripts/gates/smoke.mjs` (S4)

Script de Node sin dependencias, que recibe la URL base y sale con código distinto de 0 ante cualquier falla, imprimiendo cuál. Imprime `smoke passed` solo si todo cumple:
- `GET /up` y `GET /login` responden 200 por HTTPS (un 500 en `/login` delata el `tmpfs` de vistas o una ruta closure en `route:cache`).
- En `/login`, las cinco cabeceras de 05 §5 con sus valores exactos: `Content-Security-Policy` con `default-src 'self'`, `script-src 'self'`, `img-src 'self' data:` y `frame-ancestors 'none'`; `X-Content-Type-Options: nosniff`; `X-Frame-Options: DENY`; `Referrer-Policy: strict-origin-when-cross-origin`; `Strict-Transport-Security: max-age=31536000`.
- Cada `Set-Cookie` de `/login` (sesión y `XSRF-TOKEN`) lleva `Secure` y `SameSite=Lax`, y la de sesión además `HttpOnly`.
- `GET /api/v1/artists` sin token y sin `Accept` responde 401 con cuerpo JSON (A2 detrás de Caddy).

## Fase F: funcionalidad completa en producción (fecha límite 2026-11-08)

- [ ] F1: rutas protegidas (7 tests: el invitado va al login; el autenticado no ve /login; ni /register; un artista ajeno da 404 en show, edit, update y destroy; un álbum ajeno da 404; el ajeno y el inexistente responden con el mismo cuerpo; un PUT ajeno con datos inválidos da 404 y no 422) (responsable A)
  CHECK: php artisan test tests/Feature/AccessControlTest.php
  EXPECT: /Tests:\s+(?:[7-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] F2: dashboard (6 tests: muestra el nombre; la foto si existe; las iniciales si no; el menú completo con logout por POST; conteos reales de artistas y álbumes; estado vacío honesto con 0 artistas) (responsable A)
  CHECK: php artisan test tests/Feature/DashboardTest.php
  EXPECT: /Tests:\s+(?:[6-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] F3: CRUD de artistas con foto (10 tests: crear; ver; editar; borrar en cascada con la foto y las portadas de sus álbumes después del commit; rechaza un archivo que no es imagen; uno de más de 2 MB; uno de más de 2000 px; editar borra la foto vieja después del commit; evil.php.jpg con contenido de imagen se guarda como artists/<40 caracteres>.<jpg|png|webp>, distinto del nombre original; un género fuera de la lista da error de validación) (responsable B)
  CHECK: php artisan test tests/Feature/ArtistCrudTest.php
  EXPECT: /Tests:\s+(?:[2-9]\d|1[0-9]|[1-9]\d{2,}) passed/
  EVIDENCE: pending

- [ ] F4: CRUD de álbumes con portada (7 tests: crear; ver; editar; borrar; rechaza un artist_id ajeno; un año fuera de rango da error de validación; un género fuera de la lista también) (responsable B)
  CHECK: php artisan test tests/Feature/AlbumCrudTest.php
  EXPECT: /Tests:\s+(?:[7-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] F5: listas (4 tests: la de artistas entrega la tabla que DataTables mejora; la de álbumes también; sin artistas, estado vacío con CTA; en álbumes sin artistas, el CTA lleva a crear un artista) (responsable B)
  CHECK: php artisan test tests/Feature/ListsTest.php
  EXPECT: /Tests:\s+(?:[4-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] F6: perfil (5 tests: editar el nombre; el correo; la foto; un correo de otra cuenta da error y no se guarda; el correo nuevo se guarda en minúsculas) (responsable A)
  CHECK: php artisan test tests/Feature/ProfileTest.php
  EXPECT: /Tests:\s+(?:[5-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] F7: estadísticas (3 tests: el gráfico recibe los conteos por género del usuario; no cuenta álbumes ajenos; con 0 álbumes muestra el estado vacío) (responsable C)
  CHECK: php artisan test tests/Feature/StatsTest.php
  EXPECT: /Tests:\s+(?:[3-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] F8: ficha PDF (4 tests: se descarga como application/pdf; con el nombre artista-<slug>.pdf; la de un artista ajeno da 404; con una portada WebP de 2000 px la respuesta empieza con %PDF y tarda menos de 3 s en el entorno del test) (responsable B)
  CHECK: php artisan test tests/Feature/ArtistPdfTest.php
  EXPECT: /Tests:\s+(?:[4-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] F9: API con tokens reales (13 tests: POST /tokens emite un token; acepta el correo con mayúsculas; credenciales malas dan 422 genérico; listar; ver; crear con 201 y Location; PUT reemplaza y pone a null los opcionales omitidos; DELETE da 204; sin token y sin Accept da 401 en JSON; un token de más de 30 días da 401; el ajeno y el inexistente dan 404 con el mismo cuerpo, sin App\Models; datos inválidos sin Accept dan 422 en JSON por campo; el sexto POST /tokens en un minuto da 429) (responsable C)
  CHECK: php artisan test tests/Feature/Api
  EXPECT: /Tests:\s+(?:[2-9]\d|1[3-9]|[1-9]\d{2,}) passed/
  EVIDENCE: pending

- [ ] F10: GoRest con Http::fake (12 tests: GET filtrado por @discoteca.test con per_page=100; POST; PUT; DELETE; cada llamada sale con Bearer y Accept JSON; mensaje propio para 401; para 403; para 404; los 422 junto a cada campo; para 429; para timeout; una respuesta sin id se trata como error) sobre usuarios externos (responsable C)
  CHECK: php artisan test tests/Feature/GoRestTest.php
  EXPECT: /Tests:\s+(?:[2-9]\d|1[2-9]|[1-9]\d{2,}) passed/
  EVIDENCE: pending

- [ ] F11: idioma español (4 tests: mensajes de validación; mensaje de login fallido; asunto y cuerpo del correo de reset vía Notification::fake y toMail; el 401 de la API) (responsable A)
  CHECK: php artisan test tests/Feature/LocaleTest.php
  EXPECT: /Tests:\s+(?:[4-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] F12: con APP_ENV=production (8 tests: la CSP del TRD; las otras cuatro cabeceras; la vista propia 403; la 404; la 413; la 419; la 500, todas heredando la maestra; ningún error muestra trazas) (responsable C)
  CHECK: php artisan test tests/Feature/SecurityHeadersTest.php
  EXPECT: /Tests:\s+(?:[8-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] F13: GoRest real: el flujo crear, editar y borrar un usuario externo @discoteca.test funciona en producción con el token del equipo (evidencia: ids devueltos y captura del recuadro técnico) (responsable C)
  EVIDENCE: pending

- [ ] F14: DataTables 2.3.8 real sobre los 12 álbumes del seeder (2 páginas de 10): búsqueda, orden y paginación en español con el estilo del brief; capturas a 1280 y 375 px sin desborde horizontal; el diálogo de borrado se abre, se cancela y se confirma solo con teclado (evidencia: rutas de las capturas) (responsable B)
  EVIDENCE: pending

- [ ] F15: el seeder de demostración (3 tests: es idempotente al correrlo dos veces; crea el usuario demo con sus 5 artistas y 12 álbumes y el segundo usuario con su artista; falla en APP_ENV=production sin SEED_DEMO_PASSWORD) (responsable B)
  CHECK: php artisan test tests/Feature/DemoSeederTest.php
  EXPECT: /Tests:\s+(?:[3-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] F16: en producción la API responde JSON sin Accept explícito y con token real (evidencia: salida de curl de POST /api/v1/tokens, GET /api/v1/artists con y sin Bearer contra https://laravel.helx.page, y la colección de Postman exportada en docs/) (responsable C)
  EVIDENCE: pending

- [ ] F17: en producción el gráfico se dibuja y la consola del navegador no tiene violaciones de CSP en /stats, /artists y /albums (evidencia: captura de /stats con el gráfico y de la consola vacía). Cubre también los atributos style="" en Blade, que el checker de P5 no lee (responsable C)
  EVIDENCE: pending

- [ ] F18: límites de intentos y caché del logout (5 tests: el sexto login fallido en un minuto da 429; el séptimo POST a /forgot-password da 429; diez GET /login seguidos no dan 429; /dashboard responde Cache-Control con no-store; después del logout, GET /dashboard redirige al login) (responsable A)
  CHECK: php artisan test tests/Feature/ThrottleAndCacheTest.php
  EXPECT: /Tests:\s+(?:[5-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] F19: landing y maestras (3 tests: el invitado ve la landing con "Crear cuenta" e "Iniciar sesión"; con sesión, el CTA lleva al dashboard; landing y login renderizan layouts.guest y dashboard renderiza layouts.app, marcados con data-layout en el body) (responsable A)
  CHECK: php artisan test tests/Feature/LandingTest.php
  EXPECT: /Tests:\s+(?:[3-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] F20: cambio de contraseña (5 tests: cambia con la actual correcta; rechaza una actual incorrecta; borra las otras filas de sessions del usuario y deja la actual; revoca sus tokens de API; rota el remember_token) (responsable A)
  CHECK: php artisan test tests/Feature/PasswordTest.php
  EXPECT: /Tests:\s+(?:[5-9]|[1-9]\d+) passed/
  EVIDENCE: pending

- [ ] F21: tokens desde la UI (4 tests: crear muestra el valor una sola vez; la lista muestra nombre y fecha; revocar un token propio; revocar uno ajeno da 404) (responsable A)
  CHECK: php artisan test tests/Feature/TokenUiTest.php
  EXPECT: /Tests:\s+(?:[4-9]|[1-9]\d+) passed/
  EVIDENCE: pending

## Fase P: producción congelada (fecha límite 2026-11-14)

- [ ] P1: la suite completa pasa con al menos los 119 tests que suman los mínimos de S y F (responsable C)
  CHECK: php artisan test
  EXPECT: /Tests:\s+(?:[2-9]\d{2}|1[2-9]\d|119|[1-9]\d{3,}) passed/
  EVIDENCE: pending

- [ ] P2: los assets compilan para producción (responsable C)
  CHECK: npm run build
  EXPECT: /built in \d/
  EVIDENCE: pending

- [ ] P3: las dependencias PHP no tienen avisos de seguridad (responsable C)
  CHECK: composer audit
  EXPECT: No security vulnerability advisories found
  EVIDENCE: pending

- [ ] P4: las dependencias npm de producción no tienen vulnerabilidades (responsable C)
  CHECK: npm audit --omit=dev
  EXPECT: found 0 vulnerabilities
  EVIDENCE: pending

- [ ] P6: prueba de humo completa en producción: subir una foto, verla en la lista, descargar su ficha PDF con la foto, la imagen sigue ahí después de un redeploy, y al cambiar la contraseña en un navegador la sesión abierta en otro cae al login (evidencia: hora del redeploy y capturas) (responsable C; el redeploy lo hace el dueño)
  EVIDENCE: pending

## Solo el dueño (máquina de Santiago)

Estos gates usan rutas absolutas de `C:/Users/santi/.claude` o el alias `ssh oracle` con la llave del dueño: solo corren en su máquina. P5, P7, P9 y P10 tienen la fecha límite de la fase P (2026-11-14) y se repiten sobre el host nuevo si cambia; G0 se corre cada vez que cambia este archivo.

- [ ] G0: este ledger tiene compuertas que pueden fallar (responsable dueño)
  CHECK: node C:/Users/santi/.claude/skills/unlazy/scripts/gate-lint.mjs specs/06_GATES.md
  EXPECT: LINT OK
  EVIDENCE: pending

- [ ] P5: el CSS, el JS y las vistas propias pasan el checker de deliberate sin hallazgos, y las tres carpetas existen (el checker no lee .blade.php: esa parte la cubre F17) (responsable dueño)
  CHECK: node -e "for (const d of ['resources/css','resources/js','resources/views']) require('fs').statSync(d)" && node C:/Users/santi/.claude/skills/deliberate/scripts/check.mjs resources/css resources/js resources/views --strict
  EXPECT: /[1-9]\d* files(?:\x1b\[\d+m)*\s+(?:\x1b\[\d+m)*0 error(?:\x1b\[\d+m)*\s+(?:\x1b\[\d+m)*0 warn/
  EVIDENCE: pending

- [ ] P7: el respaldo diario existe en la VPS: el último laravel-YYYYmmdd.dump pesa más de 10 KB y él y su laravel-uploads-YYYYmmdd.tar.gz son de las últimas 26 horas (script remoto que imprime "backup fresh" solo si se cumple) (responsable dueño)
  CHECK: ssh -o BatchMode=yes oracle "find ~/backups/laravel -name 'laravel-*.dump' -mmin -1560 -size +10k | grep -q . && find ~/backups/laravel -name 'laravel-uploads-*.tar.gz' -mmin -1560 | grep -q . && echo backup fresh"
  EXPECT: backup fresh
  EVIDENCE: pending

- [ ] P9: la app corre en producción con un rol de base de datos que no es superusuario (responsable dueño)
  CHECK: ssh -o BatchMode=yes oracle "docker exec laravel-db psql -U app -d laravel -tAc 'select rolsuper from pg_roles where rolname=current_user'"
  EXPECT: /^f\s*$/m
  EVIDENCE: pending

- [ ] P10: un respaldo de producción se restaura en una base vacía y la app arranca con sus imágenes (evidencia: comando de restauración con --no-owner --role=app, conteo de artistas y álbumes antes y después, una portada visible) (responsable dueño)
  EVIDENCE: pending

Pendiente del dueño, fuera del ledger (no es un entregable del curso): registrar la app en "Apps desplegadas" y el puerto 8000 en "Puertos tomados" de `~/.claude/CLAUDE.md` (antes P8).

## Fase E: sustentación (2026-11-18)

- [ ] E1: el guion docs/GUION.md recorre los 23 ítems de la rúbrica de 01 §4 en menos de 12 minutos, con un responsable por tramo (responsable C)
  EVIDENCE: pending

- [ ] E2: ensayo completo sobre producción el 2026-11-18, cada integrante con sus historias, cronometrado (evidencia: duración y lista de fallos encontrados) (responsables A, B y C)
  EVIDENCE: pending

- [ ] E3: respaldo de la demo: video grabado del flujo de GoRest y de la API con Postman, por si un tercero falla el día de la sustentación (responsable C)
  EVIDENCE: pending

## Definición de Terminado

- Todos los gates de S, F, P, E y "Solo el dueño" con evidencia real; ningún gate abandonado sin `ABANDON:` y su motivo.
- Cero `TODO`, código de relleno o datos inventados en lo entregado.
- Specs 00 a 05 alineados con el código: si la implementación cambia una decisión, se corrige el spec en el mismo commit.
- Cada commit con `php artisan test` en verde; un commit por cambio lógico.

# LaraHelx: Discoteca (proyecto universitario, Laravel 12)

Catálogo personal de artistas y álbumes. Equipo de 3, sustentación de 15 minutos el 2026-11-21, publicado en `https://laravel.helx.page`. Toda la planeación está aprobada en `specs/`: si el código y un spec no coinciden, gana el spec hasta que se corrija en el mismo commit.

## Dónde está cada cosa

| Necesitas | Lee |
| :--- | :--- |
| Qué se construye y por qué (historias H1-H15, reparto A/B/C) | `specs/01_PRD.md` |
| Stack, despliegue, configuración obligatoria, entorno local | `specs/02_TRD.md` (§4, §6, §7) |
| Rutas, guardas y los 5 estados de cada pantalla | `specs/03_APP_FLOW.md` |
| Tokens visuales, tipografía, DataTables, Chart.js, PDF | `specs/04_DESIGN_BRIEF.md` y `specs/design/prototype.html` |
| Tablas, invariantes I1-I9, validación, contratos de la API | `specs/05_BACKEND_SCHEMA.md` |
| Qué significa "terminado" (44 compuertas con `CHECK:`/`EXPECT:`) | `specs/06_GATES.md` |
| Vocabulario del dominio (Artista, Álbum, Usuario externo...) | `CONTEXT.md` |
| Por qué cambió un spec | La nota de versión al inicio de cada spec y `git log -- specs/` |

## Reglas

- Laravel fijado en `^12.0` (la 13 ya existe; la rúbrica pide 12). PHP 8.4, PostgreSQL 17.
- TDD en los cuatro seams de `06_GATES.md`: HTTP web, HTTP API, GoRest (`Http::fake()`, único mock) y correo (`Notification::fake()`). Un test rojo, el código mínimo, el siguiente test.
- Los tests corren contra PostgreSQL (`laravel_test`), nunca SQLite.
- Fail-fast en toda escritura: nada de `catch` vacíos ni datos de relleno; transacción, rollback y excepción. Estados de error y vacíos honestos en la UI.
- Lo ajeno responde 404 (`Response::denyAsNotFound()`), en web y en API.
- Blade siempre con `{{ }}`; prohibido `{!! !!}` con datos del usuario. Sin atributos `style=""` ni scripts inline (CSP `script-src 'self'`).
- UI solo en español (`lang/es`). Sin emojis en código, commits ni textos.
- Commits pequeños, uno por cambio lógico, con `php artisan test` en verde y sin líneas `Co-Authored-By` ni otra atribución a agentes de IA. Si una decisión de un spec cambia, el spec se actualiza en el mismo commit.
- Si algo falla dos veces seguidas, parar y replantear antes de un tercer intento.

## Comandos

- Entorno local (una vez): `specs/02_TRD.md` §7.
- Desarrollo: `composer run dev`. Tests: `php artisan test`. Build: `npm run build`.

## Skills recomendadas (si tu agente las tiene)

`tdd`, `unlazy` (para el ledger de GATES), `karpathy-guidelines`, `system-fallback-guard`, `backend-hardening`, `domain-modeling`; para vistas, `deliberate` e `impeccable harden`.

Configuración específica de cada máquina (rutas, VPS, herramientas locales): `CLAUDE.local.md`, fuera de git.

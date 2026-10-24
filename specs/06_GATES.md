---
document: 06_GATES
stage: 6
status: APPROVED
version: 1.0.0
last_updated: 2026-10-24
approved_by: human
depends_on: [04_DESIGN_BRIEF, 05_BACKEND_SCHEMA]
---

# Gates: Discoteca (Laravel 12, sustentación 2026-11-21)

Scope: cada ítem de la rúbrica funciona en `https://laravel.helx.page`, probado con tests de feature contra PostgreSQL y con evidencia registrada antes del ensayo del 2026-11-18.

Ledger en formato `/unlazy`. Se corre desde la raíz del repo:

```text
node C:/Users/santi/.claude/skills/unlazy/scripts/gate-lint.mjs specs/06_GATES.md
node C:/Users/santi/.claude/skills/unlazy/scripts/gate-check.mjs --status specs/06_GATES.md
node C:/Users/santi/.claude/skills/unlazy/scripts/gate-check.mjs --cwd . --approve specs/06_GATES.md
```

Reglas del ledger:
- Un gate runnable cuenta solo si el proceso sale con 0 **y** la salida casa con `EXPECT:`. `php artisan test` imprime `Tests:  N passed` solo cuando todo pasa (si algo falla, la línea dice `N failed`), y una ruta de tests inexistente sale con error: así un gate no pasa por accidente.
- Shell del checker en Windows: `cmd.exe`. Los comandos usan solo `php`, `node`, `npm`, `composer`, `ssh` y `gitleaks`, que deben estar en el PATH.
- Responsable (A, B, C) según el PRD §6. Nadie marca un gate de otro sin que esa persona lo vea pasar.

## Seams de los tests (TDD)

Los tests viven en estas fronteras y en ninguna otra (`/tdd`: solo en seams acordados):
1. **HTTP web**: peticiones a las rutas de 03 §1 con `actingAs`, afirmando estado, redirección, sesión y HTML visible. Nunca se consulta la BD para verificar algo que la respuesta ya muestra.
2. **HTTP API**: peticiones JSON a `/api/v1` con y sin `Authorization: Bearer`.
3. **Frontera GoRest**: `Http::fake()` sobre `gorest.co.in`, el único mock permitido. Se afirma qué cabeceras salieron (`Http::assertSent`) y cómo se pinta cada respuesta.
4. **Correo**: `Notification::fake()` para el enlace de reset; el correo real se prueba una vez en producción (S5).

Orden: un test rojo, el código mínimo para ponerlo verde, el siguiente test. Nada de escribir todos los tests primero.

## Fase S: esqueleto publicado (fecha límite 2026-10-30, responsable C con A)

- [ ] G0: este ledger tiene compuertas que pueden fallar
  CHECK: node C:/Users/santi/.claude/skills/unlazy/scripts/gate-lint.mjs specs/06_GATES.md
  EXPECT: LINT OK
  EVIDENCE: pending

- [ ] S1: el proyecto corre Laravel 12 y no la 13
  CHECK: php artisan --version
  EXPECT: /Laravel Framework 12\.\d+\.\d+/
  EVIDENCE: pending

- [ ] S2: la suite corre contra PostgreSQL (test que afirma el driver pgsql y la base laravel_test)
  CHECK: php artisan test tests/Feature/DatabaseConnectionTest.php
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] S3: registro, login, logout y "olvidé mi contraseña" responden con las vistas propias y sin enumerar correos
  CHECK: php artisan test tests/Feature/Auth
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] S4: producción responde sana con HTTPS y cabeceras de seguridad (script que pide /up, /login y verifica CSP, HSTS, nosniff y X-Frame-Options; imprime "smoke passed" solo si todo cumple)
  CHECK: node scripts/gates/smoke.mjs https://laravel.helx.page
  EXPECT: smoke passed
  EVIDENCE: pending

- [ ] S5: un correo real de restablecimiento llega desde producción por Resend (evidencia: id del envío en el panel de Resend y hora de llegada)
  EVIDENCE: pending

- [ ] S6: el historial de git no contiene secretos
  CHECK: gitleaks git --no-banner
  EXPECT: no leaks found
  EVIDENCE: pending

- [ ] S7: el host donde corre la app existirá el 2026-11-21 (A1 con capacidad, la Intel con Pay As You Go confirmado, o el plan B contratado; evidencia: captura de la consola de Oracle o del proveedor)
  EVIDENCE: pending

## Fase F: funcionalidad completa en producción (fecha límite 2026-11-08)

- [ ] F1: rutas protegidas: invitado va al login, autenticado no ve /login, lo ajeno da 404 en web y API (responsable A)
  CHECK: php artisan test tests/Feature/AccessControlTest.php
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] F2: el dashboard muestra nombre, foto o iniciales, el menú completo y conteos reales, con estado vacío honesto (responsable A)
  CHECK: php artisan test tests/Feature/DashboardTest.php
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] F3: CRUD de artistas con foto: crear, ver, editar, borrar en cascada; rechaza archivos no imagen, mayores a 2 MB o de más de 4000 px; borra la imagen vieja después del commit (responsable B)
  CHECK: php artisan test tests/Feature/ArtistCrudTest.php
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] F4: CRUD de álbumes con portada; rechaza un artist_id ajeno; año fuera de rango y género fuera de la lista dan error de validación (responsable B)
  CHECK: php artisan test tests/Feature/AlbumCrudTest.php
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] F5: las listas de artistas y álbumes entregan la tabla que DataTables mejora y el estado vacío con CTA (responsable B)
  CHECK: php artisan test tests/Feature/ListsTest.php
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] F6: perfil: editar nombre, correo y foto; cambiar contraseña con la actual; al cambiarla se cierran las otras sesiones y se revocan los tokens; crear y revocar tokens desde la UI (responsable A)
  CHECK: php artisan test tests/Feature/ProfileTest.php
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] F7: estadísticas: el gráfico recibe los conteos por género del usuario y muestra estado vacío sin álbumes (responsable C)
  CHECK: php artisan test tests/Feature/StatsTest.php
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] F8: la ficha PDF de un artista se descarga como application/pdf con su nombre de archivo, y la de un artista ajeno da 404 (responsable B)
  CHECK: php artisan test tests/Feature/ArtistPdfTest.php
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] F9: API: emisión de token, CRUD de artistas con Bearer, 401 sin token, 404 sobre lo ajeno, 422 por campo, 429 al pasarse del límite (responsable C)
  CHECK: php artisan test tests/Feature/Api
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] F10: GoRest: GET filtrado por @discoteca.test, POST, PUT y DELETE con Bearer y Accept JSON, y mensajes propios para 401, 403, 404, 422, 429 y timeout (responsable C)
  CHECK: php artisan test tests/Feature/GoRestTest.php
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] F11: los mensajes de validación y de autenticación salen en español (responsable A)
  CHECK: php artisan test tests/Feature/LocaleTest.php
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] F12: con APP_ENV=production las respuestas llevan la CSP del TRD y las cabeceras de seguridad, y los errores no muestran trazas (responsable C)
  CHECK: php artisan test tests/Feature/SecurityHeadersTest.php
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] F13: GoRest real: el flujo crear, editar y borrar un usuario @discoteca.test funciona en producción con el token del equipo (evidencia: ids devueltos y captura del recuadro técnico)
  EVIDENCE: pending

- [ ] F14: DataTables 2.3.8 real sobre 25 filas del seeder: búsqueda, orden y paginación con el estilo del brief; capturas a 1280 y 375 px sin desborde horizontal; el diálogo de borrado se abre, se cancela y se confirma solo con teclado (evidencia: rutas de las capturas)
  EVIDENCE: pending

## Fase P: producción congelada (fecha límite 2026-11-14)

- [ ] P1: la suite completa pasa
  CHECK: php artisan test
  EXPECT: /Tests:\s+\d+ passed/
  EVIDENCE: pending

- [ ] P2: los assets compilan para producción
  CHECK: npm run build
  EXPECT: /built in \d/
  EVIDENCE: pending

- [ ] P3: las dependencias PHP no tienen avisos de seguridad
  CHECK: composer audit
  EXPECT: No security vulnerability advisories found
  EVIDENCE: pending

- [ ] P4: las dependencias npm de producción no tienen vulnerabilidades
  CHECK: npm audit --omit=dev
  EXPECT: found 0 vulnerabilities
  EVIDENCE: pending

- [ ] P5: el CSS y el JS propios pasan el checker de deliberate sin hallazgos
  CHECK: node C:/Users/santi/.claude/skills/deliberate/scripts/check.mjs resources/css resources/js --strict
  EXPECT: /0 error\S*\s+\S*0 warn/
  EVIDENCE: pending

- [ ] P6: prueba de humo completa en producción: subir una foto, verla en la lista, descargar su ficha PDF con la foto, y la imagen sigue ahí después de un redeploy (evidencia: hora del redeploy y captura)
  EVIDENCE: pending

- [ ] P7: el respaldo diario existe en la VPS y su último archivo es de las últimas 26 horas (script remoto que imprime "backup fresh" solo si se cumple)
  CHECK: ssh -o BatchMode=yes oracle "find ~/backups/laravel -name '*.dump' -mmin -1560 | grep -q . && echo backup fresh"
  EXPECT: backup fresh
  EVIDENCE: pending

- [ ] P8: la app está registrada en "Apps desplegadas" y su puerto en "Puertos tomados" de ~/.claude/CLAUDE.md
  CHECK: node -e "const s=require('fs').readFileSync(require('os').homedir()+'/.claude/CLAUDE.md','utf8'); const ok=/Laravel[^\n]*laravel\.helx\.page/i.test(s) && /Puertos tomados:[^\n]*8000/.test(s); if(!ok) process.exit(1); console.log('registry ok')"
  EXPECT: registry ok
  EVIDENCE: pending

## Fase E: sustentación (2026-11-18)

- [ ] E1: el guion docs/GUION.md recorre los 17 ítems de la rúbrica en menos de 12 minutos, con un responsable por tramo (responsable C)
  EVIDENCE: pending

- [ ] E2: ensayo completo sobre producción el 2026-11-18, cada integrante con sus historias, cronometrado (evidencia: duración y lista de fallos encontrados)
  EVIDENCE: pending

- [ ] E3: respaldo de la demo: video grabado del flujo de GoRest y de la API con Postman, por si un tercero falla el día de la sustentación
  EVIDENCE: pending

## Definición de Terminado

- Todos los gates de S, F, P y E con evidencia real; ningún gate abandonado sin `ABANDON:` y su motivo.
- Cero `TODO`, código de relleno o datos inventados en lo entregado.
- Specs 00 a 05 alineados con el código: si la implementación cambia una decisión, se corrige el spec en el mismo commit.
- Cada commit con `php artisan test` en verde; un commit por cambio lógico.

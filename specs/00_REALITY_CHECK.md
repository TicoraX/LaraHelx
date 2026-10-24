---
document: 00_REALITY_CHECK
stage: 0
status: APPROVED
version: 1.1.0
last_updated: 2026-10-24
approved_by: human
depends_on: []
---

# 00. Reality Check y Evaluación de Viabilidad

Proyecto universitario en Laravel 12, equipo de 3. Sustentación: sábado 2026-11-21 (15 min). Entrega: app publicada en internet y repo en GitHub.

> v1.1.0 (2026-10-23): corregido según `REVIEW_00-04.md` (A4, A5, L1, recortes de alcance). Hosting resuelto: VPS Oracle del usuario (ver 02_TRD §4).

## 1. Veredicto Inicial

- **Estado general**: ATERRIZADA, con la condición de no construir nada fuera de la rúbrica. Cada ítem de la rúbrica tiene un paquete oficial o de facto en el ecosistema Laravel; el trabajo es integrar, no inventar.
- **Mayor riesgo de muerte**: el despliegue, no el código: base de datos persistente, imágenes que sobrevivan a un redeploy y correo real para "olvidé mi contraseña", sobre una VM que vence hacia el 2026-11-20. Por eso el despliegue va primero: un esqueleto mínimo publicado en el host definitivo, con un correo de reset real llegando, antes del 2026-10-30 (ver 02_TRD §5).
- **Segundo riesgo**: el entorno local de los tres integrantes. En esta máquina no había PHP ni PostgreSQL; quedó resuelto con PHP 8.4 oficial y PostgreSQL en Docker (camino único documentado en el README, ver 02_TRD §6). Un stack que solo una persona ha corrido es un riesgo para la defensa oral.
- **Apetito temporal**: 29 días calendario (2026-10-23 a 2026-11-21), 3 personas en tiempo parcial. Objetivo interno: la app publicada y completa el 2026-11-14, con una semana de colchón para el ensayo y los arreglos.

## 2. Auditoría de Reinvención de Rueda (Build vs Buy)

| Requisito de la rúbrica | Herramienta existente | Decisión |
| :--- | :--- | :--- |
| Registro, login, logout, restaurar contraseña, editar perfil | **Laravel Fortify** (oficial, sin vistas: trae la lógica y las rutas, uno pone las vistas Blade) | ADOPTAR. Cumple "formulario personalizado" porque las vistas son propias y heredan la maestra. Descartados los starter kits de Laravel 12 (React, Vue, Livewire con Flux): imponen su propia UI y una SPA que nadie del equipo tiene que defender. |
| Vistas que heredan de una maestra | Blade `@extends` / componentes de layout | Nativo. Dos maestras: `layouts/guest` (landing, login, registro) y `layouts/app` (dashboard). |
| Rutas protegidas | Middleware `auth` y `guest`; `auth:sanctum` en la API; Policies para que cada quien edite solo lo suyo | Nativo. |
| Migraciones, modelos y controladores | `php artisan make:model -mcr` (modelo, migración y controlador resource) | Nativo. |
| Listar dos tablas con DataTables | **DataTables 2** (npm `datatables.net-dt@^2.3`, empaquetado con Vite; trae jQuery como dependencia aunque su API se usa sin jQuery) sobre la tabla HTML que pinta Blade | ADOPTAR el modo cliente. `yajra/laravel-datatables` (modo servidor) va al purgatorio: sirve con miles de filas y aquí habrá decenas. |
| Un chart | **Chart.js 4** (npm, empaquetado con Vite) alimentado por una consulta agregada en el controlador | ADOPTAR. |
| Informe en PDF | **barryvdh/laravel-dompdf** | ADOPTAR. Renderiza una vista Blade a PDF. |
| Subir imágenes | `Storage` de Laravel con validación `image|max:2048` | Nativo. Disco `public` en un volumen Docker de la VPS. |
| Ruta API para apps externas | **Laravel Sanctum** (`php artisan install:api`), tokens Bearer | ADOPTAR. Resuelve a la vez "ruta API" y "tokens, cabeceras y autorización" del lado servidor. |
| Consumir REST con GET, POST, PUT y DELETE gestionando tokens | Cliente `Http` de Laravel + **GoRest** (`gorest.co.in`): API pública que exige `Authorization: Bearer` para escribir | ADOPTAR. Verificado el 2026-10-23: `GET /public/v2/users` responde 200 y `POST` sin token responde 401 "Authentication failed". Muestra los cuatro verbos, el token y las cabeceras contra un servicio de verdad. |
| Correo de recuperación | Local: driver `log` o Mailpit en Docker. Producción: SMTP de Resend, Brevo o Mailtrap | ADOPTAR. Decidido: Resend con el dominio `helx.page`. |
| Entorno local sin PHP | PHP 8.4 NTS x64 oficial (zip de windows.php.net con las extensiones activadas en `php.ini`) + Composer + PostgreSQL en un contenedor Docker | ADOPTAR. ~~php.new~~ descartado: su PHP (Herd Lite) no trae `pdo_pgsql`. Laravel Sail en Windows exige WSL2. |
| Tests (TDD) | PHPUnit (viene con Laravel 12), contra PostgreSQL (`laravel_test`), con tests de feature por HTTP; `Http::fake()` solo en la frontera con GoRest | ADOPTAR. |

**Decisión**: adoptar todo lo existente. Código propio solo para el dominio (dos entidades), las vistas y el pegamento entre piezas.

## 3. El Loop Atómico

La rúbrica no tiene un producto; mide un recorrido. El loop que tiene que salir perfecto en la sustentación:

- **Entrada**: un visitante entra a la landing, se registra y llega al dashboard con su nombre.
- **Proceso central**: crea un registro de la entidad principal con imagen, lo ve en la tabla de DataTables, lo edita y lo borra.
- **Feedback**: el chart y el PDF reflejan los datos que acaba de crear.

Todo lo demás (API, consumo de GoRest, perfil, recuperación de contraseña) cuelga del menú del dashboard y se demuestra por separado.

## 4. Poda de Alcance

La rúbrica entera es el núcleo: no se recorta ningún ítem calificado. Se recorta todo lo que no aparece en ella.

### Al cementerio (fuera de plano)

- **SPA con React o Vue / starter kits con Inertia**: dobla el stack a defender en 15 minutos sin sumar puntos.
- **Roles y permisos (spatie/laravel-permission)**: la rúbrica pide usuario activo, no administradores.
- **Redis, colas, WebSockets, microservicios**: cero usuarios concurrentes que lo justifiquen.
- **Microservicio propio para "consumir REST"**: GoRest ya cumple con un servicio real y externo.
- **Segundo gráfico, segundo PDF, revocar el token actual por API (`DELETE /tokens/current`), columna "último uso" de tokens, páginas de confirmación de borrado sin JS, páginas propias para 429 y 503**: la rúbrica pide "al menos uno"; cada extra es superficie que defender sin puntos (REVIEW §4).

### Al purgatorio (solo si todo lo calificado está publicado y probado)

- Verificación de correo al registrarse.
- DataTables en modo servidor (`yajra/laravel-datatables`).
- Exportar a Excel o CSV.
- Modo oscuro e i18n.
- Autenticación de dos factores y passkeys (Fortify las trae activas por defecto: se desactivan en `config/fortify.php`).

### El núcleo intocable (V1 = la rúbrica)

- **Dos tablas con CRUD completo**, relacionadas 1:N, una con imagen. El dominio lo decide el equipo (ver supuesto 1).
- **Autenticación**: landing, registro, login, logout, olvidé mi contraseña, editar perfil (con foto de perfil).
- **Dashboard**: nombre y foto del usuario activo, y un menú con logout, las dos listas DataTables, editar perfil, un chart (álbumes por género), consumo de API. Un PDF (ficha de artista).
- **API propia**: `GET/POST/PUT/DELETE /api/v1/<entidad>` protegida con Sanctum, con emisión de token.
- **Consumo externo**: una pantalla que lista, crea, edita y borra en GoRest con el token guardado en `.env`.
- **Persistencia**: PostgreSQL 17, todo por migraciones y seeders.
- **Publicación**: app en internet y repo en GitHub con README.

## 5. Criterios de Éxito de la V1

- [ ] El recorrido del loop atómico funciona en la URL pública con un usuario recién registrado.
- [ ] `php artisan test` en verde, con al menos un test de feature por ítem de la rúbrica (auth, rutas protegidas, CRUD x2, imagen, API con y sin token, PDF, consumo de GoRest con `Http::fake`).
- [ ] `php artisan migrate:fresh --seed` levanta la base desde cero sin pasos manuales.
- [ ] Un correo real de "olvidé mi contraseña" llega desde producción.
- [ ] Una imagen subida en producción sigue ahí después de un redeploy.

## 6. Supuestos a Validar

1. **Dominio** (RESUELTO en el PRD: Artistas y Álbumes): propuesta, tomada del ejemplo de la rúbrica: **Artistas** (con foto) **y Álbumes** (con portada, año y género; N álbumes por artista). Chart: álbumes por género. PDF: catálogo de un artista con sus álbumes. Si el equipo ya tiene un tema, este documento se ajusta.
2. **Hosting** (RESUELTO): VPS Oracle del usuario como `laravel.helx.page`, con PostgreSQL 17 en Docker. Railway y Render más Neon quedan descartados. Plan B si la VM no sobrevive: VPS genérico de unos 5 USD con Docker (02_TRD §5).
3. **GoRest disponible el día de la sustentación**: es un tercero. Mitigación: timeout corto (02_TRD §3), estado de error honesto en la pantalla y captura grabada del flujo como respaldo.
4. **Reparto del equipo**: este repo es el del equipo y los tres trabajan sobre las mismas specs. El reparto historia → responsable está en el PRD §6 (nombres pendientes del usuario). El diseño visual (Etapa 4) lo hizo un subagente.

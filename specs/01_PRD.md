---
document: 01_PRD
stage: 1
status: APPROVED
version: 1.2.0
last_updated: 2026-10-24
approved_by: human
depends_on: []  # 00_REALITY_CHECK quedó en notas/, fuera de git
---

# 01. Product Requirements Document (PRD)

> v1.1.0 (2026-10-23): corregido según `REVIEW_00-04.md` (B1, A1, A4, M1, L2, L7 y recortes).
>
> v1.2.0 (2026-10-24): corregido según `REVIEW_05-06.md` (A5, M10, B7).

Nombre de trabajo: **Discoteca** (catálogo de artistas y álbumes). El nombre es provisional; lo cambia el equipo.

## 1. Problema e Hipótesis de Valor

- **Problema real (el del curso)**: el equipo tiene que demostrar en 15 minutos que domina el ciclo completo de una app Laravel 12: autenticación propia, rutas protegidas, modelo de datos por migraciones, CRUD, API con tokens, consumo de servicios externos, reportes y despliegue público. La rúbrica es el cliente.
- **Problema del producto (el que se demuestra)**: un coleccionista o una tienda pequeña de discos lleva su catálogo en hojas de cálculo sin fotos, sin relación artista-álbum y sin forma de compartirlo. Discoteca le da un catálogo con imágenes, búsqueda, un gráfico de su colección por género y un PDF imprimible por artista.
- **Hipótesis**: si cada requisito de la rúbrica sale de una acción natural del catálogo (y no de una pantalla puesta solo para cumplir), la sustentación se cuenta como un recorrido y no como una lista de casillas.
- **Alternativa actual**: Excel o Google Sheets, sin imágenes ni relaciones.

## 2. Usuarios y Historias

Un solo rol: **usuario registrado**. Cada usuario gestiona su propio catálogo; no ve ni edita el de otros. La landing es pública.

Historias con criterios Gherkin. El código entre corchetes es el id con el que el GATES las va a rastrear.

**[H1] Landing pública**
- *Como* visitante, *quiero* ver de qué se trata la app y llegar al registro o al login, *para* decidir si me registro.
- Dado que no inicié sesión, cuando entro a `/`, entonces veo la landing con enlaces a "Registrarme" e "Iniciar sesión".

**[H2] Registro con formulario propio**
- *Como* visitante, *quiero* registrarme con nombre, correo y contraseña, *para* tener mi catálogo.
- Dado un correo nuevo y una contraseña de al menos 8 caracteres confirmada, cuando envío el formulario, entonces quedo con la sesión iniciada en el dashboard.
- Dado un correo ya registrado, cuando envío el formulario, entonces veo el error junto al campo y no se crea otra cuenta.

**[H3] Login y logout**
- Dado un usuario registrado, cuando ingreso correo y contraseña correctos, entonces llego al dashboard.
- Dadas credenciales incorrectas, entonces veo un error genérico (sin revelar si el correo existe).
- Dado que inicié sesión, cuando elijo "Cerrar sesión" en el menú, entonces la sesión se invalida y vuelvo a la landing; el botón Atrás no me devuelve al dashboard con datos.
- Tras 5 intentos fallidos en un minuto, el login se bloquea temporalmente.

**[H4] Olvidé mi contraseña**
- Dado un correo registrado, cuando pido restablecer, entonces recibo un correo con un enlace que lleva un token de un solo uso (guardado con hash en `password_reset_tokens`) y vence a los 15 minutos.
- Cuando abro el enlace y pongo una contraseña nueva, entonces puedo iniciar sesión con ella y la vieja deja de servir.
- Dado un correo no registrado, la pantalla responde igual que con uno registrado (no revela qué correos existen). Esto no es el comportamiento por defecto de Fortify: requiere una respuesta propia (02_TRD §3).
- Tras 6 pedidos en un minuto desde la misma IP, el formulario se bloquea temporalmente.

**[H5] Rutas protegidas**
- Dado que no inicié sesión, cuando entro a cualquier ruta del dashboard, entonces me redirige al login.
- Dado que inicié sesión, cuando intento ver, editar o borrar un artista o álbum de otro usuario, entonces recibo 404 (no se revela que el registro existe).
- Dado que inicié sesión, cuando entro a `/login` o `/register`, entonces me redirige al dashboard.

**[H6] Dashboard con usuario activo y menú**
- Dado que inicié sesión, el dashboard muestra mi nombre y mi foto (o mis iniciales si no tengo foto) y un menú con: Inicio, Artistas, Álbumes, Estadísticas, API externa, Mi perfil y Cerrar sesión.
- El dashboard muestra cuántos artistas y álbumes tengo, contados de la base de datos (cero si no tengo; nunca cifras de ejemplo).

**[H7] CRUD de Artistas (tabla 1)**
- *Como* usuario, *quiero* crear, ver, editar y borrar artistas con nombre, país, género principal, biografía corta y foto, *para* armar mi catálogo.
- Cuando creo un artista con una foto JPG, PNG o WebP de hasta 2 MB, entonces la foto se guarda y se ve en el listado y en el detalle del artista.
- Cuando subo un archivo que no es imagen o pesa más de 2 MB, entonces veo el error y no se guarda nada.
- Cuando borro un artista que tiene álbumes, entonces se me pide confirmar y se borran también sus álbumes y sus imágenes.

**[H8] CRUD de Álbumes (tabla 2)**
- *Como* usuario, *quiero* crear, ver, editar y borrar álbumes con título, artista (uno de los míos), año, género y portada, *para* completar la discografía.
- El selector de artista solo ofrece mis artistas; un `artist_id` ajeno enviado a mano se rechaza.

**[H9] Listas con DataTables**
- Las opciones "Artistas" y "Álbumes" del menú muestran mis registros en DataTables con búsqueda, orden por columna y paginación, en español.
- Con cero registros, la tabla muestra un estado vacío con un botón para crear el primero.

**[H10] Editar mi perfil**
- *Como* usuario, *quiero* cambiar mi nombre, correo y foto de perfil, y por separado mi contraseña (pidiendo la actual), *para* mantener mi cuenta al día.
- Si cambio el correo a uno que ya usa otra cuenta, veo el error y no se guarda.

**[H11] Gráfico**
- La vista "Estadísticas" muestra un gráfico (Chart.js) de mis álbumes por género, calculado de mis datos, con su tabla de datos debajo.
- Con cero álbumes, se muestra un estado vacío honesto en lugar de un gráfico vacío o inventado.

**[H12] Ficha PDF** (el "informe PDF" de la rúbrica)
- Desde el detalle de un artista, "Descargar PDF" genera su ficha: un PDF con los datos del artista, su foto y la lista de sus álbumes.

**[H13] API propia para apps externas**
- Una app externa obtiene un token con `POST /api/v1/tokens` (correo y contraseña) y con `Authorization: Bearer <token>` puede listar, ver, crear, actualizar y borrar los artistas del dueño del token (`GET`, `POST`, `PUT`, `DELETE` sobre `/api/v1/artists`).
- Sin token o con un token inválido, la API responde 401 en JSON. Con un token válido pero sobre un recurso ajeno, 404.
- El usuario puede crear (el valor se muestra una sola vez), ver y revocar sus tokens desde "Mi perfil".

**[H14] Consumo de un servicio REST externo con token**
- La opción "API externa" muestra los usuarios externos (registros de GoRest) creados desde la app (`GET` filtrado por el dominio de correo `@discoteca.test`), permite crear (`POST`, siempre con correo `@discoteca.test`), editar (`PUT`) y borrar (`DELETE`) uno de ellos, enviando `Authorization: Bearer` y `Accept: application/json` desde el servidor.
- GoRest solo deja modificar filas creadas con el mismo token: si responde 403, la pantalla dice "Ese usuario lo creó otra cuenta de GoRest; solo puedes editar los que creaste aquí".
- El token de GoRest vive en `.env` del servidor y nunca llega al navegador.
- Si GoRest no responde a tiempo o devuelve un error, la pantalla muestra el error real y un botón "Reintentar"; no se inventan datos.

**[H15] Publicación**
- La app responde en `https://laravel.helx.page` con HTTPS válido y el código está en un repositorio de GitHub con un README que explica cómo levantarla.

## 3. Delimitación de Alcance

### En alcance (V1)

- [ ] H1 a H15 completas, con datos reales del usuario.
- [ ] Seeder de demostración que crea un usuario de prueba con artistas y álbumes de verdad (discografías públicas), para la sustentación.
- [ ] Colección de Postman (o archivo `.http`) que demuestra la API propia con y sin token.

### Fuera de alcance (prohibido en V1)

- Roles y panel de administración.
- Verificación de correo y autenticación de dos factores.
- Catálogo público o compartido entre usuarios.
- DataTables del lado del servidor, exportar a Excel o CSV.
- Modo oscuro, i18n (la app va solo en español).
- Pistas o canciones como tercera tabla.
- Segundo gráfico (por década) y PDF del catálogo completo.
- `DELETE /api/v1/tokens/current` y la columna "último uso" de los tokens.
- Confirmación de borrado sin JS y páginas de error propias para 429 y 503.
- API propia para álbumes (la de artistas basta para la rúbrica; álbumes queda en el purgatorio).

## 4. Métricas de Éxito

- **Métrica principal**: los ítems de la rúbrica (lista de abajo) se demuestran en vivo sobre `https://laravel.helx.page` en menos de 12 minutos, siguiendo un guion de sustentación escrito (`docs/GUION.md`, responsable: integrante C, ver §6).
- **Ítems de la rúbrica** (el guion los recorre en este orden o dice dónde salta):
  1. Laravel 12.
  2. Equipo de 3.
  3. Vistas que heredan de una maestra.
  4. Base de datos PostgreSQL.
  5. Landing page.
  6. Formulario propio de registro y login.
  7. Rutas protegidas.
  8. Base de datos creada por migraciones.
  9. Modelos y controladores coherentes.
  10. Restaurar contraseña.
  11. Dashboard con el usuario activo.
  12. Menú con logout correcto.
  13. DataTables en dos tablas.
  14. Editar perfil.
  15. Al menos un chart.
  16. CRUD de al menos dos tablas.
  17. Ruta API consumible desde fuera.
  18. Informe PDF.
  19. Subir imágenes.
  20. Consumir un servicio REST con GET, POST, PUT y DELETE.
  21. Tokens, cabeceras y autorización.
  22. Publicado en internet.
  23. Copia en GitHub.
- **Criterio de aceptación**:
  - `php artisan test` en verde, con al menos un test de feature por historia H2 a H14.
  - Cada historia tiene su compuerta en `06_GATES.md` con `CHECK:` y `EXPECT:` y evidencia registrada.
  - Ensayo completo de la sustentación hecho sobre producción a más tardar el 2026-11-18.

## 5. Supuestos a Validar

1. **Dominio Artistas y Álbumes**: se toma por aprobado con la Etapa 0. Si el equipo tiene otro tema, este es el último momento barato para cambiarlo.
2. **Catálogo por usuario** (cada uno ve solo lo suyo) en lugar de un catálogo compartido. Da sentido a las Policies y a los 403 de H5 y H13. El costo: el seeder tiene que crear los datos de demostración a nombre de un usuario.
3. **La API propia cubre solo Artistas**. La rúbrica pide "al menos una ruta API"; cinco endpoints de un recurso más la emisión de token sobran.

## 6. Reparto del Equipo

Cada integrante implementa y defiende en la sustentación sus historias. Nombres pendientes del usuario.

| Integrante | Historias | Defiende en la sustentación |
| :--- | :--- | :--- |
| A (pendiente) | H1, H2, H3, H4, H5, H6, H10 | Landing, registro, login, reset, rutas protegidas, dashboard y perfil con tokens |
| B (pendiente) | H7, H8, H9, H12 | CRUD de Artistas y Álbumes con imágenes, DataTables y PDF |
| C (pendiente) | H11, H13, H14, H15 | Gráfico, API propia con Sanctum, consumo de GoRest, despliegue y guion |

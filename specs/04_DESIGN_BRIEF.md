---
document: 04_DESIGN_BRIEF
stage: 4
status: APPROVED
version: 1.3.0
last_updated: 2026-10-24
approved_by: human
depends_on: [03_APP_FLOW]
---

# 04. Design Brief y Sistema Visual

Hoja implementable para el equipo. Identidad propia de Discoteca (no hereda EStiri).

**Cambios**: 1.2.0 (2026-10-24): Josefin Slab en títulos, pedido del usuario el 2026-10-24.

> v1.3.0 (2026-10-24): corregido según `REVIEW_05-06.md` (M10 glosario y landing; A6 y M7 en los pendientes de §10, que ya tienen compuerta).

Artefactos de apoyo:

- `specs/design/prototype.html`: maqueta estática del riel lateral, la lista de artistas con estilo DataTables, el estado vacío, los estados de botón y el diálogo de borrado. No carga DataTables ni Chart.js reales (ver §10).
- `specs/design/contrast.mjs`: mide el contraste WCAG de los tokens del prototipo. Ya dio 0 fallas; solo se vuelve a correr si cambia un color.

## 1. Dirección

- **Idea**: una batea de tienda de discos ordenada. Fondos fríos y lisos como la funda interior de plástico, un riel lateral negro como el vinilo y un solo color cálido: el amarillo de la etiqueta central del disco (la "galleta"). Fotos y portadas cuadradas como las fundas; avatares circulares como la galleta.
- **Tono**: utilitario y legible a distancia, porque la app se proyecta en un salón: contraste alto, texto de 16 px como mínimo y ningún adorno que no informe. Pantallas con la misma estructura (riel, cabecera, panel) para que el recorrido de la sustentación se lea igual en todas.
- **Prohibido**: métricas, testimonios, logos de clientes o capturas inventadas en la landing; tarjetas dentro de tarjetas; degradados, brillos, `hover:scale`, fade-up al hacer scroll; imágenes de relleno o servicios de placeholders.

## 2. Paleta (OKLCH)

Fuente única: el bloque de §9. El hex es la conversión exacta y solo se usa donde OKLCH no llega (Chart.js y dompdf). Todos los colores están dentro del gamut sRGB.

| Rol | Token | OKLCH | Hex | Uso |
| :--- | :--- | :--- | :--- | :--- |
| Fondo de página | `--color-canvas` | `oklch(96.6% 0.006 255)` | `#f1f4f8` | 60 % de la pantalla |
| Panel | `--color-surface` | `oklch(99.2% 0.003 255)` | `#fbfdfe` | Paneles, tablas, formularios, diálogo |
| Hundido | `--color-sunken` | `oklch(93.6% 0.009 255)` | `#e6eaf0` | Hover de secundario y paginación; botón deshabilitado |
| Separador | `--color-rule` | `oklch(88.5% 0.009 255)` | `#d5d9df` | Línea bajo cada fila (decorativa) |
| Borde de control | `--color-control` | `oklch(55% 0.016 255)` | `#6b727b` | Bordes de inputs y botón secundario |
| Texto | `--color-ink` | `oklch(22% 0.016 258)` | `#161b22` | Títulos y lectura; borde del primario; anillo de foco |
| Texto secundario | `--color-ink-2` | `oklch(45% 0.018 258)` | `#4f565f` | Metadatos, ayudas, encabezados de tabla; barras del gráfico |
| Riel | `--color-vinyl` | `oklch(21% 0.016 258)` | `#141920` | Menú lateral y recuadro técnico de GoRest |
| Riel activo | `--color-vinyl-2` | `oklch(29% 0.018 258)` | `#262c34` | Ítem activo y hover del menú |
| Texto sobre riel | `--color-on-vinyl` | `oklch(95% 0.006 255)` | `#eceff2` | Nombre del usuario, ítem activo |
| Texto secundario sobre riel | `--color-on-vinyl-2` | `oklch(76% 0.014 255)` | `#abb2ba` | Ítems en reposo, correo |
| Acento (galleta) | `--color-accent` | `oklch(86% 0.165 95)` | `#f3cf31` | Botón primario, página actual, marca del ítem activo, galleta |
| Acento presionado | `--color-accent-strong` | `oklch(80% 0.155 88)` | `#e7b726` | Hover y active del primario |
| Acento suave | `--color-accent-soft` | `oklch(96% 0.05 98)` | `#faf3cd` | Fondo del token mostrado una vez |
| Peligro | `--color-danger` | `oklch(50% 0.185 27)` | `#b51f1f` | Errores, Eliminar |
| Peligro presionado | `--color-danger-strong` | `oklch(44% 0.17 27)` | `#9b1014` | Hover y active de Eliminar |
| Peligro suave | `--color-danger-soft` | `oklch(95.5% 0.018 27)` | `#fcecea` | Resumen de errores, flash de error |
| Éxito | `--color-success` | `oklch(46% 0.11 150)` | `#1d6835` | Texto del flash de éxito |
| Éxito suave | `--color-success-soft` | `oklch(96% 0.03 150)` | `#e4f8e7` | Fondo del flash de éxito |
| Foco | `--color-focus` | = ink | `#161b22` | `:focus-visible` en fondos claros |
| Foco sobre riel | `--color-focus-on-vinyl` | = accent | `#f3cf31` | `:focus-visible` en el riel y el recuadro técnico |
| Telón | `--color-backdrop` | `oklch(21% 0.016 258 / 0.55)` | n/a | `dialog::backdrop` |

**60-30-10**: 60 % canvas y surface; 30 % tinta, riel y bordes; 10 % amarillo, solo en lo accionable o lo actual (un botón primario por pantalla, la página actual, el ítem activo, la galleta). El amarillo nunca va como texto sobre fondo claro ni como fondo de una sección.

### Contraste medido

Salida de `node specs/design/contrast.mjs` (WCAG 2.x, calculado desde OKLCH). Al texto principal se le exige 7:1 porque el proyector lava el contraste.

| Texto / elemento | Fondo | Razón | Mínimo | Resultado | Uso |
| :--- | :--- | ---: | ---: | :--- | :--- |
| `--color-ink` | `--color-canvas` | 15.69:1 | 7:1 | PASA | Texto principal sobre el fondo de página |
| `--color-ink` | `--color-surface` | 16.92:1 | 7:1 | PASA | Texto principal en tablas, formularios y paneles |
| `--color-ink` | `--color-sunken` | 14.35:1 | 4.5:1 | PASA | Texto sobre hover de fila/botón secundario |
| `--color-ink-2` | `--color-canvas` | 6.74:1 | 4.5:1 | PASA | Metadatos, ayudas, info de DataTables |
| `--color-ink-2` | `--color-surface` | 7.27:1 | 4.5:1 | PASA | Encabezados de tabla, texto secundario en paneles |
| `--color-ink-2` | `--color-sunken` | 6.16:1 | 4.5:1 | PASA | Texto de botón deshabilitado (informativo) |
| `--color-ink` | `--color-accent` | 11.33:1 | 4.5:1 | PASA | Texto del botón primario y página actual |
| `--color-ink` | `--color-accent-strong` | 9.20:1 | 4.5:1 | PASA | Botón primario en hover/active |
| `--color-ink` | `--color-accent-soft` | 15.47:1 | 7:1 | PASA | Token de API mostrado una vez |
| `--color-on-vinyl` | `--color-vinyl` | 15.31:1 | 7:1 | PASA | Texto del riel lateral (nombre, item activo) |
| `--color-on-vinyl-2` | `--color-vinyl` | 8.26:1 | 4.5:1 | PASA | Items del menú en reposo, correo del usuario |
| `--color-on-vinyl` | `--color-vinyl-2` | 12.20:1 | 4.5:1 | PASA | Item del menú activo / hover |
| `--color-ink` | `--color-accent` | 11.33:1 | 4.5:1 | PASA | Iniciales en la galleta (avatar) |
| `--color-danger` | `--color-surface` | 6.46:1 | 4.5:1 | PASA | Enlace "Eliminar" y errores de campo |
| `--color-danger` | `--color-canvas` | 5.99:1 | 4.5:1 | PASA | Errores sobre el fondo de página |
| `--color-danger` | `--color-danger-soft` | 5.76:1 | 4.5:1 | PASA | Resumen de errores / flash de error |
| `--color-surface` | `--color-danger` | 6.46:1 | 4.5:1 | PASA | Texto del botón Eliminar |
| `--color-surface` | `--color-danger-strong` | 8.29:1 | 4.5:1 | PASA | Botón Eliminar en hover/active |
| `--color-success` | `--color-success-soft` | 6.09:1 | 4.5:1 | PASA | Flash de éxito |
| `--color-success` | `--color-surface` | 6.62:1 | 4.5:1 | PASA | Texto de éxito sobre panel |
| `--color-control` | `--color-surface` | 4.74:1 | 3:1 | PASA | Borde de inputs y botón secundario (no texto) |
| `--color-control` | `--color-canvas` | 4.39:1 | 3:1 | PASA | Borde de inputs sobre el fondo de página (no texto) |
| `--color-focus` | `--color-canvas` | 15.69:1 | 3:1 | PASA | Anillo de foco sobre fondo de página (no texto) |
| `--color-focus` | `--color-surface` | 16.92:1 | 3:1 | PASA | Anillo de foco sobre panel (no texto) |
| `--color-focus-on-vinyl` | `--color-vinyl` | 11.60:1 | 3:1 | PASA | Anillo de foco y marca activa en el riel (no texto) |
| `--color-ink` | `--color-surface` | 16.92:1 | 3:1 | PASA | Borde del botón primario sobre panel (no texto) |
| `--color-ink-2` | `--color-surface` | 7.27:1 | 3:1 | PASA | Barras de Chart.js sobre el panel (no texto) |
| `--color-rule` | `--color-surface` | 1.38:1 | n/a | PASA | Separador de filas (decorativo, sin mínimo) |

## 3. Tipografía

Dos familias, autoalojadas con Fontsource (npm) e importadas en `resources/css/app.css`: Vite las empaqueta y salen del mismo origen. Subconjuntos que declaran los paquetes (5.3.0): Josefin Slab, solo `latin` (un woff2 variable de 20.5 KB, pesos 100-700); Atkinson Hyperlegible Next, `latin` y `latin-ext` con `unicode-range`, y el navegador solo descarga el que necesita. El español (á, é, ñ, ü, ¿, ¡) cabe en `latin`.

| Rol | Familia | Paquete | Pesos |
| :--- | :--- | :--- | :--- |
| Títulos | Josefin Slab (pedido del usuario) | `@fontsource-variable/josefin-slab` 5.3.0: un solo archivo; la versión estática necesitaría dos (600 y 700, 23.3 KB) | 600 y 700; nada por debajo de 600, porque sus trazos finos se pierden en el proyector |
| Texto y UI | Atkinson Hyperlegible Next (distingue 0/O y 1/l: correos, tokens y años proyectados) | `@fontsource-variable/atkinson-hyperlegible-next` 5.3.0 | variable; se usan 400, 500, 700 |
| Mono (token, recuadro GoRest) | del sistema: `ui-monospace, "Cascadia Mono", Consolas, monospace` | ninguno | 400 |

| Rol | Tamaño | Familia y peso | Interlineado |
| :--- | :--- | :--- | :--- |
| Display (landing, código de error) | `clamp(2.75rem, 4vw + 1rem, 3.75rem)` | Josefin 600 | 1.1 |
| H1 de página y marca "Discoteca" | 2.25rem (36 px) | Josefin 600 | 1.15 |
| H2 (panel, diálogo) | 1.75rem (28 px) | Josefin 600 | 1.2 |
| H3 | 1.25rem (20 px) | Josefin 700 | 1.3 |
| Título de fila (nombre o título en tablas y grilla) | 1.125rem (18 px) | Josefin 700 | 1.2 |
| Cuerpo, celdas, inputs, botones | 1rem (16 px) | Atkinson 400; botones y labels 700 | 1.55 |
| Leyendas, encabezados de tabla, ayudas, mono | 0.8125rem (13 px) | Atkinson 400/700; mono 400 | 1.4 |

Josefin Slab tiene x-height baja y es más ancha que una condensada: por eso los títulos suben un paso respecto del texto (36/28 px) y los títulos de fila van a 18 px y no a 16.

**Títulos largos**:
- H1 y H2 (detalle del artista, diálogo): se ajustan completos, nunca se truncan: `text-wrap: balance` y `overflow-wrap: anywhere` (solo parte una palabra que no cabe sola en la línea).
- Título de fila en tablas: `min-inline-size: 10ch`, `max-inline-size: 26ch`, `overflow-wrap: break-word` y como máximo 2 líneas (`line-clamp: 2`). El texto completo sigue en el DOM: la búsqueda de DataTables y el lector de pantalla lo leen entero, y el detalle lo muestra completo.
- Grilla de portadas (160 px): título en 2 líneas como máximo con la misma regla; el año va aparte en Atkinson.

Reglas: títulos en redonda y en tipo oración (sin itálica, sin mayúsculas sostenidas). Párrafos a `max-width: 65ch`. `tabular-nums` en tablas, conteos, años y paginación. Inputs a 16 px como mínimo. Puntos suspensivos reales (`Guardando…`).

## 4. Espacio, radios e imágenes

- **Espaciado** (base de Tailwind, 4 px): dentro de un componente 4/8/12/16 px; entre bloques 24/32 px; ritmo de página 40/64 px (un corte de 96 px en la landing antes de la lista de funciones). En móvil (< 56rem): 24/40 px y 16 px de margen lateral.
- **Radios**: `--radius-sleeve` 2 px (fotos y portadas), `--radius-control` 4 px (botones, inputs, ítems del menú, paginación), `--radius-panel` 6 px (paneles, flash, diálogo). Avatares: círculo.
- **Elevación**: por paso tonal (canvas → surface; hover a sunken). La única sombra es `--shadow-dialog`, en el diálogo modal, que no lleva borde.
- **Imágenes**: fotos y portadas siempre 1:1 con `object-fit: cover`, `width` y `height` explícitos, `loading="lazy"` en listas. Tamaños: 40 px en tabla, 120 px en la vista previa del formulario, 240 px en el detalle del artista, 160 px en la grilla de álbumes. Foto del usuario: círculo de 40 px (riel) y 96 px (perfil).
- **La galleta** (componente `<x-galleta :name="..." />`): círculo amarillo con aro interior `accent-strong`, agujero de eje `vinyl` al centro y dos iniciales en mono `ink` en la mitad superior (inicial de las dos primeras palabras del nombre, en mayúscula). Se usa cuando falta la foto del usuario o del artista. Tamaño por clase: 40 px por defecto, `galleta--lg` 64 px (detalle sin foto). Siempre `aria-hidden="true"`: el nombre ya está escrito al lado.

## 5. Motion

Dos reglas:

1. Hover y `:active` con transición de 90 a 140 ms (`background-color`, `color`; `transform: translateY(1px)` al presionar), curva `cubic-bezier(0.22, 1, 0.36, 1)`. Hover solo dentro de `@media (hover: hover)`. Nunca `transition: all` ni animación del anillo de foco.
2. `@media (prefers-reduced-motion: reduce)` apaga todo: `transition: none` y `animation: none` en `*`, sin `translateY` al presionar y Chart.js con `animation: false`.

No hay más animaciones: el flash, el diálogo y el botón de carga aparecen sin transición. Tampoco hay skeletons: todo se renderiza en el servidor y la única espera (GoRest) ocurre antes de pintar.

## 6. Componentes y estados

En toda la app: `:focus-visible` = `outline: 2px solid var(--color-focus)` con `outline-offset: 2px` (`--color-focus-on-vinyl` dentro del riel y del recuadro técnico); nunca `outline: none` sin reemplazo; áreas táctiles de 44 x 44 px como mínimo.

| Componente | Reposo | Hover / active | Disabled | Loading | Error / vacío |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Botón primario (uno por pantalla) | fondo accent, texto ink 700, borde 1 px ink, alto 44 px | accent-strong; al presionar, +1 px | fondo sunken, texto ink-2, borde rule, `cursor: not-allowed`, sin hover | `disabled` + `aria-busy="true"` y el texto "Guardando…" (listener de `form[data-submit-once]`) | n/a |
| Botón secundario / peligro | secundario: surface con borde control; peligro: fondo danger, texto surface | sunken / danger-strong; +1 px | igual que primario | "Eliminando…" en el peligro | n/a |
| Input, select, textarea | surface, borde 1 px control, alto 44 px, label visible arriba (16 px 700, `for`), ayuda debajo en 13 px ink-2 con `min-height: 1lh` | hover: fondo canvas; el ancho del borde nunca cambia; foco: outline ink con `offset: 1px` | opacidad 0.55, `cursor: not-allowed` | n/a | borde danger; el mensaje reemplaza la ayuda en el mismo lugar; `aria-invalid="true"`, `aria-describedby`; resumen arriba del formulario con `role="alert"` y foco al primer error |
| Flash (`role="status"`) | panel success-soft, texto success 700, botón cerrar de 44 px; no se cierra solo | botón cerrar: fondo surface | n/a | n/a | variante de error: danger-soft + danger |
| Tabla | encabezado 13 px 700 ink-2 con regla de 1 px ink; filas con regla `rule` abajo; números a la derecha | fila con fondo canvas | n/a | llega completa del servidor | catálogo vacío: no se pinta la tabla (va el estado vacío); búsqueda sin coincidencias: "Ningún registro coincide con la búsqueda" |
| Estado vacío | panel surface: H2 que dice qué falta, una línea de por qué importa y un CTA primario | | | | |

El resto (ítems del menú, paginación, acciones de texto, diálogo, token mostrado una vez) sigue el mismo patrón: reposo con tokens del riel o del panel, hover con el paso tonal siguiente, foco con el anillo y disabled con `ink-2` y `cursor: not-allowed`. También se tematizan `::selection` (accent con texto ink), `caret-color`, `scrollbar-color`, `color-scheme: light` y el enlace "Saltar al contenido".

## 7. Pantallas

### `layouts.guest` (landing, auth, errores)

Fondo canvas, sin riel. Cabecera con "Discoteca" en Josefin 600 y un solo enlace a la derecha ("Iniciar sesión", o "Ir al dashboard" con sesión).

- **Landing**: alineada a la izquierda. H1 en display "Tus artistas y álbumes, en orden.", un párrafo de dos líneas, CTA primario "Crear cuenta" y enlace "Ya tengo cuenta". Tras el corte de 96 px, "Qué guarda Discoteca": lista de definiciones de 4 filas separadas por reglas (artistas con foto, álbumes con portada, año y género, gráfico por género, PDF por artista). Si se quiere una captura, va la real de la app terminada. Pie de una línea con el enlace al repo y `{{ now()->year }}`.
- **Login, registro, olvidé, reset**: columna de 26rem en un panel surface, campos con label visible, primario a todo el ancho y enlaces cruzados debajo. `autocomplete` correcto (`email`, `current-password`, `new-password`, `name`), `spellcheck="false"` en el correo, nunca bloquear el pegado. Mensajes de 03_APP_FLOW tal cual.
- **Errores propios (403, 404, 413, 419, 500)**: el código en display ink-2, H1 con la frase, una línea de qué hacer y un botón secundario de vuelta (dashboard con sesión, landing sin ella). 403 "No tienes acceso a esto"; 404 "No encontramos esta página"; 413 "El archivo es demasiado grande" + "Sube una imagen de hasta 2 MB"; 419 "Tu sesión expiró" + "Vuelve a cargar la página y repite la acción"; 500 "Algo falló de nuestro lado". El resto de códigos usa las páginas de Laravel.

### `layouts.app` (todo lo autenticado)

Riel de 15.5rem (vinilo, `position: sticky`, alto `100svh`) y contenido de `max-width: 72rem`. Riel: "Discoteca", bloque del usuario (foto circular o galleta, nombre y correo), menú de 6 ítems y "Cerrar sesión" (formulario POST). El ítem actual lleva `aria-current="page"`, fondo vinyl-2 y una marca de 3 px en accent. Debajo de 56rem el riel pasa arriba (`grid-template-rows: auto 1fr`) y el menú corre en una fila con scroll propio. Cada página abre con H1, una línea en ink-2 y su CTA primario a la derecha.

- **Dashboard**: "Hola, {nombre}" y dos bloques en fila, "Artistas" y "Álbumes", con el conteo real (Josefin 600, 36 px, cifras tabulares) y su enlace. Con 0 artistas, el estado vacío.
- **Listas**: "Nuevo artista" o "Nuevo álbum" (primario) y la tabla DataTables (§8) en un panel. Columnas de 03_APP_FLOW; imagen de 40 px; nombre como enlace al detalle; Editar y Eliminar como acciones de texto. Por debajo de 56rem se ocultan dos columnas y la tabla se desplaza en su contenedor. En álbumes sin artistas, el CTA del estado vacío lleva a crear un artista.
- **Formularios**: una columna de 40rem en un panel, campos a 24 px, "Guardar" (primario) y "Cancelar" (secundario). Año con `inputmode="numeric"` y ancho de 8ch. Archivo con `::file-selector-button` como secundario, el aviso "JPG, PNG o WebP, hasta 2 MB" y, en edición, la miniatura actual de 120 px.
- **Detalle del artista**: foto de 240 px (o galleta `--lg` en un cuadro sunken de 240 px) y datos al lado (H1, "País · Género", biografía a 65ch). Acciones: "Agregar álbum" (primario), "Editar" y "Descargar PDF" (secundarios), "Eliminar" (acción de texto en danger). Debajo, grilla de portadas de 160 px con título y año. Sin álbumes: "Este artista todavía no tiene álbumes" + "Agregar álbum".
- **Borrado**: el botón Eliminar abre un `<dialog>` con `showModal()`: "Vas a eliminar a {X} y sus {N} álbumes. No se puede deshacer.", foco inicial en Cancelar (`autofocus`), Esc cierra. Sin JS, el formulario DELETE se envía directo (no hay página de confirmación).
- **Estadísticas**: un solo panel, "Álbumes por género": barras horizontales en un contenedor de 20rem de alto y, debajo, la tabla de datos visible. Vacío: "Agrega álbumes para ver tus estadísticas" + CTA.
- **API externa (GoRest)**: "Nuevo usuario" (primario). Arriba, el recuadro técnico: panel vinilo sin barra de título falsa, mono 13 px on-vinyl, método en accent (`GET`), la URL completa, las cabeceras enviadas una por línea (`Authorization: Bearer abcd…wxyz`, `Accept: application/json`) y el código de respuesta ("200 OK"). Debajo, tabla simple (sin DataTables) con nombre, correo, género, estado y acciones, que muestra solo los usuarios creados por la app (correo `@discoteca.test`). Errores con el flash de error y "Reintentar"; los 422 junto a cada campo; 403 al editar o borrar: "Ese usuario lo creó otra cuenta de GoRest"; vacío: "Todavía no creaste usuarios externos" + "Crear uno".
- **Perfil**: tres paneles con su H2: "Tus datos" (nombre, correo, foto con vista previa circular de 96 px), "Contraseña" y "Tokens de API" (nombre, fecha de creación y "Revocar"; formulario de una línea para crear uno). Cada bloque muestra solo sus errores. Token recién creado: panel accent-soft con el token en mono, `user-select: all`, botón "Copiar" (dice "Copiado" con `aria-live="polite"`) y el aviso "Cópialo ahora: no se podrá ver de nuevo."

## 8. DataTables 2

- **Instalación**: `npm install datatables.net-dt@^2.3` (instala la 2.3.8). Sin el rango, npm instala la 3.x (`latest` es 3.1.4) y el TRD fija la 2.
- **CSS propio**: no se importa `dataTables.dataTables.css` (sus selectores son muy específicos y traen el azul `13, 110, 253`). `resources/css/datatables.css` (unas 80 líneas, las del prototipo) estiliza las clases de la 2.3.x: `.dt-container`, `.dt-layout-row`, `.dt-length`, `.dt-search`, `.dt-input`, `.dt-info`, `.dt-paging-button` (+ `.current`, `.disabled`), `table.dataTable`, `th.dt-orderable-*`, `th.dt-ordering-*`, `.dt-column-order` (flechas `↑`/`↓` según `aria-sort`), `.dt-type-numeric` y `.dt-empty`. Se carga solo en las vistas de lista.
- **Plan B** (si la prueba de §10 falla): importar `datatables.net-dt/css/dataTables.dataTables.css` y sobrescribir con unas 20 líneas (colores de `.dt-paging-button.current`, `.dt-input`, encabezado y filas).
- **Configuración** (`resources/js/datatables.js`):

```js
import DataTable from 'datatables.net-dt';

const es = {
  search: 'Buscar',
  lengthMenu: '_MENU_ filas por página',
  info: 'Mostrando _START_ a _END_ de _TOTAL_',
  infoEmpty: 'Sin resultados',
  infoFiltered: '(filtrado de _MAX_)',
  zeroRecords: 'Ningún registro coincide con la búsqueda',
  emptyTable: 'Sin registros',
  paginate: { first: 'Primera', last: 'Última', next: 'Siguiente', previous: 'Anterior' },
  aria: {
    orderable: 'Ordenar por esta columna',
    orderableReverse: 'Invertir el orden',
    paginate: { first: 'Primera página', previous: 'Página anterior', next: 'Página siguiente', last: 'Última página' },
  },
};

document.querySelectorAll('table[data-datatable]').forEach((table) => {
  new DataTable(table, {
    language: es,
    pageLength: 10,
    lengthMenu: [10, 25, 50],
    order: [[1, 'asc']],
    layout: { topStart: 'pageLength', topEnd: 'search', bottomStart: 'info', bottomEnd: 'paging' },
    columnDefs: [{ targets: [0, -1], orderable: false, searchable: false }], // imagen y acciones
  });
  // 2.3.8 fija aria-label="pagination" en el <nav> sin opción de idioma.
  table.closest('.dt-container')?.querySelector('.dt-paging nav')?.setAttribute('aria-label', 'Paginación');
});
```

- **Scroll a 375 px**: solo la celda de la tabla (`.dt-layout-table > .dt-layout-cell`) se desplaza, con `position: relative` para contener los `.visually-hidden` absolutos de las acciones; `.main` lleva `grid-template-columns: minmax(0, 1fr)` y los botones de paginación `flex: none`.

## 9. Tokens en el código (Tailwind 4) y Chart.js

`resources/css/app.css`:

```css
@import "tailwindcss";
@import "@fontsource-variable/josefin-slab";
@import "@fontsource-variable/atkinson-hyperlegible-next";

@theme {
  --color-*: initial;   /* sin la paleta por defecto: text-blue-500 deja de existir */
  --font-*: initial;
  --text-*: initial;
  --radius-*: initial;
  --shadow-*: initial;

  --color-canvas: oklch(96.6% 0.006 255);
  --color-surface: oklch(99.2% 0.003 255);
  --color-sunken: oklch(93.6% 0.009 255);
  --color-rule: oklch(88.5% 0.009 255);
  --color-control: oklch(55% 0.016 255);
  --color-ink: oklch(22% 0.016 258);
  --color-ink-2: oklch(45% 0.018 258);
  --color-vinyl: oklch(21% 0.016 258);
  --color-vinyl-2: oklch(29% 0.018 258);
  --color-on-vinyl: oklch(95% 0.006 255);
  --color-on-vinyl-2: oklch(76% 0.014 255);
  --color-accent: oklch(86% 0.165 95);
  --color-accent-strong: oklch(80% 0.155 88);
  --color-accent-soft: oklch(96% 0.05 98);
  --color-danger: oklch(50% 0.185 27);
  --color-danger-strong: oklch(44% 0.17 27);
  --color-danger-soft: oklch(95.5% 0.018 27);
  --color-success: oklch(46% 0.11 150);
  --color-success-soft: oklch(96% 0.03 150);
  --color-focus: var(--color-ink);
  --color-focus-on-vinyl: var(--color-accent);
  --color-backdrop: oklch(21% 0.016 258 / 0.55);

  --font-display: "Josefin Slab Variable", Rockwell, Georgia, serif;
  --font-body: "Atkinson Hyperlegible Next Variable", "Segoe UI", system-ui, sans-serif;
  --font-mono: ui-monospace, "Cascadia Mono", Consolas, monospace;

  --text-sm: 0.8125rem;
  --text-base: 1rem;
  --text-md: 1.125rem;
  --text-lg: 1.25rem;
  --text-xl: 1.75rem;
  --text-2xl: 2.25rem;
  --text-display: clamp(2.75rem, 4vw + 1rem, 3.75rem);

  --radius-sleeve: 2px;
  --radius-control: 4px;
  --radius-panel: 6px;
  --shadow-dialog: 0 1px 2px oklch(22% 0.016 258 / 0.18), 0 12px 32px oklch(22% 0.016 258 / 0.16);
  --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
}

/* Solo para JS: :root y no @theme, porque Tailwind 4 no emite variables de @theme que ninguna clase usa. */
:root {
  --chart-bar: #4f565f;        /* ink-2: 7.27:1 sobre el panel */
  --chart-bar-hover: #f3cf31;  /* accent */
  --chart-grid: #d5d9df;       /* rule */
  --chart-text: #4f565f;       /* ink-2 */
  --chart-tooltip: #161b22;    /* ink */
}

body { font-family: var(--font-body); background: var(--color-canvas); color: var(--color-ink); }
```

- Los hex de Chart.js van en `:root` y no en `@theme static`: así no generan clases `bg-chart-*` que nadie debe usar, y la documentación de Tailwind recomienda `:root` para variables sin utilidades.
- Chart.js toma la fuente con `getComputedStyle(document.body).fontFamily` y no de `--font-body`, así funciona aunque Tailwind no emita esa variable.
- Ningún atributo `style=""` en las vistas: los tamaños van por clases. Los datos del gráfico llegan en `<script type="application/json">`.

`resources/js/stats.js`:

```js
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip } from 'chart.js';
Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

const css = getComputedStyle(document.documentElement);
const t = (name) => css.getPropertyValue(name).trim();
const data = JSON.parse(document.getElementById('stats-data').textContent);

Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.font.size = 13;
Chart.defaults.color = t('--chart-text');

new Chart(document.getElementById('chart-genres'), {
  type: 'bar',
  data: { labels: data.labels, datasets: [{
    data: data.values,
    backgroundColor: t('--chart-bar'),
    hoverBackgroundColor: t('--chart-bar-hover'),
    hoverBorderColor: t('--chart-text'), hoverBorderWidth: 1,
    borderRadius: 0, maxBarThickness: 40,
  }] },
  options: {
    indexAxis: 'y',                                   // géneros en horizontal: etiquetas largas legibles
    responsive: true, maintainAspectRatio: false,
    animation: matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 140 },
    plugins: {
      legend: { display: false },                     // una sola serie
      tooltip: { backgroundColor: t('--chart-tooltip'), padding: 10, cornerRadius: 4, displayColors: false },
    },
    scales: {
      x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: t('--chart-grid') }, title: { display: true, text: 'Álbumes' } },
      y: { grid: { display: false } },
    },
  },
});
```

El `<canvas>` lleva `role="img"` y un `aria-label` generado en Blade ("Álbumes por género: Rock 5, Salsa 2…"); la tabla de datos queda debajo.

### PDF por artista (dompdf)

dompdf entiende CSS 2.1 (sin flex, grid, variables ni `oklch()`). La vista tiene su propio `<style>` con hex y maquetación por tablas.

- A4 vertical, `@page { margin: 18mm 16mm 20mm; }`, fuente `DejaVu Sans` en todo el PDF (viene con dompdf y cubre acentos y ñ). El PDF no usa Josefin Slab: dompdf no lee woff2, y registrar un TTF (OFL, del repositorio de Google Fonts) exige `load_font` y una carpeta de fuentes escribible en un contenedor `read_only`. La coherencia con la app la dan los colores, la franja amarilla y la estructura.
- Colores: texto `#161b22`, secundario `#4f565f`, reglas `#d5d9df` y una franja amarilla `#f3cf31` de 4 pt bajo el encabezado.
- Encabezado: "Discoteca" (bold, 18 pt) a la izquierda; título y fecha (9 pt, secundario) a la derecha.
- Ficha: tabla de dos columnas con la foto de 120 px (ruta de disco, `isRemoteEnabled=false`) y los datos (nombre en 20 pt bold, país y género, biografía en 10 pt). Sin foto: las iniciales en un recuadro con borde `#d5d9df`.
- Álbumes: tabla con portada de 40 px, título, año (a la derecha) y género; encabezado en 9 pt bold con borde inferior de 1 pt `#161b22`; filas con borde de 0.5 pt `#d5d9df` y `page-break-inside: avoid`.
- Pie fijo: "Discoteca · {usuario} · Página N" con `counter(page)`.

## 10. Verificación

- Hecho: `contrast.mjs` (28 pares, 0 fallas) y el checker de deliberate en `--strict` sobre el prototipo (0 hallazgos). Las capturas del prototipo a 1280, 375 y 320 px se tomaron durante el diseño pero no quedaron registradas en el repo: no cuentan como evidencia.
- **En el GATES (F14)**: prueba con DataTables 2.3.8 real sobre los 12 álbumes del seeder (2 páginas de 10) (búsqueda, `select` de filas, flechas de orden, paginación), capturas guardadas a 1280 y 375 px, y el `<dialog>` abierto y cerrado solo con teclado (Tab, Enter, Esc). Si el CSS propio no aguanta, se aplica el plan B de §8.
- En el GATES (F8): un PDF con una portada WebP de 2000 px (dompdf necesita GD con soporte WebP).

## 11. Supuestos a validar

1. **Amarillo de etiqueta como acento y riel oscuro**: el amarillo solo funciona como relleno con texto oscuro y borde de tinta. Si se quiere un acento que también sirva como color de texto, hay que rehacer la paleta.
2. **WebP en el PDF**: si GD no viene con WebP en el contenedor, las portadas WebP no salen en el PDF; habría que convertirlas a JPG al subirlas.

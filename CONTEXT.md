# Discoteca

Catálogo personal de música: cada usuario registra a sus artistas y los álbumes de cada uno, con imágenes, y consulta su colección en tablas, un gráfico y fichas imprimibles.

## Catálogo

**Usuario**:
Persona registrada que es dueña de un catálogo. Solo ve y modifica lo suyo.
_Evitar_: cuenta, cliente, miembro

**Catálogo**:
El conjunto de artistas y álbumes de un usuario. No hay catálogo compartido.
_Evitar_: colección (en la UI se usa como sinónimo informal, no en el código), biblioteca

**Artista**:
Músico o agrupación que el usuario registra en su catálogo. Su nombre no se repite dentro del mismo catálogo.
_Evitar_: banda, cantante, autor

**Álbum**:
Obra publicada de un artista, con título, año y género. Siempre pertenece a un único artista, y su dueño es el dueño de ese artista.
_Evitar_: disco, LP, lanzamiento

**Género**:
Clasificación musical tomada de una lista cerrada. Un artista tiene un género principal; cada álbum tiene el suyo.
_Evitar_: estilo, categoría

**Foto**:
Imagen de un artista o del usuario.
_Evitar_: avatar (salvo para la del usuario en la UI), imagen de perfil

**Portada**:
Imagen de un álbum.
_Evitar_: carátula, cover

**Ficha**:
Documento PDF de un artista con sus datos, su foto y la lista de sus álbumes.
_Evitar_: reporte, informe (la rúbrica dice "informe"; en el producto es la ficha)

## Acceso e integración

**Token de API**:
Credencial personal que un usuario crea para que una aplicación externa acceda a su catálogo. Se muestra completa una sola vez.
_Evitar_: clave, API key, llave

**Usuario externo**:
Registro en el servicio público GoRest que la app crea, edita y borra para demostrar el consumo de un servicio REST. No tiene relación con los usuarios del catálogo.
_Evitar_: usuario de GoRest (ambiguo con Usuario), contacto

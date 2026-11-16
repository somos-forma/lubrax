# Lubrax · Documentación del sitio y del tema

Block theme de WordPress construido a partir de la maqueta estática `maqueta-lubrax/` (HTML + Bootstrap 5.3 + JS).
Este documento explica **qué se construyó, dónde está cada cosa y cómo se modifica**, para el equipo editorial y para el equipo de desarrollo.

- **Sitio local:** `http://lubrax.test` (Laragon, carpeta `c:\laragon\www\lubrax`, base `lubrax_db`).
- **Tema:** `wp-content/themes/lubrax/`.
- **Plugins requeridos:** WooCommerce (solo catálogo), Secure Custom Fields (SCF), Contact Form 7 y Contact Form CFDB7.
- **Plugin SEO:** opcional (Yoast SEO o Rank Math). El tema ya cubre lo básico si no hay ninguno; ver [SEO](#11-seo).

---

## Índice

**Parte A · Equipo editorial**

1. [Mapa del sitio y dónde se edita cada página](#1-mapa-del-sitio-y-dónde-se-edita-cada-página)
2. [Opciones del tema](#2-opciones-del-tema)
3. [Header, footer y menús](#3-header-footer-y-menús)
4. [Productos (catálogo)](#4-productos-catálogo)
5. [Distribuidores y mapa](#5-distribuidores-y-mapa)
6. [Documentación técnica (PDF)](#6-documentación-técnica-pdf)
7. [Inicio, Sobre Lubrax y bloques editables](#7-inicio-sobre-lubrax-y-bloques-editables)
8. [Blog](#8-blog)
9. [Contacto, formularios y newsletter](#9-contacto-formularios-y-newsletter)
10. [Página 404 y buscador interno](#10-página-404-y-buscador-interno)
11. [SEO](#11-seo)
12. [Importar productos y distribuidores desde Excel](#12-importar-productos-y-distribuidores-desde-excel)
13. [Roles y permisos](#13-roles-y-permisos)

**Parte B · Equipo de desarrollo**

14. [Estructura de archivos del tema](#14-estructura-de-archivos-del-tema)
15. [Módulos PHP (`/inc`)](#15-módulos-php-inc)
16. [Bloques (`/blocks`)](#16-bloques-blocks)
17. [Plantillas, partes y patrones](#17-plantillas-partes-y-patrones)
18. [CSS y JavaScript](#18-css-y-javascript)
19. [Campos personalizados (SCF / acf-json)](#19-campos-personalizados-scf--acf-json)
20. [WooCommerce en modo catálogo](#20-woocommerce-en-modo-catálogo)
21. [Datos estructurados (Schema)](#21-datos-estructurados-schema)
22. [Recetas: cómo hacer cambios frecuentes](#22-recetas-cómo-hacer-cambios-frecuentes)
23. [Paso a producción](#23-paso-a-producción)
24. [Entorno local y problemas conocidos](#24-entorno-local-y-problemas-conocidos)
25. [Activar las ventas en WooCommerce (salir del modo catálogo)](#25-activar-las-ventas-en-woocommerce-salir-del-modo-catálogo)

---

# Parte A · Equipo editorial

## 1. Mapa del sitio y dónde se edita cada página

| Página | URL | Se edita en | Plantilla |
|---|---|---|---|
| Inicio | `/` | Páginas → Inicio (bloques) | Página con patrones (ancho completo) |
| Productos (catálogo) | `/productos/` | Productos; textos en el editor del sitio (plantilla) | `archive-product` |
| Segmento (ej. Automotriz) | `/segmento/automotriz/` | Productos → Segmentos | `taxonomy` |
| Ficha de producto | `/productos/{producto}/` | Productos → editar producto | `single-product` |
| Distribuidores | `/distribuidores/` | Páginas → Distribuidores + Distribuidores (CPT) | Página con patrones |
| Documentación técnica | `/documentacion-tecnica/` | Páginas → Documentación técnica + PDF de cada producto | Página con patrones |
| Quiénes somos | `/nosotros/` | Páginas → Quiénes somos (bloques) | Página con patrones |
| Blog | `/blog/` | Entradas | `home` |
| Categoría / etiqueta del blog | `/category/…/`, `/tag/…/` | Entradas → Categorías / Etiquetas | `category`, `tag` |
| Artículo | `/{articulo}/` | Entradas → editar entrada | `single` |
| Contacto | `/contacto/` | Páginas → Contacto (bloques) + Contacto (CF7) | Página con patrones |
| Términos / Políticas | `/terminos/`, `/politicas/` | Páginas → bloque *Documento legal* ([ver 7.1](#71-páginas-legales-términos-y-políticas)) | Página con patrones |
| Búsqueda | `/?s=texto` | Automática | `search` |
| Error 404 | cualquier URL inexistente | **Opciones del tema → Página 404** | `404` |

**Regla general:** las páginas con diseño (Inicio, Quiénes somos, Contacto, Distribuidores, Documentación) usan la plantilla **"Página con patrones (ancho completo)"**. Cada sección es un bloque, y sus textos se editan en la **barra lateral derecha** del editor, sin tocar HTML. Si un campo de un bloque queda vacío, el bloque muestra el texto de la maqueta.

**Imagen del encabezado de una página:** es la **imagen destacada** de la página. Si no tiene, se usa una imagen por defecto. La **bajada** del encabezado es el campo "Bajada" del bloque *Encabezado de página*; si está vacío, se usa el **extracto** de la página.

---

## 2. Opciones del tema

Menú lateral del escritorio → **Opciones del tema** (solo administradores). Organizado en pestañas:

| Pestaña | Campos | Dónde se ven |
|---|---|---|
| **Contacto** | Teléfono, correo, WhatsApp, nota bajo cada canal, dirección | Footer, menú lateral, canales de la página Contacto, Schema Organization |
| **Redes sociales** | Instagram, Facebook, YouTube, LinkedIn | Footer y Schema. **Una URL vacía oculta la red.** |
| **Footer** | Texto del footer | Columna izquierda del footer |
| **Formularios** | Correos de las áreas Comercial, Técnica y Distribuidores; formulario de contacto y de newsletter | Enrutamiento del formulario ([sección 9](#9-contacto-formularios-y-newsletter)) |
| **Banner del catálogo** | Mostrar u ocultar, título, descripción, enlace, imagen, color de fondo y color del texto (claro u oscuro) | Tarjeta bajo los filtros del catálogo y de cada segmento ([sección 4](#4-productos-catálogo)) |
| **Página 404** | Antetítulo, título, texto, botón «Volver atrás», segundo botón, texto del buscador, título y lista de enlaces útiles, descripción para buscadores | Página de error 404 ([sección 10](#10-página-404-y-buscador-interno)) |
| **Mapa** | URL de teselas, atribución y zoom máximo | Mapa de distribuidores |

Los valores por defecto (los de la maqueta) están en `inc/theme-options.php` → `lbx_option_defaults()`.

---

## 3. Header, footer y menús

- **Menús:** Apariencia → Menús (o el editor del sitio). Ubicaciones:
  | Ubicación | Dónde aparece |
  |---|---|
  | Header: menú principal | Barra superior |
  | Menú lateral: enlaces | Menú lateral (botón ☰), enlaces grandes |
  | Menú lateral: enlaces secundarios | Menú lateral, enlaces pequeños |
  | Footer: Explora | Fila "Explora" del footer |
  | Footer: enlaces legales | Última fila del footer |

  Si una ubicación no tiene menú asignado, el tema muestra los enlaces de la maqueta.
- **Botón del header** ("Quiero ser distribuidor"): Apariencia → Editor → Partes de plantilla → Header → bloque *Encabezado Lubrax* → barra lateral: mostrar u ocultar, texto y motivo del formulario (`?tipo=`).
- **Newsletter del footer:** Partes de plantilla → Footer → bloque *Pie de página Lubrax*: mostrar u ocultar y título.
- **Selector de distribuidor o región del header:** se llena solo con los distribuidores publicados.

---

## 4. Productos (catálogo)

WooCommerce funciona **solo como catálogo**: no hay precios, carrito ni pago. El botón de compra es **"Cotizar volumen"**, que lleva al formulario de contacto con el producto ya elegido.

### Crear o editar un producto (Productos → Añadir nuevo)

| Dato | Dónde |
|---|---|
| Nombre, descripción larga | Editor principal |
| Descripción corta (bajada de la ficha y meta description) | Caja "Descripción corta del producto" |
| Imagen principal y galería | Imagen del producto / Galería |
| **Segmento**, Tipo de producto, Especificación, Base, Combustible, Viscosidad | Cajas de taxonomía en la barra lateral |
| Destacado (aparece en el Inicio) | Estrella en el listado de Productos |
| Presentaciones (envase + imagen) | Campos "Ficha técnica del producto" → pestaña **Ficha** |
| Aplicaciones recomendadas, normas y homologaciones | Pestaña **Ficha** |
| Propiedades típicas (tabla) | Pestaña **Propiedades típicas** |
| Ficha técnica y hoja de seguridad (PDF) | Pestaña **Documentos** |
| Distribuidores que lo venden | Pestaña **Distribuidores** (relación bidireccional) |
| Productos relacionados (máx. 3) | Pestaña **Relacionados**. Si queda vacía, se usan productos del mismo segmento. |

### Taxonomías

| Taxonomía | URL | Uso |
|---|---|---|
| Segmento | `/segmento/{slug}/` | Categorías del catálogo (con ícono y orden propios) |
| Tipo de producto | `/tipo-de-producto/{slug}/` | Clasificación (aceite de motor, hidráulico…) |
| Especificación técnica | `/especificacion/{slug}/` | Normas (API SP, ACEA E9…); filtro del catálogo |
| Base | `/base/{slug}/` | Mineral / semisintética / sintética; filtro |
| Combustible | `/combustible/{slug}/` | Gasolina / diésel; filtro |
| Viscosidad | `/viscosidad/{slug}/` | SAE / ISO VG; filtro y buscador del Inicio |

- **Segmentos** (Productos → Segmentos): ícono (del tema o una imagen propia), **orden** (menor primero) y descripción (bajada del listado y meta description).
- Las categorías y etiquetas propias de WooCommerce están ocultas a propósito.

### Catálogo (`/productos/`)

Los filtros, la búsqueda, el orden, la vista y la paginación (9 por página) funcionan en el navegador, igual que la maqueta. La URL guarda el estado: `?combustible=diesel&sae=15W-40&q=…`. El título y la banda final se editan en la plantilla **Archivo de productos** (Apariencia → Editor → Plantillas).

**Banner de la columna de filtros** (tarjeta con imagen bajo los filtros, en el catálogo y en cada segmento): **Opciones del tema → Banner del catálogo**.
- Se puede mostrar u ocultar y cambiar título, descripción, enlace (toda la tarjeta es el enlace), imagen y color de fondo.
- **Color del texto:** «Claro» para fondos oscuros y «Oscuro» para fondos claros, como el amarillo de la marca. Elige el que contraste con el fondo.
- **Imagen:** un envase recortado con fondo transparente (PNG o WebP, unos 420 × 520 px) se ve como en la maqueta.
- Un campo vacío usa el contenido de la maqueta.

---

## 5. Distribuidores y mapa

Menú **Distribuidores** (tipo de contenido privado: no tiene página propia, se muestra en el mapa y en las fichas).

| Pestaña | Campos |
|---|---|
| General | Nombre corto (zona) para el header, tipo de compra, presentaciones, capacidad logística |
| Contacto | Teléfono, correo, sitio web, horario |
| Ubicación | Dirección, **Región**, **Comuna** (lista dependiente de la región), enlace de Google Maps, latitud y longitud |
| Productos | Portafolio (relación bidireccional con Productos) |

- **Región y comuna:** elige primero la región; la lista de comunas muestra solo las de esa región (las 346 comunas oficiales de Chile). Si eliges la comuna sin región, la región se completa sola. Al guardar se valida que la comuna pertenezca a la región.
- **Coordenadas:** si pegas un enlace de Google Maps que incluya coordenadas, latitud y longitud se completan solas al guardar. Sin coordenadas, el distribuidor aparece en el listado pero no en el mapa.
- **Página `/distribuidores/`:** bloque *Localizador de distribuidores* (vista mapa/listado, filtros por región, producto y texto, "cerca de mí" y ficha ampliada). Acepta `?producto={slug}&region={región}`.
- **Proveedor del mapa:** Opciones del tema → Mapa. En producción conviene un proveedor de teselas con clave (OpenStreetMap limita el uso intensivo).

---

## 6. Documentación técnica (PDF)

La página `/documentacion-tecnica/` (bloque *Documentación técnica*) reúne **todos los PDF cargados en los productos** (pestaña Documentos). No se administra aparte: al subir o quitar un PDF en un producto, se actualiza el repositorio. Filtros: producto, segmento, tipo de documento y texto. La cantidad por página se ajusta en la barra lateral del bloque.

> Para que la biblioteca de medios genere miniaturas de los PDF, el servidor necesita Imagick.

---

## 7. Inicio, Sobre Lubrax y bloques editables

### Bloques del tema (categoría "Lubrax" en el insertador)

| Bloque | Dónde se usa | Qué se edita |
|---|---|---|
| Slider principal | Inicio | Diapositivas (imagen, envase recortado, antetítulo, título, texto, botón), intervalo, H1 oculto para SEO |
| Catálogo regional | Inicio | Imagen, antetítulo, título (una línea por renglón), texto, botón, enlace |
| Categorías (segmentos) | Inicio | Antetítulo, título, enlace, fondo. Las categorías salen de Segmentos. |
| Buscador de productos | Inicio | Título. Las categorías y viscosidades salen del catálogo. |
| Productos destacados | Inicio | Antetítulo, título, cantidad, enlace. Muestra los productos con estrella. |
| Planta | Inicio | Textos, imágenes y tarjeta |
| Noticias | Inicio | Antetítulo, título, cantidad, enlace. Muestra las últimas entradas. |
| Banda con imagen y servicios | Quiénes somos | Fondo, servicios (ícono, título, texto), título destacado, botón |
| Preguntas frecuentes | Quiénes somos | Preguntas y respuestas, buscador, Schema FAQPage (interruptor) |
| Encabezado de página | Varias | Bajada, versión compacta, canales de atención, fecha de última actualización |
| Documento legal | Términos, Políticas | Texto con índice lateral (ver 7.1) |
| Banda de llamada a la acción | Varias | Título, texto, dos botones (texto y enlace) |
| Servicios de contacto | Contacto | Tarjetas con el motivo del formulario que eligen |
| Formulario de contacto | Contacto | Título, formulario de CF7, mensaje de éxito, recuadro lateral, preguntas de atención |

### Patrones (insertador → Patrones → "Lubrax: páginas" / "Lubrax: secciones")

- **Página de inicio**, **Página Sobre Lubrax**, **Página de contacto** y **Página legal**: la página completa, lista para usar.
- **Texto e imagen** y **Cifras**: secciones sueltas hechas con bloques de Gutenberg.

### Herramientas de diseño en bloques de Gutenberg

- **Botones:** estilos *Amarillo*, *Verde*, *Contorno amarillo* y *Contorno verde* (panel Estilos del bloque Botón).
- **Animación al aparecer:** en Avanzado → Clases CSS adicionales escribe `reveal-up`, `reveal-down`, `reveal-left`, `reveal-right`, `reveal-zoom` o `reveal-clip`.
- **Título animado por líneas:** clase `reveal-title`; cada línea (Mayús + Intro) entra por separado.
- **Contador animado:** un párrafo con clase `stat__value` y un número ("+180", "8") cuenta al aparecer.
- **Imagen con bordes redondeados:** clase `media-rounded`.

### 7.1 Páginas legales (Términos y Políticas)

Se arman con tres bloques: **Encabezado de página** (versión compacta y fecha de «Última actualización»), **Documento legal** y la banda final.

- **Escribir el texto:** dentro del bloque *Documento legal*, con bloques normales de Gutenberg (títulos, párrafos, listas, tablas).
  - **Cada título H2 abre una sección**: se numera sola (1., 2., 3.…) y aparece en el índice lateral, que resalta la sección visible al hacer scroll.
  - Lo que se escriba antes del primer H2 sale como introducción, sin número.
  - Los títulos H3 sirven para subtítulos dentro de una sección y no van al índice.
- **Estilos** (panel Estilos del bloque):
  - Párrafo **«Nota destacada»**: recuadro con ícono y borde amarillo.
  - Lista **«Lista con check»**: viñetas con check verde.
- **Sección «Contacto» al final:** se agrega sola. En la barra lateral del bloque se cambia su título, su texto y el correo (vacío: correo de atención); también se puede ocultar.
- **Fecha de actualización:** bloque *Encabezado de página* → «Última actualización». Si queda vacía, no se muestra.
- **Enlace de anclaje propio para una sección:** en el título H2 → Avanzado → Ancla HTML (por ejemplo `cookies`, para enlazar a `/politicas/#cookies`). Sin ancla, el id es `seccion-N`.
- **Nueva página legal:** crea la página, elige la plantilla «Página con patrones (ancho completo)» e inserta el patrón **Página legal**.
- La página de privacidad que enlaza el formulario de contacto es la de Ajustes → Privacidad (hoy, Políticas).
- El texto actual es el *lorem ipsum* de la maqueta: hay que reemplazarlo por los documentos legales del cliente.

---

## 8. Blog

- **Entradas:** título, extracto (bajada y meta description), imagen destacada, **una categoría principal** y etiquetas. El cuerpo se arma con bloques de Gutenberg.
- **Artículos populares** (barra lateral): casilla **"Artículo popular"** en la barra lateral de la entrada. Si ninguna está marcada, se muestran las más recientes.
- **Listado `/blog/`:** buscador, orden, chips de categoría, etiquetas y paginación de 6 en 6, en el navegador. Parámetros: `?q=`, `?etiqueta=`, `?orden=antiguos`.
- **Autor:** se muestra el "Nombre público" del usuario (hoy, "Equipo Lubrax").
- **Bajada del listado:** bloque *Listado del blog* en la plantilla Blog. En una categoría se usa su descripción.

---

## 9. Contacto, formularios y newsletter

Los formularios usan **Contact Form 7** (CF7) y los envíos se guardan con **CFDB7**.

### Dónde está cada cosa

| Qué | Dónde |
|---|---|
| Textos y campos del formulario | Contacto → Formularios de contacto → **Contacto** → pestaña Formulario |
| Correo al área y autorrespuesta al cliente | Mismo formulario → pestañas **Correo** y **Correo (2)** |
| Mensajes (éxito, errores) | Pestaña **Mensajes** |
| Destinatarios por área | **Opciones del tema → Formularios** |
| Envíos recibidos | Menú **Contact Forms** (CFDB7), con exportación a CSV |
| Turnstile o reCAPTCHA | Contacto → **Integración** |
| Textos de la sección (título, éxito, recuadro, preguntas) | Página Contacto → bloque *Formulario de contacto* |
| Tarjetas de servicios | Página Contacto → bloque *Servicios de contacto* |
| Canales (teléfono, correo, WhatsApp) | Opciones del tema → Contacto |

### Motivos y enrutamiento

| Grupo en el formulario | Motivo (`?tipo=`) | Área que recibe el correo |
|---|---|---|
| Comercial | `cliente`, `distribuidor`, `industrial`, `pedido` | Comercial |
| Servicios técnicos | `consulta`, `capacitacion`, `analisis` | Técnico |
| Postventa | `reclamo-producto`, `garantia` | Técnico |
| Postventa | `reclamo-servicio` | Distribuidores |
| Otros | `sugerencia` | Comercial |

- Cada área acepta **varios correos separados por coma**. Si un área queda vacía, el mensaje llega al correo de atención y, en último caso, al del administrador.
- Para enlazar al formulario con un motivo elegido: `/contacto/?tipo=garantia#formulario`. Para preseleccionar además un producto: `&producto={slug}`.

### Cómo funciona

1. Al elegir el motivo se muestran sus campos. Por ejemplo, un reclamo de producto pide el lote, y "Quiero ser distribuidor" pide el tipo de negocio.
2. Al enviar, el cliente ve **"¡Recibimos tu mensaje!"** con su **número de caso** (`LBX-AAAAMMDD-00001`).
3. Se envían dos correos: uno **al área** (con Reply-To del cliente y solo los campos que aplican) y una **autorrespuesta al cliente** con el número de caso.
4. El envío queda en **Contact Forms (CFDB7)** con caso, área, motivo y producto por su nombre.

### Etiquetas especiales (pestaña Correo)

| Etiqueta | Valor |
|---|---|
| `[_lbx_caso]` | Número de caso |
| `[_lbx_motivo]` | Motivo elegido ("Reclamo de producto") |
| `[_lbx_area]` | Área (Comercial, Técnico, Distribuidores) |
| `[_lbx_area_email]` | Correos del área (va en "Para") |
| `[_lbx_resumen]` | Tabla con los campos que aplican al motivo |

### Reglas al editar el formulario en CF7

- Un campo que depende del motivo va dentro de `<fieldset data-show-for="motivo1 motivo2" hidden disabled>`.
- Para que ese campo sea obligatorio **solo para esos motivos**, agrega `class:lbx-required` a la etiqueta, sin asterisco. Ejemplo: `[text lote id:cLot class:form-control class:lbx-required]`.
- Las etiquetas propias del tema son:
  - `[lbx_tipo* tipo]`: selector de motivos.
  - `[lbx_producto producto]`: productos del catálogo.
  - `[lbx_acepto* privacidad "Texto con {enlace}"]`: casilla de privacidad; el texto entre llaves enlaza a la política.
  - `[lbx_enviar class:btn-lbx-dark icon:send "Texto"]`: botón.
  - `[lbx_antispam]`: antispam. **Siempre debe quedar al final.**
- **No cambies los nombres** `tipo`, `producto` ni `privacidad`: el enrutamiento y el resumen dependen de ellos.

### Antispam

Campo trampa invisible, token firmado y un tiempo mínimo de llenado de 3 segundos. Para producción se recomienda activar **Turnstile** (Contacto → Integración). Mientras no esté activo, CF7 muestra un aviso en la autorrespuesta ("configuración insegura"); con Turnstile desaparece.

### Newsletter

Formulario **Newsletter** de CF7, en el footer. Las suscripciones solo se guardan en **Contact Forms (CFDB7)**: no envía correo (`skip_mail: on` en Ajustes adicionales).

---

## 10. Página 404 y buscador interno

- **404:** responde con **estado 404 real**, sin redirección, y con `noindex, follow`. Incluye "Volver atrás", "Ir al catálogo", buscador y enlaces útiles. **Todo se edita en Opciones del tema → Página 404**: textos, botones, enlaces útiles (repetidor) y descripción para buscadores. Un campo vacío usa el texto de la maqueta.
- **Buscador interno (`/?s=texto`):** muestra productos (también los encuentra por viscosidad, norma o segmento: "15W-40", "API CK-4"), artículos y páginas. Si hay más resultados de los visibles, ofrece "Ver todos en el catálogo" o "en el blog". La cantidad por grupo se edita en la plantilla **Resultados de búsqueda**.
- El buscador del menú lateral y el del Inicio buscan **en el catálogo** (`/productos/?q=`), como en la maqueta.

---

## 11. SEO

**Sin plugin SEO (situación actual), el tema genera:**

- **Etiqueta `<title>`:** "Página | Lubrax".
- **Meta description:** extracto de la página o entrada; en productos, la descripción corta; en segmentos y categorías, su descripción.
- **Redes sociales:** Open Graph y Twitter Card. Imagen: la destacada o, si no hay, `assets/img/og/og-lubrax.jpg`.
- **Canónicas:** WordPress las imprime en el contenido individual. En los listados las imprime el tema, sin los filtros (`?combustible=`, `?q=`…).
- **Sitemap:** `/wp-sitemap.xml`, con productos, entradas, páginas y taxonomías del catálogo. Sin usuarios ni categorías de WooCommerce.
- **robots.txt:** declara el sitemap. La búsqueda y la 404 llevan `noindex`.
- **Schema:** Product, BlogPosting, BreadcrumbList, FAQPage, Organization y WebSite.

**Cómo escribir buenas descripciones:** usa el **extracto** de cada página o entrada, y la **descripción corta** en los productos. Las páginas clave ya tienen el texto de la maqueta.

**Si se instala Yoast SEO o Rank Math:** el tema lo detecta y deja de imprimir description, OG, canónicas, Organization, WebSite, BreadcrumbList y BlogPosting, para no duplicarlos. **Mantiene Product y FAQPage**, que esos plugins no generan en su versión gratuita. Con Rank Math hay que revisar que su Schema de producto de WooCommerce no duplique el del tema.

---

## 12. Importar productos y distribuidores desde Excel

**Productos → Importar desde Excel**, o por terminal con `wp lubrax importar <archivo.xlsx> [--simular] [--estado=draft]`.

- **Archivo:** descarga la plantilla desde la misma pantalla del importador (`assets/import/plantilla-contenido-web.xlsx`). Tiene las hojas **Instrucciones**, **Productos** y **Distribuidores**, y una hoja oculta **Listas** con las opciones de las listas desplegables.
- **Listas desplegables:**
  - Columnas de una sola opción (Categoría, Tipo de producto, Combustible, Base, Destacado, Tipo de compra, Región, **Comuna**): solo aceptan valores de la lista.
  - **Comuna depende de la Región:** al elegir la región, la lista muestra solo sus comunas.
  - «Distribuido por» y «Portafolio de productos» ofrecen los nombres escritos en la otra hoja.
  - Las listas funcionan en Excel y LibreOffice. En Google Sheets no está la lista dependiente de comunas.
- **Excel con los datos actuales:** `plantilla-importacion-datos-prueba.xlsx`, en la raíz del sitio. Tiene los 18 productos y 10 distribuidores de prueba y sirve de ejemplo de llenado. Reimportarlo no duplica nada, porque los medios de la propia biblioteca se reutilizan.
- **Simulación:** muestra qué se crearía o actualizaría, sin guardar nada.
- **Coincidencias:** los productos se actualizan por SKU o por nombre, y los distribuidores por nombre. Productos y distribuidores se enlazan desde ambas hojas ("Distribuido por" / "Portafolio de productos").
- **Imágenes y PDF:** se aceptan enlaces de **Google Drive** o **Dropbox**. Se descargan en segundo plano (Herramientas → Acciones programadas, hook `lbx_import_media`) y no se duplican si ya se descargaron.
- **Varios valores en una celda:** sepáralos con coma o salto de línea (especificaciones, presentaciones…). En la plantilla, esas columnas ya permiten escribir varios valores además de elegir de la lista.
- **Comuna:**
  - Se valida contra la lista oficial y se guarda con su nombre oficial. Se aceptan nombres alternativos y sin tildes («Til Til», «Puerto Natales», «concepcion»).
  - Si no existe o no corresponde a la región, no se guarda y el informe lo indica.
  - Si falta la región, se deduce de la comuna. Si faltan ambas, se intentan deducir de la dirección.
- **Regenerar la plantilla** cuando cambien las listas (segmentos, viscosidades, especificaciones, envases): ver `tools/plantilla-excel/README.md`.

| Hoja Productos (encabezado) | Campo |
|---|---|
| Nombre Producto, SKU, Categoría (segmento), Tipo de producto | Datos básicos |
| Descripción, Descripción corta | Textos |
| Grado de viscosidad, Especificaciones, Base, Tipo combustible | Taxonomías |
| Presentación/Envase, Aplicaciones recomendadas, Normas y homologaciones | Ficha |
| Ruta/URL de imagen, Ficha técnica, Hoja de seguridad (SDS) | Medios |
| Distribuido por, Destacado | Relación y destacado |

| Hoja Distribuidores (encabezado) | Campo |
|---|---|
| Nombre Distribuidor, Zona, Tipo de compra | General |
| Dirección, Región, Comuna, Link google maps, Latitud, Longitud | Ubicación |
| Teléfono, Correo, Página web, Horario | Contacto |
| Portafolio de productos, Presentaciones que ofrece, Capacidad logística | Oferta |

---

## 13. Roles y permisos

| Rol | Puede | No puede |
|---|---|---|
| **Administrador Técnico** | Todo (mismas capacidades que el Administrador) | — |
| **Editor de Contenido** | Entradas, páginas, productos y sus taxonomías, distribuidores, patrones, medios; formularios de CF7 (CF7 lo permite a quien publica páginas) | Temas, plugins, ajustes, editor del sitio, Opciones del tema, envíos de CFDB7 |

Para que el Editor de Contenido vea los envíos de CFDB7, hay que darle la capacidad `cfdb7_access`.

---

# Parte B · Equipo de desarrollo

## 14. Estructura de archivos del tema

```
lubrax/
├── style.css, theme.json        Cabecera del tema; paleta, tipografías locales, estilos de elementos, plantillas personalizadas
├── functions.php                Solo carga los módulos de /inc (en orden)
├── DOCUMENTACION.md             Este documento
├── inc/                         Lógica PHP por responsabilidad (ver sección 15)
│   ├── importer/                Importador Excel (lector XLSX, importador, medios, pantalla y WP-CLI)
│   └── data/comunas-chile.php   16 regiones y 346 comunas de Chile
├── blocks/{bloque}/             block.json + render.php por bloque (registrados automáticamente)
├── templates/                   Plantillas FSE (.html)
├── parts/                       header.html, footer.html
├── patterns/                    Patrones PHP (páginas y secciones)
├── acf-json/                    Grupos de campos SCF (Local JSON: se cargan y guardan aquí)
├── tools/plantilla-excel/       Generador de la plantilla Excel de importación (Python + openpyxl)
└── assets/
    ├── css/                     main.css (maqueta), wp-compat.css (ajustes WP), components.css (componentes nuevos)
    ├── js/                      JS de la maqueta adaptado + forms.js, docs.js; editor/blocks.js
    ├── icons/sprite.svg         Sprite de íconos (<use href="#i-nombre">)
    ├── fonts/                   Saira y Archivo (woff2 locales)
    ├── img/                     Imágenes de la maqueta (WebP), favicon, OG
    ├── vendor/                  Bootstrap 5.3.3 y Leaflet 1.9.4 (locales)
    └── import/                  Plantilla Excel del importador
```

**Prefijo:** todo usa `lbx_` (funciones, opciones, handles, campos) y el dominio de traducción `lubrax`.

---

## 15. Módulos PHP (`/inc`)

| Archivo | Responsabilidad | Funciones clave |
|---|---|---|
| `helpers.php` | Utilidades de plantilla y campos | `lbx_asset()`, `lbx_icon()`, `lbx_page_url()`, `lbx_contact_url($tipo)`, `lbx_tel_href()`, `lbx_whatsapp_url()`, `lbx_field_key()`, `lbx_get_field()`, `lbx_update_field()` |
| `setup.php` | Soportes del tema, menús, categorías de patrones, estilos del editor | — |
| `theme-options.php` | Página de opciones SCF y Local JSON | `lbx_option($key)`, `lbx_option_raw()`, `lbx_option_defaults()`, `lbx_social_links()` |
| `nav-walker.php` | Menús con el marcado de la maqueta; corrige el falso "Blog" actual | `LBX_Nav_Walker`, `lbx_nav_menu()` |
| `assets.php` | CSS/JS (defer), precarga de fuentes, favicon, script del editor | `lbx_asset_version()`; registra `lbx-slider`, `lbx-finder`, `lbx-catalog`, `lbx-product`, `lbx-locator`, `lbx-blog`, `lbx-contact`, `lbx-docs` y `lbx-forms` |
| `globals.php` | Sprite SVG, menú lateral, modal de distribuidor, "volver arriba" | — |
| `blocks.php` | Registra cada `blocks/*/block.json` y la categoría "Lubrax" | — |
| `media.php` | Salida WebP, tamaños `lbx-card` (640×360) y `lbx-hero` (1600) | — |
| `cleanup.php` | Limpieza de `<head>`, emojis, comentarios desactivados | — |
| `post-types.php` | CPT `distribuidor` (no público, capacidades propias) | — |
| `taxonomies.php` | Las 6 taxonomías de producto y sus términos iniciales | `lbx_product_taxonomies()`, `lbx_segment_icon_url()` |
| `distributors.php` | Datos de distribuidores para mapa, modal y fichas; coordenadas desde Google Maps; comunas (select dependiente, validación) | `lbx_get_distributors()` (transient `lbx_distributors`), `lbx_distributor_data()`, `lbx_distributor_card()`, `lbx_comunas()`, `lbx_comuna_match()` |
| `woocommerce.php` | Modo catálogo y botón "Cotizar volumen" | `lbx_quote_url()`, `lbx_quote_button()` |
| `products.php` | Datos de producto, tarjetas, documentos, relacionados, búsqueda, Schema Product | `lbx_product_data()`, `lbx_product_card()`, `lbx_product_documents()`, `lbx_related_products()`, `lbx_product_options_html()`, `lbx_search_product_ids()` |
| `blog.php` | Tarjeta de artículo, barra lateral, tiempo de lectura, Schema BlogPosting | `lbx_post_card()`, `lbx_blog_sidebar()`, `lbx_post_date()` |
| `acf-blocks.php` | Helpers de bloques SCF y **contenido por defecto de la maqueta** | `lbx_block_value()`, `lbx_block_defaults()`, `lbx_acf_block_markup()`, `lbx_title_lines()`, `lbx_link_html()`, `lbx_image_or_asset()` |
| `core-blocks.php` | Estilos de botón, clases `reveal-*`, contadores, sin `entry-content` en páginas con patrones | — |
| `contact.php` | CF7: motivos, áreas, etiquetas propias, validación condicional, antispam, etiquetas de correo, CFDB7, creación de los formularios | `lbx_contact_motives()`, `lbx_contact_areas()`, `lbx_contact_recipients()`, `lbx_form_id()`, `lbx_form_html()`, `lbx_install_forms()` |
| `seo.php` | Metadatos, canónicas, sitemap, 404 sin redirección, Organization/WebSite, coordinación con plugins SEO | `lbx_seo_plugin_active()`, `lbx_schema_enabled()`, `lbx_print_schema()`, `lbx_seo_context()` |
| `roles.php` | Roles Administrador Técnico y Editor de Contenido (versionados) | `LBX_ROLES_VERSION` |
| `importer/` | Importador Excel | `wp lubrax importar` |

**Versionados que se ejecutan una vez en `admin_init`:** roles (`LBX_ROLES_VERSION`), formularios CF7 (`LBX_FORMS_VERSION`; crea "Contacto" y "Newsletter" si no existen) y términos de taxonomías. Para volver a aplicar un cambio, sube la versión.

---

## 16. Bloques (`/blocks`)

Hay dos tipos:

1. **Bloques dinámicos** (`"render": "file:./render.php"` en block.json): sus atributos se editan en la barra lateral, que define `assets/js/editor/blocks.js` (objeto `BLOCKS`, sin compilación). La vista previa en el editor usa ServerSideRender.
2. **Bloques SCF** (`"acf": {"renderTemplate": "render.php"}`): sus campos están en `acf-json/group_lbx_block_{nombre}.json`. Cada campo lleva un **prefijo propio del bloque** (`hero_`, `cf_`, `srv_`…) para que no choque con los de otros bloques.

| Bloque | Tipo | Script que encola | Notas |
|---|---|---|---|
| `site-header`, `site-footer` | Dinámico | — | Marcado de la maqueta; el footer imprime el formulario Newsletter |
| `page-hero` | Dinámico | — | Atributos `lead`, `compact`, `channels` |
| `product-catalog` | Dinámico | `lbx-catalog` | Catálogo y archivos de taxonomía |
| `product-page` | Dinámico | `lbx-product` | Ficha completa y relacionados |
| `distributor-locator` | Dinámico | `lbx-locator` (+ Leaflet) | Mapa y listado |
| `doc-library` | Dinámico | `lbx-docs` | Repositorio de PDF |
| `post-list`, `post-article` | Dinámico | `lbx-blog` | Blog |
| `cta-band` | Dinámico | — | Banda amarilla |
| `legal-document` | Dinámico **con bloques internos** | — (app.js: índice) | Guarda su contenido; `render.php` lo divide por H2 en secciones, índice y contacto |
| `error-404` | Dinámico | — | Lee Opciones del tema → Página 404 |
| `search-results` | Dinámico | — | Buscador interno |
| `hero-slider` | SCF | `lbx-slider` | Precarga la primera imagen |
| `intro`, `segments`, `plant`, `featured-products`, `latest-posts`, `image-band` | SCF | — | Inicio / Quiénes somos |
| `product-finder` | SCF | `lbx-finder` | Imprime el índice JSON `#lbx-product-index` |
| `faq` | SCF | — | JSON-LD FAQPage |
| `contact-services` | SCF | — | Botones `data-contact-type` |
| `contact-form` | SCF | `lbx-contact`, `lbx-forms` | Formulario CF7 + éxito y error |

**Contenido por defecto:** si un campo SCF está vacío, `lbx_block_value()` devuelve el valor de `lbx_block_defaults('{bloque}')` (en `inc/acf-blocks.php`). Ese mismo contenido arma los patrones con `lbx_acf_block_markup()`.

---

## 17. Plantillas, partes y patrones

| Plantilla | Uso | Bloques |
|---|---|---|
| `index.html` | Respaldo | — |
| `page.html` | Página de texto | page-hero + contenido |
| `page-full.html` | **"Página con patrones (ancho completo)"** | contenido con clase `lbx-sections` (sin márgenes entre secciones ni `entry-content`) |
| `archive-product.html`, `taxonomy.html` | Catálogo y taxonomías | product-catalog + cta-band |
| `single-product.html` | Ficha | product-page + cta-band |
| `home.html`, `category.html`, `tag.html` | Blog | post-list + cta-band |
| `single.html` | Artículo | post-article + cta-band |
| `search.html` | Búsqueda | search-results + cta-band |
| `404.html` | Error 404 | error-404 |

Los cambios hechos en el **editor del sitio** se guardan en la base de datos y tienen prioridad sobre los archivos `.html`. Para volver al archivo del tema: Editor → Plantillas → ⋮ → **Restablecer**.

**Patrones** (`/patterns`, categorías `lubrax-paginas` y `lubrax-secciones`): `home`, `about`, `about-intro`, `stats`, `contact` y `legal`. Son PHP: pueden usar las funciones del tema para generar el marcado con los textos por defecto.

---

## 18. CSS y JavaScript

### CSS (orden de carga, prioridad 20)

1. `vendor/bootstrap/bootstrap.min.css`
2. `css/main.css`: **CSS de la maqueta sin cambios** (salvo `@font-face`, que ahora define theme.json). Tiene un índice de secciones al inicio.
3. `css/wp-compat.css`: ajustes para WordPress (partes de plantilla con `display: contents`, márgenes de `#main` y `.lbx-sections`, barra de administración).
4. `css/components.css`: componentes nuevos:
   - 01 Repositorio de documentos
   - 02 Bloques core
   - 03 Formularios CF7
   - 04 Buscador interno

**Regla:** no edites `main.css` para estilos nuevos; agrégalos en `components.css` con los tokens (`--lbx-green`, `--lbx-yellow`, `--radius`…).

### JavaScript (todos con `defer`)

| Archivo | Origen | Cambios respecto de la maqueta |
|---|---|---|
| `app.js` | Maqueta | Ninguno (núcleo: zona activa, header, animaciones, validación, FAQ, paginación) |
| `slider.js`, `finder.js` | Maqueta | Ninguno |
| `catalog.js` | Maqueta | Título del archivo con `onFacetArchive()` |
| `product.js` | Maqueta | `data-map-url` |
| `locator.js` | Maqueta | Teselas desde `data-tile-*` |
| `blog.js` | Maqueta | `data-cat`, `data-tag`, `data-base-url` |
| `contact.js` | Maqueta | Ninguno |
| `docs.js` | **Nuevo** | Repositorio de documentos |
| `forms.js` | **Nuevo** | Envío de CF7 por la API REST. Ver el detalle bajo la tabla. |
| `editor/blocks.js` | **Nuevo** | Interfaz de los bloques dinámicos en el editor |

`forms.js` escucha `lbx:formsubmit` (lo emite `app.js` tras validar con Bootstrap). Luego envía a `data-cf7-endpoint`, muestra `[data-form-success]` o `[data-form-error]`, y emite los eventos de CF7: `wpcf7mailsent`, `wpcf7invalid`, etc.

El JS y el CSS de CF7 están **desactivados** (`wpcf7_load_js` y `wpcf7_load_css` en `inc/contact.php`).

---

## 19. Campos personalizados (SCF / acf-json)

- **Sincronización en los dos sentidos** (`inc/theme-options.php`):
  - **Admin → código:** al guardar un grupo en **SCF → Grupos de campos**, SCF escribe `acf-json/{clave}.json` en el tema.
  - **Código → admin:** `lbx_sync_field_groups()` se ejecuta al entrar al escritorio como administrador. Importa a la base de datos los JSON nuevos o más recientes (por ejemplo, después de un `git pull`) y muestra un aviso con los grupos actualizados. Es la acción «Sincronizar» de SCF, pero automática.
  - **Respaldo:** si la base de datos se pierde o se restaura una copia antigua, los campos siguen funcionando desde los JSON y se vuelven a importar solos. **Versiona `acf-json/` en Git**: es la copia de seguridad de la estructura de campos.
  - En producción, la carpeta `acf-json/` debe tener permisos de escritura para que los cambios hechos en el admin queden en el código. Si no, se guardan solo en la base de datos y conviene hacerlos en local y desplegarlos.
  - Para forzar la importación por terminal: `wp eval 'print_r( lbx_sync_field_groups() );'`.
- **Fuente de verdad:** los archivos de `acf-json/`. No generes los grupos con scripts externos, porque sobrescribirían lo editado en el admin.
- **Grupos:**
  - `group_lbx_theme_options`: Opciones del tema.
  - `group_lbx_producto`: ficha de producto.
  - `group_lbx_distribuidor`: datos del distribuidor.
  - `group_lbx_segmento`: ícono y orden de cada segmento.
  - `group_lbx_post`: casilla "Artículo popular".
  - `group_lbx_block_*`: uno por bloque SCF.
- **Escritura por clave:** el nombre `presentaciones` existe en producto y en distribuidor. Escribe siempre con `lbx_update_field($name, $value, $post_id)`, que resuelve la clave correcta con `lbx_field_key()`. Usar `update_field()` por nombre puede escribir en el campo equivocado.
- **Relación bidireccional** Producto ↔ Distribuidor: `field_lbx_prod_distribuidores` ↔ `field_lbx_dist_productos`. La caché de distribuidores (`lbx_distributors`) se renueva al guardar.

---

## 20. WooCommerce en modo catálogo

> Para activar las ventas en línea, ver la [sección 25](#25-activar-las-ventas-en-woocommerce-salir-del-modo-catálogo).

Todo está en `inc/woocommerce.php`:

- **Compra:** desactivada (`is_purchasable` = false). No se muestran precios ni pasarelas de pago, y las rutas de carrito y checkout de la Store API están eliminadas.
- **Botón de compra:** reemplazado por **"Cotizar volumen"** (`lbx_quote_button()`), que abre el formulario de contacto con `?tipo=industrial&producto={slug}`.
- **Estilos y Schema:** se quitan los estilos de WooCommerce (`woocommerce-blocktheme`, `woocommerce-inline` y `wc-blocks-style`). El Schema de producto de WooCommerce está vacío; lo reemplaza el del tema.
- **Escritorio:** se ocultan los menús y paneles de ventas.

---

## 21. Datos estructurados (Schema)

| Tipo | Dónde | Archivo |
|---|---|---|
| Product + BreadcrumbList | Ficha de producto | `inc/products.php` |
| BlogPosting + BreadcrumbList | Artículo | `inc/blog.php` |
| FAQPage | Bloque Preguntas frecuentes (interruptor en el bloque) | `blocks/faq/render.php` |
| Organization + WebSite | Inicio | `inc/seo.php` |

Todos pasan por `lbx_print_schema()`, que aplica `lbx_schema_enabled($tipo)`. Para forzar o quitar un tipo: `add_filter( 'lbx_schema_enabled', fn( $on, $type ) => …, 10, 2 );`.

---

## 22. Recetas: cómo hacer cambios frecuentes

**Agregar o cambiar un motivo del formulario o su área**
1. Edita `lbx_contact_motives()` en `inc/contact.php`: valor, etiqueta, ayuda y `area` (`comercial`, `tecnico` o `distribuidores`).
2. Si tiene campos propios, agrégalos en CF7 dentro de un `<fieldset data-show-for="nuevo-motivo">`.
3. Si se usa en las tarjetas de servicios, agrégalo a `MOTIVOS` del generador de grupos y en `acf-json/group_lbx_block_contact_services.json`.

**Agregar una nueva área de destino**
1. Agrégala en `lbx_contact_areas()`.
2. Crea el campo `lbx_form_to_{area}` en la pestaña Formularios (SCF → Grupos → Opciones del tema).

**Agregar un bloque SCF nuevo**
1. Crea `blocks/{nombre}/block.json` con `"acf": {"mode":"preview","renderTemplate":"render.php"}` y `render.php`.
2. Crea el grupo en SCF con la ubicación "Bloque es igual a lubrax/{nombre}". Usa un **prefijo propio** en los nombres de los campos. El grupo se guarda en `acf-json/`.
3. Opcional: agrega textos por defecto en `lbx_block_defaults()`.

**Agregar un bloque dinámico nuevo**
1. Crea `blocks/{nombre}/block.json` (atributos + `"render": "file:./render.php"`) y `render.php`.
2. Agrega sus controles en `BLOCKS` de `assets/js/editor/blocks.js` (tipos: `text`, `textarea`, `toggle`, `number`).

**Agregar un ícono**
- Agrega un `<symbol id="i-nombre">` en `assets/icons/sprite.svg` y úsalo con `lbx_icon('nombre')`.

**Agregar un script de página**
- Regístralo en `$page_scripts` de `inc/assets.php` y encólalo con `wp_enqueue_script('lbx-…')` desde el `render.php` del bloque que lo necesite.

**Cambiar colores o tipografías**
- En `theme.json` (lo que ve el editor) y en las variables `--lbx-*` de `main.css` (lo que ve el front). Mantén ambos iguales.

**Agregar un campo a un grupo (por ejemplo, a la ficha de producto)**
- SCF → Grupos de campos → editar el grupo → Guardar. El JSON del tema se actualiza solo; súbelo a Git.

**Cambiar los textos por defecto de una sección**
- En `lbx_block_defaults()` (`inc/acf-blocks.php`). Solo afecta a los campos vacíos y a los patrones nuevos, no a las páginas ya guardadas.

**Volver a crear los formularios de CF7**
- Borra el formulario en CF7 y sube `LBX_FORMS_VERSION` en `inc/contact.php`. En el siguiente acceso al escritorio se crea de nuevo y se guarda su ID en la opción `lbx_cf7_forms`.

**Cambiar el tiempo mínimo del antispam**
- `add_filter( 'lbx_form_min_seconds', fn() => 5 );`

---

## 23. Paso a producción

1. **Dominio:** reemplaza `http://lubrax.test` en la base de datos con `wp search-replace 'http://lubrax.test' 'https://www.lubrax.cl' --all-tables`, ejecutado desde PowerShell o con `MSYS_NO_PATHCONV=1` en Git Bash.
2. **Enlaces permanentes:** Ajustes → Enlaces permanentes → "Nombre de la entrada" → Guardar.
3. **Correo:** instala un plugin SMTP (o el servicio del hosting) y ajusta el **remitente** en CF7 (Correo y Correo 2) a una dirección del dominio, por ejemplo `no-responder@lubrax.cl`.
4. **Formularios:**
   - Completa los correos de cada área en Opciones del tema → Formularios.
   - Activa **Turnstile** en Contacto → Integración.
   - Haz un envío de prueba por área y borra los envíos de prueba en Contact Forms.
5. **Mapa:** configura un proveedor de teselas con clave en Opciones del tema → Mapa.
6. **Contenido de prueba:** reemplaza los 18 productos, 10 distribuidores y 12 artículos de prueba. Los medios de prueba tienen el metadato `_lbx_demo_source`. Revisa la tercera diapositiva del slider (texto provisional) y los textos legales (lorem ipsum).
7. **SEO:** instala Yoast o Rank Math (opcional), envía el sitemap a Google Search Console y revisa que no haya Schema duplicado con la [prueba de resultados enriquecidos](https://search.google.com/test/rich-results).
8. **Rendimiento:** activa una caché de página. El formulario funciona con caché: el token antispam solo exige un tiempo mínimo, sin caducidad. Imagick en el servidor sirve para las miniaturas de PDF y para AVIF.
9. **Depuración:** desactiva `WP_DEBUG` y `WP_DEBUG_LOG` en `wp-config.php` y ajusta `WP_ENVIRONMENT_TYPE` a `production`. Con eso, los assets usan la versión del tema en vez de la fecha del archivo.

---

## 24. Entorno local y problemas conocidos

- **PHP 8.3 (Laragon):** `.vscode/settings.json` apunta el linter de VS Code a ese PHP (si no, marca `match` como error).
- **Git Bash convierte rutas:** un argumento que empieza con `/` y se pasa a WP-CLI se convierte en una ruta de Windows y se guarda corrupto (así pasó con `/%postname%/`). Hay dos salidas:
  - Usar `wp eval '…'`.
  - Anteponer `MSYS_NO_PATHCONV=1` y llamar a WP-CLI como `php C:/laragon/bin/wp-cli.phar`.
- **Acentos con curl en Git Bash:** al probar formularios, curl puede enviar bytes UTF-8 inválidos y CF7 vacía esos campos. Prueba con Python o con el navegador.
- **Pruebas en navegador headless:** el antispam bloquea los envíos hechos en menos de 3 s reales. Para probar, baja el mínimo con el filtro `lbx_form_min_seconds`, solo en local.
- **Imágenes en capturas:** las de carga diferida pueden no aparecer en capturas automáticas. No es un error.
- **Caché de distribuidores:** si un cambio no aparece en el mapa, guarda de nuevo el distribuidor o borra el transient con `wp transient delete lbx_distributors`.

---

## 25. Activar las ventas en WooCommerce (salir del modo catálogo)

Hoy WooCommerce funciona solo como catálogo. Los productos, sus fichas y sus taxonomías sirven igual para una tienda, así que **no hay que rehacer el catálogo**. Lo que cambia es:

1. quitar el código que bloquea la compra;
2. agregar al diseño lo que la maqueta no tiene (precio, carrito, pago, cuenta);
3. configurar WooCommerce (pagos, envíos, impuestos, correos);
4. cumplir los requisitos legales y tributarios de la venta en línea en Chile.

Es un proyecto en sí mismo: la maqueta no incluye diseño de carrito, pago ni cuenta de cliente. Calcula diseño, desarrollo y pruebas antes de activarlo.

### 25.1 Decisiones previas

| Decisión | Opciones | Impacto |
|---|---|---|
| ¿Qué se vende? | Todo el catálogo / solo algunos productos o envases | Con venta parcial, el botón «Cotizar volumen» se mantiene en el resto (ver 25.3) |
| ¿Quién vende? | Lubrax directo / cada distribuidor | Hoy la venta es por distribuidores. Si venden ellos, se necesita un marketplace (por ejemplo, Dokan o WCFM), no solo activar el carrito |
| ¿Cómo se vende cada envase? | Un producto simple por envase / **producto variable** con atributo «Envase» (1 L, 4 L, 20 L…) | Variable es lo recomendado: un precio y stock por envase. Exige adaptar el selector de presentaciones de la ficha (25.2) |
| ¿Venta minorista, mayorista o ambas? | Precios por rol (B2B) | Requiere un plugin de precios por rol o por cantidad |
| ¿Stock en línea? | Manual / integrado con el ERP | Integración aparte |

### 25.2 Código del tema que hay que cambiar

Todo el bloqueo está en `inc/woocommerce.php`, en tres secciones comentadas. **Recomendación:** no lo borres. Agrega una constante en `wp-config.php` y envuelve esas secciones con ella, así se puede volver al modo catálogo:

```php
// wp-config.php
define( 'LBX_CATALOG_MODE', false ); // true = solo catálogo (actual), false = tienda.
```

```php
// inc/woocommerce.php, antes de la sección 1
if ( ! defined( 'LBX_CATALOG_MODE' ) || LBX_CATALOG_MODE ) {
	// … secciones 1, 2 y la parte de la sección 3 que oculta precios y pedidos …
}
```

**`inc/woocommerce.php`: qué bloquea cada pieza y qué hacer**

| Código actual | Qué bloquea | Al activar ventas |
|---|---|---|
| `woocommerce_is_purchasable` → false | Que un producto se pueda comprar | **Quitar** (o condicionar, ver 25.3) |
| `woocommerce_get_price_html` → vacío | Mostrar el precio | **Quitar** |
| `woocommerce_payment_gateways` → vacío | Medios de pago | **Quitar** |
| `woocommerce_cart_needs_payment` → false | Pago en el checkout | **Quitar** |
| `woocommerce_coupons_enabled` → false | Cupones | Quitar si se usarán cupones |
| `woocommerce_product_tabs` sin `reviews` | Reseñas | Opcional (el diseño de la ficha no las muestra) |
| `remove_action( … add_to_cart_action … )` | `?add-to-cart=ID` | **Quitar** |
| `woocommerce_loop_add_to_cart_link` y bloques `product-button`, `add-to-cart-form`, `add-to-cart-with-options` → «Cotizar» | Botón «Añadir al carrito» | **Quitar** (o condicionar, ver 25.3) |
| Bloques `product-price`, `mini-cart`, `customer-account`, `cart-link` → vacío | Precio, mini carrito y cuenta en bloques de WooCommerce | **Quitar** |
| `template_redirect`: carrito, checkout y cuenta → catálogo (301) | Las páginas de compra | **Quitar** |
| `rest_endpoints` sin `/wc/store/cart`, `checkout`, `order`, `batch` | API del carrito y del pago (la usan los bloques Carrito y Finalizar compra) | **Quitar** |
| `woocommerce_persistent_cart_enabled` → false | Carrito guardado del cliente | Quitar |
| `wp_dequeue_script` de `wc-cart-fragments`, `wc-add-to-cart`, `wc-cart`, `wc-checkout`, `woocommerce`, `sourcebuster-js`, `wc-order-attribution` | JS del carrito, del pago y de atribución | **Quitar** |
| `woocommerce_enqueue_styles` vacío y `wp_dequeue_style` de `woocommerce-blocktheme`, `woocommerce-inline`, `wc-blocks-style` | Estilos de WooCommerce | Volver a cargarlos **solo** en carrito, pago, cuenta y confirmación (`is_cart() || is_checkout() || is_account_page()`), o diseñar esas páginas con estilos propios |
| `product_type_selector` solo «simple» y `product_type_options` vacío | Productos variables, virtuales y descargables | Quitar si se usan productos variables |
| `woocommerce_product_data_tabs` sin envío, atributos, variaciones ni avanzado | Pestañas del producto en el admin | **Quitar** (envío y variaciones son necesarios) |
| CSS en `admin_head` que oculta precio, impuestos y stock | Campos de precio en el admin | **Quitar** |
| `manage_edit-product_columns` sin precio ni stock | Columnas del listado de productos | Quitar |
| `admin_menu`: quita Pedidos, Clientes, Informes, Cupones, Marketing, Analítica y Pagos | Menús de ventas | **Quitar** |
| `woocommerce_admin_features` sin `analytics`, `payments`… | Analítica y pagos del panel | Quitar |

**Otros archivos del tema**

| Archivo | Cambio |
|---|---|
| `inc/products.php` → `lbx_product_card()` | Agregar el precio (`$product->get_price_html()`) y el botón «Añadir al carrito». Hoy la tarjeta tiene «Ver más» y «Dónde comprar» |
| `blocks/product-page/render.php` | Agregar precio, cantidad y botón «Añadir al carrito», junto al recuadro «Cotizar volumen» (`lbx_quote_button()`). Con productos variables, el selector de presentaciones debe elegir la variación (precio y stock por envase), además de cambiar la imagen |
| `assets/js/product.js` | Conectar el selector de presentaciones con la variación elegida |
| `assets/js/catalog.js` | Opcional: orden por precio y filtro «disponible para compra» |
| `blocks/site-header/render.php` y `inc/globals.php` (menú lateral) | Ícono de carrito con contador y enlace a «Mi cuenta». El contador se actualiza con los fragmentos de WooCommerce (`wc-cart-fragments`) o con el bloque Mini carrito |
| `inc/products.php` → JSON-LD Product | Agregar `offers` (`@type: Offer`, `price`, `priceCurrency: CLP`, `availability`, `url`). Dejar un solo Schema de producto: el del tema o el de WooCommerce (quitando `add_filter( 'woocommerce_structured_data_product', '__return_empty_array' )`) |
| `inc/seo.php` | El sitemap ya excluye carrito, pago y mi cuenta. Si no hay plugin SEO, agregar `noindex` a esas páginas en el filtro `wp_robots` |
| `inc/roles.php` | Decidir quién gestiona pedidos: el rol **Gestor de tienda** (`shop_manager`) de WooCommerce sirve para eso. El Editor de Contenido no tiene acceso a pedidos y no debería tenerlo por defecto |
| `inc/contact.php` → `lbx_contact_motives()` | Opcional: motivos como «Mi pedido en línea» o «Cambios y devoluciones», con su área |
| `inc/taxonomies.php` | Nada obligatorio: las categorías y etiquetas de WooCommerce siguen ocultas y los segmentos las reemplazan |

### 25.3 Venta mixta: tienda y cotización

Para vender algunos productos o envases en línea (por ejemplo, 1 L y 4 L) y cotizar otros (tambores, IBC):

- **Con precio:** un producto o variación con precio se puede comprar.
- **Sin precio:** WooCommerce no permite comprar un producto o variación sin precio. Ahí el tema sigue mostrando «Cotizar volumen».
- **En el código:** cambia el filtro global por una condición:
  ```php
  add_filter( 'woocommerce_is_purchasable', fn( $ok, $product ) => $ok && '' !== $product->get_price(), 10, 2 );
  ```
  En la tarjeta y en la ficha, muestra `lbx_quote_button()` cuando `! $product->is_purchasable()`.
- **Mayoristas:** el recuadro «¿Necesitas volumen?» de la ficha se mantiene para ellos.

### 25.4 Diseño y plantillas que faltan

La maqueta no tiene estas pantallas, así que deben diseñarse con el mismo sistema (Bootstrap y `main.css`):

| Página | Plantilla FSE | Contenido |
|---|---|---|
| Carrito | `templates/page-cart.html` | Bloque «Carrito» de WooCommerce |
| Finalizar compra | `templates/page-checkout.html` | Bloque «Finalizar compra» |
| Pedido recibido | `templates/order-confirmation.html` | Resumen del pedido |
| Mi cuenta | `templates/page.html` con `[woocommerce_my_account]` | Pedidos, direcciones y datos |

- **Páginas:** WooCommerce → Estado → Herramientas → «Crear páginas por defecto», si no existen. Luego asígnalas en WooCommerce → Ajustes → Avanzado.
- **Estilos:** si se usan los bloques de WooCommerce, vuelve a cargar sus estilos en esas páginas (25.2) y ajusta colores y tipografías con las variables de `theme.json`. Los estilos propios van en `assets/css/components.css`, en una nueva sección «Tienda».
- **Correos de pedido:** WooCommerce → Ajustes → Correos electrónicos. Configura el logo, los colores de la marca (verde `#032b1a` y amarillo) y el remitente del dominio.
- **Navegación:** agrega «Mi cuenta» y «Carrito» en los menús (Header y Menú lateral) y en el footer.

### 25.5 Configuración de WooCommerce

| Ajuste | Dónde | Valor para Chile |
|---|---|---|
| Moneda | Ajustes → Generales | Peso chileno (CLP), **0 decimales**, separador de miles «.» |
| Ubicación y países de venta | Ajustes → Generales | Chile |
| Impuestos | Ajustes → Generales → «Habilitar impuestos» y pestaña Impuestos | IVA 19 %. Decide si los precios se cargan con IVA incluido (lo habitual en venta a consumidores) |
| Envíos | Ajustes → Envío | Zonas por región, tarifas y retiro en distribuidor. Para productos pesados (tambores, IBC), tarifa por peso o «a coordinar» |
| Medios de pago | Ajustes → Pagos | Plugins de pasarelas locales (Transbank Webpay Plus, Mercado Pago, Flow, Khipu) y transferencia bancaria |
| Inventario | Ajustes → Productos → Inventario | Gestión de stock y aviso de stock bajo |
| Cuentas y privacidad | Ajustes → Cuentas y privacidad | Compra como invitado, creación de cuenta, retención de datos |
| Peso y dimensiones | Cada producto → pestaña Envío | Necesarios para calcular el envío |
| Correo saliente | Plugin SMTP | Ya recomendado para los formularios (sección 23) |

### 25.6 Requisitos legales y tributarios (Chile)

Valídalos con el área legal y contable del cliente.

- **Documento tributario:** cada venta necesita su **boleta o factura electrónica** (SII). Requiere integrar un proveedor de DTE, como Bsale, OpenFactura, LibreDTE o el ERP de la empresa.
- **Ley del Consumidor (19.496):**
  - términos y condiciones de venta;
  - derecho a retracto o política de cambios y devoluciones;
  - precio total con impuestos, visible;
  - información de despacho.

  Se pueden crear como páginas con el bloque *Documento legal* ([sección 7.1](#71-páginas-legales-términos-y-políticas)) y enlazarlas en el pago.
- **Privacidad y seguridad:** actualiza la Política de privacidad con los datos de facturación y pago. El sitio debe estar en **HTTPS**.
- **Productos químicos:** revisa si hay restricciones de transporte para algunos lubricantes y si las hojas de seguridad (SDS) deben acompañar el despacho.

### 25.7 Caché y rendimiento

- **Excluir de la caché de página:** carrito, finalizar compra, mi cuenta y las visitas con las cookies `woocommerce_items_in_cart`, `woocommerce_cart_hash` o `wp_woocommerce_session_*`.
- **Contador del carrito:** el header queda en caché, así que el contador debe actualizarse por AJAX (fragmentos de WooCommerce o bloque Mini carrito).

### 25.8 Lista de verificación antes de abrir la tienda

1. `LBX_CATALOG_MODE` en `false` y las secciones de `inc/woocommerce.php` condicionadas (25.2).
2. Precios, IVA, peso y stock cargados en los productos o en las variaciones por envase.
3. Tarjeta del catálogo, ficha y header con precio, botón de compra y carrito (25.2).
4. Carrito, finalizar compra, confirmación y mi cuenta diseñados y con estilos (25.4).
5. Pasarela en modo de pruebas: compra completa, pago rechazado y reembolso.
6. Envíos: cálculo correcto por zona y por peso.
7. Boleta o factura electrónica emitida por cada pedido.
8. Correos de pedido (al cliente y a la tienda) con la marca, enviados por SMTP.
9. Páginas legales de venta publicadas y enlazadas en el pago.
10. Schema `Product` con `offers` y sin duplicados ([prueba de resultados enriquecidos](https://search.google.com/test/rich-results)).
11. Caché configurada con las exclusiones de 25.7.
12. Quien gestiona los pedidos tiene el rol Gestor de tienda y recibe el aviso de pedido nuevo.
13. Pasarela en modo producción y una compra real de bajo monto.

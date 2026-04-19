# La Mejor Taza — Pasaporte del Café de Nariño

Plugin de WordPress que implementa el diseño **La Mejor Taza** entregado por Claude Design: registro de stands del Festival del Café de Nariño, generación de códigos QR imprimibles, votación pública con emojis, pasaporte personal con efecto libreta y dashboard en vivo.

## Instalación

1. Copia la carpeta `la-mejor-taza/` dentro de `wp-content/plugins/`.
2. Actívalo desde **Plugins → La Mejor Taza** en el admin.
3. Crea (o asigna) tres páginas en WordPress:
   - **Dashboard público** — contiene el shortcode `[lmt_dashboard]`.
   - **Voto** — contiene `[lmt_vote]` (recibe `?stand=ID` desde el QR).
   - **Pasaporte** — contiene `[lmt_passport]`.
4. En **La Mejor Taza → Configuración** asocia esas páginas y ajusta el nombre del festival, fechas, ciudad y paleta.

## Shortcodes

| Shortcode                  | Pantalla del diseño                    |
|----------------------------|----------------------------------------|
| `[lmt_dashboard]`          | Dashboard público (ranking + mapa + live) |
| `[lmt_vote stand="ID"]`    | Formulario de voto en 3 pasos          |
| `[lmt_passport]`           | Pasaporte con efecto libreta 3D        |
| `[lmt_stand id="ID"]`      | Detalle público de un stand            |

## Pantallas implementadas

Sigue las pantallas planeadas en el chat del diseño:

- **Administrador** — login (vía wp-login), lista de stands (CPT estándar de WP), editor con metadatos del stand y selector de color de sello, generador de cartel A5 imprimible (`La Mejor Taza → Códigos QR`) y feed de actividad en vivo (`La Mejor Taza → Actividad en vivo`).
- **Usuario móvil** — formulario de voto (correo → emoji → compra + comentario), confirmación con animación del sello que aterriza en el pasaporte y libreta-pasaporte con páginas portada, índice, sellos y cierre.
- **Público** — dashboard con podio top 3, mapa estilizado de Nariño con puntos interactivos, feed live de votos y tabla completa con distribución; detalle por stand con tarjeta tipo pasaporte y comentarios recientes.

## Sistema de diseño

- **Tipografía**: Instrument Serif (display) + Geist (UI) + JetBrains Mono (metadatos), cargadas desde Google Fonts.
- **Paleta**: tonos tierra de Nariño con cuatro variantes alternativas (Nariño, Arena, Niebla, Mercado), seleccionables desde Configuración.
- **Tokens**: `assets/css/tokens.css` replica `styles/tokens.css` del bundle de diseño.
- **Sello**: SVG circular tipo pasaporte real con rotación pseudo-aleatoria por stand.
- **Animaciones**: `lmt-stamp-land` para el sello, `lmt-fade-up` para entradas y volteo CSS 3D para la libreta.

## Modelo de datos

- **CPT** `lmt_stand` — un post por stand. Metas: `_lmt_municipio`, `_lmt_region`, `_lmt_direccion`, `_lmt_correo`, `_lmt_color`, `_lmt_coord_x`, `_lmt_coord_y`, `_lmt_votos`, `_lmt_score`, `_lmt_total_votos`. La imagen destacada se usa como logo.
- **Tabla** `{prefix}lmt_votes` — un voto por stand y por correo (hash). Campos: stand, email + email_hash, emoji, comprado, comentario, timestamp, ip_hash.
- **Tabla** `{prefix}lmt_passports` — pasaporte por correo (hash) con nombre opcional.
- **Tabla** `{prefix}lmt_passport_visits` — relación pasaporte → stand con timestamp de visita.

## REST API

- `POST /wp-json/lmt/v1/vote` — registra un voto (requiere nonce `wp_rest`).
- `GET  /wp-json/lmt/v1/stands` — listado de stands con metadatos.
- `GET  /wp-json/lmt/v1/live` — últimos comentarios para el feed live.
- `GET  /wp-json/lmt/v1/passport?email=...` — pasaporte con visitas por correo.

## QR

Para el cartel A5 se usa la librería local `assets/vendor/qrcode.min.js` (davidshimjs/qrcodejs, MIT). El QR enruta a la página de voto con `?stand=ID` para que al escanearlo el usuario llegue directo al formulario.

## Estructura

```
la-mejor-taza/
├── la-mejor-taza.php          # bootstrap del plugin
├── uninstall.php
├── README.md
├── includes/                   # CPT, REST, DB, shortcodes, admin, assets
├── templates/                  # markup de cada pantalla
└── assets/
    ├── css/  (tokens.css, public.css, admin.css)
    ├── js/   (public.js, admin.js)
    └── vendor/qrcode.min.js
```

## Origen del diseño

Diseño entregado por Claude Design (handoff bundle `la-mejor-taza`) y resumido en `chats/chat1.md` del bundle. Este plugin recrea las 10 pantallas del prototipo en HTML/CSS/PHP con las mismas tokens y comportamientos, sustituyendo React por server-side rendering y vanilla JS para el flujo de voto y la libreta.

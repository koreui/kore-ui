# Migrar de 2.x a 3.0

La 3.0 cambia el aspecto por defecto de la librería. No cambia la API de los componentes: las etiquetas, los props y los eventos de la 2.x siguen funcionando.

## Qué hay que hacer

1. **Recompilar el CSS de la aplicación.** Las plantillas usan utilidades nuevas (`kore-control`, `kore-panel`, `shadow-kore-*`…) que Tailwind genera a partir de `kore-theme.css`.
2. **Si publicaste las vistas** (`kore-ui-views`), volver a publicarlas o revisar las tuyas: las antiguas siguen funcionando, pero conservan el aspecto de la 2.x y no obedecen al interruptor de movimiento.
3. **Si publicaste la configuración**, añadir las claves nuevas (abajo). Sin ellas todo funciona con los valores por defecto.

## Qué cambia a la vista

| Antes | Ahora |
|---|---|
| Fondo de página blanco puro | Gris muy claro; las tarjetas y tablas siguen siendo blancas y se separan del fondo |
| Grises sin tinte | Grises con un leve tinte del color primario |
| Campos sin sombra, foco con anillo de 2 px | Sombra corta, borde que se oscurece al pasar, foco con borde de color y halo |
| Botón sólido plano | Brillo en el canto superior y sombra corta; cede al pulsarlo |
| Botón sólido 2 px más bajo que un campo | Todos los controles del mismo tamaño miden lo mismo (30 / 38 / 46 px) |
| Radios de 6 y 8 px | 7 px en controles, 10 px en superficies, 14 px en modales |
| Opción elegida de un select con fondo primario | Texto en negrita y una marca; el fondo señala la opción bajo el cursor |
| `<x-kore::stats>` sin sombra | Con la misma sombra que `<x-kore::card>` |
| Casilla nativa en la tabla, el árbol y el transfer | La misma casilla que `<x-kore::checkbox>` |
| Foco con `focus:` (aparece al hacer clic) en parte de los componentes | Solo con teclado, en todos |

Para recuperar el aspecto anterior, ver [«Volver al aspecto de la 2.x»](theme/design-tokens.md#volver-al-aspecto-de-la-2x).

## Cambios que pueden romper algo

### Clases que ya no están en el HTML

Si tu CSS o tus tests apuntan a clases de las plantillas, estas han cambiado:

| Antes | Ahora |
|---|---|
| `border-kore-destructive` en un campo con error | `kore-invalid`, o el propio `aria-invalid="true"` |
| `focus:ring-2 focus:ring-kore-ring` | `kore-control` (campos) o `kore-focus` (botones) |
| `shadow-sm`, `shadow-lg`… | `shadow-kore-sm`, `shadow-kore-lg`… |
| `transition-colors duration-150` | `kore-transition` |
| `x-transition:*` en menús, selects y calendarios | `kore-pop` |
| `text-kore-success`, `text-kore-destructive`… como texto | `text-kore-success-text`… (más contraste) |

### Textos por defecto

Varios textos estaban escritos en inglés dentro de las vistas o del JavaScript. Ahora salen de la configuración y su valor por defecto está en español:

| Dónde | Antes | Ahora | Clave |
|---|---|---|---|
| Calendario y selector de color | `Apply` / `Cancel` | `Aplicar` / `Cancelar` | `form.translations.apply`, `cancel` |
| Atajos del calendario | `Today`, `Last 7 days`… | `Hoy`, `Últimos 7 días`… | `form.translations.date_*` |
| Subida | `Drag & drop or browse` | `Arrastra archivos aquí o búscalos` | `form.translations.upload_dropzone`, `upload_browse` |
| Subida | `Upload N files` / `Clear all` | `Subir N archivos` / `Quitar todos` | `upload_pending_one`, `upload_pending_many`, `upload_clear_all` |
| Select `creatable` | `Create "x"` | `Crear «x»` | `form.translations.create` |
| Spotlight, repeater, transfer | escritos en la vista | igual texto, ahora configurable | `ui.translations.spotlight_*`, `repeater_empty`, `transfer_empty` |

### `<x-kore::stats>` lleva sombra

Para dejarla como estaba:

```php
'ui' => ['stats' => ['shadow' => false]],
```

## Configuración nueva

```php
'ui' => [
    // false apaga todas las animaciones de la librería.
    'motion' => true,
],
```

Más las claves de `translations` de la tabla de arriba.

## Navegadores

La entrada y salida de los paneles usa `@starting-style` y `transition-behavior: allow-discrete` (Chrome 117, Safari 17.4, Firefox 129). En versiones anteriores los paneles se abren y se cierran igual, sin fundido.

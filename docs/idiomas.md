# Idiomas

Los textos de la librería —botones, `aria-label`, mensajes— viven en `config/kore-ui.php`,
en las secciones `form.translations`, `ui.translations` y `datatable.translations`, en español.
Para otro idioma no hace falta reescribirlos: `locale` aplica encima un paquete de textos.

## Elegir el idioma

```php
// config/kore-ui.php
'locale' => 'auto',
```

| Valor | Qué hace |
|---|---|
| `null` | Los textos del config, tal cual. Es el valor de fábrica |
| `'auto'` | El idioma de la aplicación (`app()->getLocale()`), y lo sigue si cambia en un middleware |
| `'en'` | Ese idioma, fijo |

La librería trae el paquete `en`. `en_GB` o `en-US` usan `en`: los paquetes son por idioma,
no por región. Un idioma sin paquete deja los textos como están.

De fábrica es `null` y no `'auto'` porque Laravel instala `APP_LOCALE=en` y muchas
aplicaciones en español nunca lo cambian: seguir el idioma de la aplicación por defecto
les habría pasado la interfaz al inglés al actualizar.

## Qué cubre

Las 212 claves de las tres secciones `translations` y los tres textos sueltos
(`datatable.empty_text`, `spotlight.placeholder`, `chart.empty_text`). Los nombres de mes y
de día del calendario del datepicker ya salían en el idioma de la aplicación: los da el
navegador.

No cubre lo que escribes tú: etiquetas, placeholders y opciones que pasas a un componente.

## Cambiar un texto

Un texto que cambies en el config se respeta en cualquier idioma: el paquete solo
sustituye los que siguen con su valor de fábrica.

```php
'form' => [
    'translations' => [
        'search' => 'Encontrar',   // se queda así también con locale => 'en'
    ],
],
```

Para cambiarlo solo en un idioma, publica los paquetes y edita la copia:

```bash
php artisan vendor:publish --tag=kore-ui-lang
```

```php
// lang/vendor/kore-ui/en/kore-ui.php
'form' => [
    'clear' => 'Reset',
],
```

## Añadir un idioma

Copia `lang/vendor/kore-ui/en/` con el código del tuyo (`fr`, `pt`…) y traduce. Las claves
que dejes fuera se quedan en español. Con `'locale' => 'auto'` se aplica en cuanto la
aplicación esté en ese idioma.

## Con kore-ui/calendar

El calendario tiene sus propios textos en `lang/` y sigue el idioma de la aplicación sin
configurar nada. Su formulario de evento usa componentes de kore-ui (fecha, hora, select):
para que una aplicación en inglés no vea «Mes anterior» dentro de un formulario en inglés,
pon `'locale' => 'auto'`.

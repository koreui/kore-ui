# Spotlight — Configuración y Personalización

## Configuración completa (`config/kore-ui.php`)

```php
'spotlight' => [
    // PHP provider classes
    'providers' => [],

    // Atajo de teclado (Cmd/Ctrl + shortcut)
    'shortcut' => 'k',

    // Placeholder del input
    'placeholder' => 'Buscar acciones, páginas...',

    // Mostrar items cuando input está vacío
    'show_results_without_input' => true,

    // Historial reciente (localStorage)
    'show_recent' => true,
    'recent_count' => 5,
    'max_history' => 10,

    // Búsqueda remota global
    'search_url' => null,
    'search_method' => 'GET',
    'debounce' => 300,

    // Límite de resultados totales
    'max_results' => 50,

    // z-index (encima de overlays z-50 y feedback z-60)
    'z_index' => 'z-[70]',
],
```

## Props por instancia

Cualquier valor de config puede sobreescribirse por instancia:

```blade
<x-kore::spotlight
    shortcut="p"
    placeholder="Buscar páginas, usuarios, acciones..."
    search-url="{{ route('spotlight.global') }}"
    :show-recent="false"
    :recent-count="3"
    :debounce="500"
/>
```

## Tokens CSS

El Spotlight usa tokens existentes de `kore-theme.css`. No requiere tokens nuevos:

| Token | Uso |
|---|---|
| `--color-kore-surface` | Background del panel |
| `--color-kore-surface-hover` | Hover de ítems |
| `--color-kore-border` | Bordes del panel y separadores |
| `--color-kore-primary` | Ítem seleccionado (10% opacity) |
| `--color-kore-muted-fg` | Headers de grupo, placeholder |
| `--color-kore-fg` | Texto de ítems |
| `--kore-backdrop` | Velo que oscurece la página de detrás |
| `--radius-kore-lg` | Border radius del panel |
| `--shadow-kore-xl` | Sombra del panel |

`--kore-backdrop` es el mismo token que usa el overlay manager, así que el velo de un modal y el del Spotlight se cambian en un solo sitio. Es un token propio y **no** `--kore-fg`: el color de texto se invierte con el tema, y un velo pintado con él salía casi blanco en modo oscuro, aclarando la página en vez de atenuarla.

Dark mode funciona automáticamente con `.dark` o `[data-theme="dark"]` en `<html>`.

## API JavaScript

```js
// Abrir
window.dispatchEvent(new CustomEvent('kore:spotlight-open'));
window.dispatchEvent(new CustomEvent('kore:spotlight-open', {
    detail: { query: 'crear usuario' }
}));

// Cerrar
window.dispatchEvent(new CustomEvent('kore:spotlight-close'));

// Escuchar eventos
window.addEventListener('kore:spotlight:open', () => { /* spotlight abrió */ });
window.addEventListener('kore:spotlight:close', () => { /* spotlight cerró */ });
window.addEventListener('kore:spotlight:select', (e) => {
    console.log('Item seleccionado:', e.detail.item);
});
```

## Historial

El historial se guarda en `localStorage` con la clave `kore-spotlight-history`. Solo se persisten:
- `id` — identificador del item
- `name` — nombre del item
- `icon` — nombre del ícono
- `at` — timestamp de uso

Para deshabilitar: `:show-recent="false"`.

Para limpiar programáticamente desde el componente:

```js
localStorage.removeItem('kore-spotlight-history');
```

## Fuzzy search

El fuzzy search propio (~35 líneas) pondera los campos por peso:
- `name` — peso 3 (coincidencias en el nombre tienen más valor)
- `synonyms` — peso 2
- `description` — peso 1

Incluye bonus por caracteres consecutivos y por comenzar al inicio de la cadena.

Para datasets grandes (>200 items), puede ser reemplazado por Fuse.js instalándolo manualmente e integrándolo via `Alpine.data` — el algoritmo propio es suficiente para la mayoría de casos de uso.

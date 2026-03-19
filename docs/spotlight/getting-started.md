# Spotlight — Getting Started

Command palette activado con `Cmd+K` / `Ctrl+K`. Búsqueda fuzzy instantánea, grupos, historial y dependencias multi-paso.

## Instalación

El Spotlight viene incluido en KoreUI. Solo necesitas:

1. **Agregar el componente al layout principal:**

```blade
{{-- resources/views/layouts/app.blade.php --}}
<x-kore::spotlight />
```

2. **Crear al menos un provider** (opcional — puedes usarlo sin providers solo con búsqueda remota):

```php
// app/Spotlight/NavigationProvider.php
namespace App\Spotlight;

use KoreUi\Spotlight\SpotlightItem;
use KoreUi\Spotlight\SpotlightProvider;

class NavigationProvider extends SpotlightProvider
{
    public function group(): string { return 'Navegación'; }
    public function priority(): int { return 10; }

    public function items(): array
    {
        return [
            SpotlightItem::make('Inicio')->icon('home')->route('home'),
            SpotlightItem::make('Configuración')->icon('settings')->route('settings')->shortcut('⌘,'),
        ];
    }
}
```

3. **Registrar el provider en config:**

```php
// config/kore-ui.php
'spotlight' => [
    'providers' => [
        \App\Spotlight\NavigationProvider::class,
    ],
],
```

4. **Listo.** Presiona `Cmd+K` y aparece el Spotlight con tus items.

## Uso mínimo sin providers

Solo búsqueda remota:

```blade
<x-kore::spotlight search-url="{{ route('spotlight.search') }}" />
```

## Props del componente

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `shortcut` | `string` | `'k'` | Tecla del atajo (Cmd/Ctrl + key) |
| `placeholder` | `string` | `'Buscar acciones, páginas...'` | Placeholder del input |
| `providers` | `array` | `config('kore-ui.spotlight.providers')` | Override de providers |
| `search-url` | `string\|null` | `null` | Endpoint para búsqueda remota |
| `search-method` | `string` | `'GET'` | Método HTTP |
| `show-recent` | `bool` | `true` | Mostrar historial reciente |
| `recent-count` | `int` | `5` | Cantidad de recientes |
| `max-results` | `int` | `50` | Máximo de resultados |
| `debounce` | `int` | `300` | ms de debounce para remoto |

## API JavaScript

```js
// Abrir / cerrar desde JS
window.dispatchEvent(new CustomEvent('kore:spotlight-open'));
window.dispatchEvent(new CustomEvent('kore:spotlight-open', { detail: { query: 'crear' } }));
window.dispatchEvent(new CustomEvent('kore:spotlight-close'));

// Escuchar eventos
window.addEventListener('kore:spotlight:open', () => { ... });
window.addEventListener('kore:spotlight:close', () => { ... });
window.addEventListener('kore:spotlight:select', (e) => {
    console.log('Item seleccionado:', e.detail.item);
});
```

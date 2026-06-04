# Data — Getting Started

Sistema de visualizacion de datos con dos niveles:

1. **Table** (`<x-kore::table>`) — Componente Blade estatico para tablas simples con arrays/collections.
2. **DataTable** (`KoreDataTable`) — Clase abstracta Livewire con sorting, search, paginacion y eager loading automatico.

---

## Instalacion

Ambos componentes se registran automaticamente con el ServiceProvider. No se requiere configuracion adicional para empezar.

## Cuando usar cada uno

| Escenario | Componente |
|---|---|
| Lista corta de datos sin interaccion | `<x-kore::table>` |
| Datos estaticos desde un array o collection | `<x-kore::table>` |
| Tabla con sorting, search y paginacion | `KoreDataTable` |
| Datos desde Eloquent con relaciones | `KoreDataTable` |

## Configuracion

En `config/kore-ui.php`, seccion `datatable`:

```php
'datatable' => [
    'per_page'         => 25,
    'per_page_options' => [10, 25, 50, 100],
    'density'          => 'normal',      // compact|normal|relaxed
    'pagination_type'  => 'standard',    // standard|simple|cursor
    'search_debounce'  => 300,
    'empty_text'       => 'No se encontraron resultados',
    'empty_icon'       => 'inbox',
    'translations'     => [
        'search'     => 'Buscar...',
        'per_page'   => 'Por pagina',
        'showing'    => 'Mostrando :from a :to de :total resultados',
        'no_results' => 'No se encontraron resultados',
    ],
],
```

## Density

Ambos componentes soportan tres niveles de densidad:

| Density | Celdas | Headers |
|---|---|---|
| `compact` | `px-3 py-1 text-sm` | `px-3 py-1.5 text-xs` |
| `normal` | `px-4 py-2.5 text-sm` | `px-4 py-2 text-xs` |
| `relaxed` | `px-4 py-4 text-base` | `px-4 py-3 text-sm` |

## Tokens CSS utilizados

Los componentes usan tokens semanticos de kore-theme:

- `--kore-border` — bordes de tabla
- `--kore-surface` — fondo de tabla
- `--kore-muted` / `--kore-muted-fg` — fondo y texto de headers
- `--kore-fg` — texto de celdas
- `--kore-primary` / `--kore-primary-fg` — pagina activa en paginacion
- `--kore-ring` — focus ring en controles

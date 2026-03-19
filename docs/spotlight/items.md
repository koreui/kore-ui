# SpotlightItem — API

Builder fluido para definir ítems del Spotlight. El ID se genera con `Str::slug($name)` para que sea estable entre sesiones (historial localStorage).

## API completa

```php
SpotlightItem::make('Nombre del item')
    // Contenido
    ->description('Descripción opcional')
    ->icon('user-plus')        // Nombre de blade-lucide-icons
    ->group('Acciones')        // Sección en la lista
    ->shortcut('⌘U')           // Badge visual de atajo

    // Acciones (mutuamente excluyentes)
    ->route('users.create')                      // Navegar a ruta nombrada
    ->route('users.show', ['id' => 1])           // Con parámetros
    ->url('https://example.com')                 // URL externa (misma pestaña)
    ->url('https://example.com', true)           // URL externa (nueva pestaña)
    ->action('exportCsv')                        // Llamar método Livewire
    ->action('createUser', ['role' => 'admin'])  // Con params
    ->dispatch('kore:open', ['name' => 'overlays.my-modal']) // Evento Alpine/Livewire (nombre del componente Livewire)

    // Búsqueda
    ->synonym('crear', 'nuevo', 'agregar')       // Términos extra para fuzzy

    // Visibilidad
    ->hidden()             // Solo aparece en búsqueda (no en lista vacía)
    ->when($condition)     // Solo si la condición es true
    ->gate('create-users') // Solo si el usuario pasa el gate

    // Multi-paso
    ->dependency(SpotlightDependency::search(...))
    ->dependency(SpotlightDependency::input(...))
    ->dependency(SpotlightDependency::select(...));
```

## Tipos de acción

| Método | Comportamiento |
|---|---|
| `->route('name', $params)` | `window.location.href = route(...)` vía Livewire redirect |
| `->url($url)` | `window.location.href = $url` |
| `->url($url, true)` | `window.open($url, '_blank')` |
| `->action('method', $params)` | `$wire.call('method', ...params)` — método en el componente Livewire caller |
| `->dispatch('event', $data)` | `$dispatch('event', data)` — evento Alpine/Livewire |

## ID estable para historial

El ID se genera con `Str::slug($name)`. Para ítems con el mismo nombre, el ID será el mismo entre sesiones, permitiendo que el historial del localStorage haga match.

Si tienes dos ítems con el mismo nombre, el segundo sobreescribirá el historial del primero. Usa nombres únicos o sobreescribe el ID vía subclase si es necesario.

## Visibilidad y autorización

```php
// Solo incluir si hay filas seleccionadas (contexto del componente)
SpotlightItem::make('Exportar selección')
    ->when($this->livewireComponent?->hasSelection() ?? false)

// Solo si el usuario tiene el permiso
SpotlightItem::make('Crear usuario')
    ->gate('create-users')

// Combinado
SpotlightItem::make('Eliminar')
    ->when(!$isReadOnly)
    ->gate('delete-records')
```

Los items filtrados por `when()` o `gate()` nunca llegan al frontend (filtrado en `SpotlightProvider::toArray()`).

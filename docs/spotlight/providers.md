# SpotlightProvider — Sistema de Providers

Los providers son clases PHP que generan los items del Spotlight bajo demanda. Permiten separar responsabilidades, son testeables y pueden recibir inyección de dependencias.

## Abstract class base

```php
abstract class SpotlightProvider
{
    public function __construct(
        protected readonly ?Component $livewireComponent = null
    ) {}

    // Items que siempre se muestran (input vacío)
    public function items(): array { return []; }

    // Items filtrados por query — default devuelve items(), Alpine filtra
    // Override para búsqueda server-side
    public function search(string $query): array { return $this->items(); }

    // Orden entre providers (menor = primero)
    public function priority(): int { return 100; }

    // Nombre del grupo/sección
    public function group(): string { return 'General'; }
}
```

## Ejemplos

### Provider de navegación (local, sin servidor)

```php
class NavigationProvider extends SpotlightProvider
{
    public function group(): string { return 'Navegación'; }
    public function priority(): int { return 10; }

    public function items(): array
    {
        return [
            SpotlightItem::make('Inicio')->icon('home')->route('home'),
            SpotlightItem::make('Usuarios')->icon('users')->route('users.index')->gate('view-users'),
            SpotlightItem::make('Reportes')->icon('bar-chart-2')->route('reports.index')->shortcut('⌘R'),
        ];
    }
}
```

### Provider de acciones (usa contexto del componente Livewire)

```php
class ActionProvider extends SpotlightProvider
{
    public function group(): string { return 'Acciones'; }
    public function priority(): int { return 20; }

    public function items(): array
    {
        return [
            SpotlightItem::make('Crear usuario')
                ->icon('user-plus')
                ->shortcut('⌘U')
                ->action('openCreateUser')
                ->gate('create-users'),

            SpotlightItem::make('Exportar CSV')
                ->icon('download')
                ->action('exportCsv')
                ->when($this->livewireComponent?->hasSelection() ?? false),
        ];
    }
}
```

### Provider de búsqueda remota (server-side)

Para providers que buscan en base de datos, sobreescribe `search()` en lugar de `items()`:

```php
class UserSearchProvider extends SpotlightProvider
{
    public function group(): string { return 'Usuarios'; }
    public function priority(): int { return 50; }

    public function items(): array { return []; }  // vacío sin input

    public function search(string $query): array
    {
        return User::search($query)->limit(5)->get()
            ->map(fn($user) => SpotlightItem::make($user->name)
                ->description($user->email)
                ->icon('user')
                ->route('users.show', $user)
            )->all();
    }
}
```

> **Nota:** La diferencia entre `items()` y `search()`: `items()` se carga en el mount y Alpine filtra con fuzzy search. `search()` se llama con debounce por $wire — es para consultas pesadas a BD.

## Registro

En `config/kore-ui.php`:

```php
'spotlight' => [
    'providers' => [
        \App\Spotlight\NavigationProvider::class,
        \App\Spotlight\ActionProvider::class,
        \App\Spotlight\UserSearchProvider::class,
    ],
],
```

O por instancia en el componente:

```blade
<x-kore::spotlight :providers="[\App\Spotlight\NavigationProvider::class]" />
```

## Prioridad y grupos

- `priority()` determina el orden en que aparecen los grupos (menor número = arriba).
- `group()` del provider se usa como grupo para los items que no definen su propio grupo.
- Si un `SpotlightItem` define `->group('Custom')`, ese grupo se respeta.

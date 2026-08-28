# Breadcrumbs

Sistema de navegacion jerarquica con componente Blade visual y sistema de registro PHP.

---

## Uso basico

### Auto-resolve desde registry

```blade
{{-- Resuelve automaticamente basado en la ruta actual --}}
<x-kore::breadcrumbs />
```

### Items manuales

```blade
<x-kore::breadcrumbs :items="[
    ['label' => 'Inicio', 'url' => '/', 'icon' => 'home'],
    ['label' => 'Productos', 'url' => '/products'],
    ['label' => 'Detalle'],
]" />
```

---

## Props

| Prop | Tipo | Default | Descripcion |
|---|---|---|---|
| `items` | array\|Collection\|null | `null` | Items manuales. Si null, auto-resolve desde registry |
| `separator` | string | `'icon:chevron-right'` | Separador: texto o `icon:nombre-lucide` |
| `separator-class` | string\|null | `null` | Clases CSS adicionales para el separador |
| `size` | string | `'md'` | Tamano: `xs`, `sm`, `md`, `lg` |
| `max-items` | int\|null | `null` | Maximo visible. Si hay mas, colapsa con ellipsis |
| `json-ld` | bool | `false` | Genera JSON-LD structured data para SEO |

---

## Tamanos

```blade
<x-kore::breadcrumbs :items="$items" size="xs" />
<x-kore::breadcrumbs :items="$items" size="sm" />
<x-kore::breadcrumbs :items="$items" size="md" />
<x-kore::breadcrumbs :items="$items" size="lg" />
```

| Size | Font | Gap | Icono |
|---|---|---|---|
| `xs` | text-xs | gap-0.5 | size-3 |
| `sm` | text-sm | gap-1 | size-3.5 |
| `md` | text-sm | gap-1.5 | size-4 |
| `lg` | text-base | gap-2 | size-5 |

---

## Separadores

```blade
{{-- Icono (default) --}}
<x-kore::breadcrumbs separator="icon:chevron-right" />

{{-- Texto --}}
<x-kore::breadcrumbs separator="/" />
<x-kore::breadcrumbs separator=">" />

{{-- Otro icono --}}
<x-kore::breadcrumbs separator="icon:slash" />
```

---

## Collapsible

Cuando hay muchos niveles, usa `max-items` para colapsar los intermedios con un boton de ellipsis.

```blade
<x-kore::breadcrumbs :max-items="4" />
```

Con 7 items y `max-items="4"`:
- Muestra: Home > **...** > Subcategoria > Producto
- Al click en `...`: Home > Tienda > Ropa > Categoria > Subcategoria > Producto

---

## JSON-LD (SEO)

```blade
<x-kore::breadcrumbs :json-ld="true" />
```

Genera un `<script type="application/ld+json">` con structured data segun las guias de Google.

---

## Slots

```blade
<x-kore::breadcrumbs>
    <x-slot:left>
        <x-lucide-layout-dashboard class="size-4 text-kore-muted-foreground mr-2" />
    </x-slot:left>
    <x-slot:right>
        <span class="ml-2 text-xs bg-kore-primary/10 text-kore-primary px-2 py-0.5 rounded-full">
            Beta
        </span>
    </x-slot:right>
</x-kore::breadcrumbs>
```

---

## Sistema de registro PHP

### Definir breadcrumbs

Crea `routes/kore-breadcrumbs.php` (o el archivo configurado en `kore-ui.breadcrumbs.files`):

```php
use KoreUi\Breadcrumbs\BreadcrumbTrail;
use KoreUi\Facades\Kore;

// Raiz
Kore::breadcrumbs()
    ->for('home', fn (BreadcrumbTrail $trail) => $trail
        ->push('Inicio', route('home'), icon: 'home')
    );

// Con herencia
Kore::breadcrumbs()
    ->for('users.index', fn (BreadcrumbTrail $trail) => $trail
        ->parent('home')
        ->push('Usuarios', route('users.index'), icon: 'users')
    );

// Con route model binding
Kore::breadcrumbs()
    ->for('users.show', fn (BreadcrumbTrail $trail, User $user) => $trail
        ->parent('users.index')
        ->push($user->name)
    );
```

### Before / After callbacks

```php
// Siempre agregar "Home" al inicio
Kore::breadcrumbs()->before(fn (BreadcrumbTrail $trail) => $trail
    ->push('Inicio', route('home'), icon: 'home')
);

// Agregar paginacion
Kore::breadcrumbs()->after(function (BreadcrumbTrail $trail) {
    $page = (int) request('page', 1);
    if ($page > 1) {
        $trail->push("Pagina $page");
    }
});
```

### Macros

```php
Kore::breadcrumbs()->macro('resource', function (string $name, string $label) {
    $this->for("{$name}.index", fn (BreadcrumbTrail $trail) => $trail
        ->push($label, route("{$name}.index"))
    );
    $this->for("{$name}.create", fn (BreadcrumbTrail $trail) => $trail
        ->parent("{$name}.index")
        ->push('Crear')
    );
    $this->for("{$name}.show", fn (BreadcrumbTrail $trail, $model) => $trail
        ->parent("{$name}.index")
        ->push($model->name ?? "#{$model->id}")
    );
    $this->for("{$name}.edit", fn (BreadcrumbTrail $trail, $model) => $trail
        ->parent("{$name}.show", $model)
        ->push('Editar')
    );
});

Kore::breadcrumbs()->resource('users', 'Usuarios');
```

### Helpers

```php
// Facade
Kore::breadcrumbs()->exists('users.index');
Kore::breadcrumbs()->current();
Kore::breadcrumbs()->generate('users.show', $user);

// Helper function
kore_breadcrumbs()->exists();

// Override de ruta (error pages)
Kore::breadcrumbs()->setCurrentRoute('errors.404');
Kore::breadcrumbs()->clearCurrentRoute();
```

---

## Configuracion

En `config/kore-ui.php`:

```php
'breadcrumbs' => [
    // Desactivar el sistema de registro (el componente visual sigue funcionando con :items manuales)
    'enabled' => true,

    // Archivo con las definiciones. Usa nombre propio para no chocar con otras librerias.
    'files' => base_path('routes/kore-breadcrumbs.php'),

    'separator' => 'icon:chevron-right',
    'size' => 'md',
    'json_ld' => false,
    'exceptions' => [
        'missing' => true,
        'unnamed' => true,
        'duplicate' => true,
    ],
],
```

## Accesibilidad

- El contenedor es un `<nav>` con nombre: «Ruta de navegación», de
  `kore-ui.ui.translations.breadcrumb`. Decía «Breadcrumb», en inglés, hasta la
  2.0.0.
- El último item lleva `aria-current="page"`.
- Los separadores son `aria-hidden`: son decoración, y un lector que los leyera
  diría «chevron derecha» entre cada dos niveles.
- El botón que despliega los pasos ocultos también tiene nombre
  (`ui.translations.breadcrumb_expand`).

> El desplegable de `max-items` **no desplegaba nada** hasta la 2.0.0: el bloque
> metía los `<li>` ocultos dentro de otro `<li>`, y eso es HTML inválido — el
> parser cierra el `<li>` exterior al encontrarse el interior, así que el botón
> acababa fuera del elemento que llevaba el `x-data`. No daba error de
> compilación y solo se veía mirando el DOM ya parseado.

---

### Convivencia con otras librerias

Si ya usas `diglactic/laravel-breadcrumbs` u otra libreria que carga `routes/breadcrumbs.php`, no hay conflicto: koreUi usa su propio archivo (`routes/kore-breadcrumbs.php`).

Si prefieres desactivar el sistema de registro de koreUi por completo y solo usar el componente visual con items manuales:

```php
'breadcrumbs' => [
    'enabled' => false,
],
```

El componente `<x-kore::breadcrumbs :items="$items" />` sigue funcionando — solo se desactiva el singleton `BreadcrumbManager` y la carga del archivo de definiciones.

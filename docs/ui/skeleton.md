# Skeleton

Placeholder animado que muestra la estructura del contenido mientras se carga.

## Uso básico

```blade
<x-kore::skeleton />
<x-kore::skeleton shape="circle" size="3rem" />
<x-kore::skeleton shape="text" :lines="3" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `shape` | `string` | `rect` | Forma: `rect`, `circle`, `text` |
| `width` | `string\|null` | `null` | Ancho CSS (ej: `200px`, `100%`) |
| `height` | `string\|null` | `null` | Alto CSS (ej: `1rem`, `40px`) |
| `size` | `string\|null` | `null` | Atajo para width=height (ideal para circles) |
| `lines` | `int` | `1` | Número de líneas para `shape="text"` |
| `animation` | `string` | `shimmer` | Tipo de animación: `shimmer`, `pulse`, `none` |
| `rounded` | `string\|null` | `null` | Border radius personalizado |

## Formas

```blade
{{-- Rectángulo (default) --}}
<x-kore::skeleton width="100%" height="1rem" />

{{-- Círculo --}}
<x-kore::skeleton shape="circle" size="3rem" />

{{-- Texto multilínea --}}
<x-kore::skeleton shape="text" :lines="4" />
```

## Composición (Card skeleton)

```blade
<div class="rounded-kore-lg border border-kore-border p-4 space-y-4">
    <x-kore::skeleton width="100%" height="8rem" />
    <x-kore::skeleton shape="text" :lines="2" />
    <div class="flex items-center gap-3">
        <x-kore::skeleton shape="circle" size="2.5rem" />
        <div class="flex-1 space-y-2">
            <x-kore::skeleton width="60%" height="0.75rem" />
            <x-kore::skeleton width="40%" height="0.75rem" />
        </div>
    </div>
</div>
```

## Animaciones

```blade
{{-- Shimmer (default) — gradiente que barre de izquierda a derecha --}}
<x-kore::skeleton animation="shimmer" />

{{-- Pulse — fade de opacidad --}}
<x-kore::skeleton animation="pulse" />

{{-- Sin animación --}}
<x-kore::skeleton animation="none" />
```

## Configuración

En `config/kore-ui.php`:

```php
'skeleton' => [
    'animation' => 'shimmer',  // shimmer|pulse|none
],
```

## Siluetas de componente

Montar a mano la silueta de una tarjeta o de una tabla —barra de título, tres
líneas, cinco filas de cuatro columnas— es trabajo repetido que además nunca
queda igual que el componente real: al llegar los datos, la página salta.

Los componentes con una forma reconocible saben dibujarse vacíos. Basta el prop
`skeleton`:

```blade
<x-kore::card title="Ventas" :skeleton="$cargando">
    {{-- Esto no se renderiza mientras `skeleton` esté activo --}}
    <x-kore::chart :data="$ventas">…</x-kore::chart>
</x-kore::card>
```

| Componente | Prop | Qué elige el entero |
|---|---|---|
| `<x-kore::card>` | `skeleton` | líneas del cuerpo (3) |
| `<x-kore::stats>` | `skeleton` | — |
| `<x-kore::table>` | `skeleton` | filas (5); las columnas salen de `:headers` |
| `<x-kore::stepper>` | `skeleton` | pasos (3) |
| `<x-kore::chart>` | `skeleton` | barras (7, o las filas de `:data` si las hay) |

`skeleton` a secas usa el valor por defecto; `:skeleton="8"` lo cambia;
`:skeleton="false"` —o no ponerlo— deja el componente como siempre.

Cada silueta hereda el marco del componente: el borde, la sombra, el radio y el
relleno son los mismos, así que lo que ocupa la silueta es lo que ocupará el
contenido.

### No es lo mismo que `loading`

Son dos momentos distintos y conviven a propósito:

| | Cuándo | Qué hace |
|---|---|---|
| `loading` | ya hay contenido pintado y se está refrescando | echa un velo por encima, con un spinner |
| `skeleton` | todavía no hay nada que enseñar | dibuja la forma de lo que va a llegar |

Poner `loading` sobre una tarjeta vacía deja un recuadro en blanco con una rueda
girando; poner `skeleton` sobre datos que ya estaban los hace desaparecer y
volver.

### Sueltas, sin el componente

Las siluetas son componentes por derecho propio. Sirven cuando ni siquiera se
sabe cuántos elementos habrá:

```blade
@foreach(range(1, 6) as $i)
    <x-kore::skeleton.card :lines="2" />
@endforeach
```

Disponibles: `skeleton.card`, `skeleton.stats`, `skeleton.table`,
`skeleton.stepper` y `skeleton.chart`, cada una con sus propios props
(`:lines`, `:rows`, `:columns`, `:steps`, `:bars`, `image`, `footer`, `legend`…).

### Accesibilidad

Cada silueta se anuncia sola: la raíz lleva `role="status"`, `aria-busy="true"`
y un texto para lectores (`config('kore-ui.ui.translations.loading')`). Las
barras van con `aria-hidden`, porque no significan nada.

Las alturas de las barras del gráfico son fijas, no aleatorias: una silueta que
cambia en cada repintado parpadea, y con Livewire se repinta más de lo que uno
cree.

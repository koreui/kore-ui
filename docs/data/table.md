# Table

Componente Blade estatico para tablas simples. Recibe arrays o collections y renderiza headers, filas, slots por celda, estado vacio y paginacion.

---

## Uso basico

```blade
<x-kore::table
    :headers="['Nombre', 'Email', 'Rol']"
    :rows="[
        ['Nombre' => 'Ana', 'Email' => 'ana@test.com', 'Rol' => 'Admin'],
        ['Nombre' => 'Luis', 'Email' => 'luis@test.com', 'Rol' => 'Editor'],
    ]"
/>
```

---

## Props

| Prop | Tipo | Default | Descripcion |
|---|---|---|---|
| `headers` | `array` | `[]` | Headers de la tabla (strings o arrays asociativos) |
| `rows` | `array\|Collection` | `[]` | Filas de datos |
| `striped` | `bool` | `false` | Filas alternas con fondo |
| `hoverable` | `bool` | `true` | Highlight al hover |
| `bordered` | `bool\|null` | `null` | Líneas verticales entre columnas. Ver [aspecto](../ui/look.md) |
| `headerless` | `bool` | `false` | Oculta el header |
| `compact` | `bool\|null` | `null` | Atajo para `density="compact"`; `density` explícito manda sobre él. Ver [aspecto](../ui/look.md) |
| `responsive` | `bool` | `true` | Scroll horizontal en pantallas pequenas |
| `density` | `string\|null` | `config(normal)` | Densidad: `compact`, `normal`, `relaxed` |
| `emptyText` | `string\|null` | `config` | Texto cuando no hay filas |
| `emptyIcon` | `string\|null` | `config(inbox)` | Icono del estado vacio (Lucide) |
| `caption` | `string\|null` | `null` | Nombre de la tabla, en un `<caption>` |
| `captionHidden` | `bool` | `false` | Deja el caption solo para lectores de pantalla |
| `skeleton` | `bool\|int` | `false` | Silueta mientras no hay filas; el entero elige cuántas, y las columnas salen de `:headers`. Ver [skeleton](../ui/skeleton.md#siluetas-de-componente) |
| `shadow` | `bool\|null` | `null` | Sombra de la superficie. Ver [aspecto](../ui/look.md) |

### Nombrar la tabla

Un `aria-label` escrito en la etiqueta **no** nombra la tabla: `$attributes` se vuelca en el `<div>` envolvente, que no tiene rol y por tanto no acepta nombre. Sin `caption`, un lector de pantalla anuncia «tabla, 3 columnas, 3 filas» y nada más.

```blade
{{-- Visible, encima de la tabla --}}
<x-kore::table caption="Altas del equipo" :headers="$headers" :rows="$rows" />

{{-- Solo para lectores de pantalla --}}
<x-kore::table caption="Altas del equipo" :captionHidden="true" :headers="$headers" :rows="$rows" />
```

---

## Headers

### Formato simple (strings)

```blade
<x-kore::table :headers="['Nombre', 'Email', 'Acciones']" :rows="$rows" />
```

Las keys de cada fila se resuelven por indice (0, 1, 2...).

### Formato con key y alineacion

```blade
<x-kore::table
    :headers="[
        ['key' => 'name', 'label' => 'Nombre'],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'total', 'label' => 'Total', 'align' => 'right'],
    ]"
    :rows="$rows"
/>
```

Opciones de `align`: `left` (default), `center`, `right`.

---

## Slots por celda

Para personalizar el contenido de una celda, usa un slot con nombre `cell-{key}` en camelCase:

```blade
<x-kore::table
    :headers="[
        ['key' => 'name', 'label' => 'Nombre'],
        ['key' => 'status', 'label' => 'Estado'],
    ]"
    :rows="$users"
>
    <x-slot:cellStatus>
        <x-kore::badge color="success">Activo</x-kore::badge>
    </x-slot:cellStatus>
</x-kore::table>
```

La convencion es: `cell-` + key con puntos reemplazados por guiones, convertido a camelCase. Ejemplo: key `user.name` → slot `cellUserName`.

---

## Striped y hoverable

```blade
{{-- Filas alternas con fondo --}}
<x-kore::table :headers="$headers" :rows="$rows" striped />

{{-- Sin hover --}}
<x-kore::table :headers="$headers" :rows="$rows" :hoverable="false" />

{{-- Ambos --}}
<x-kore::table :headers="$headers" :rows="$rows" striped :hoverable="false" />
```

---

## Density

```blade
<x-kore::table :headers="$headers" :rows="$rows" density="compact" />
<x-kore::table :headers="$headers" :rows="$rows" density="relaxed" />

{{-- Shortcut --}}
<x-kore::table :headers="$headers" :rows="$rows" compact />
```

---

## Sin header

```blade
<x-kore::table :headers="$headers" :rows="$rows" headerless />
```

---

## Estado vacio

Cuando `rows` esta vacio, se muestra un `<x-kore::empty-state>` automaticamente.

```blade
<x-kore::table
    :headers="['Nombre']"
    :rows="[]"
    empty-text="No hay usuarios"
    empty-icon="users"
/>
```

---

## Footer

```blade
<x-kore::table :headers="$headers" :rows="$rows">
    <x-slot:footer>
        <tr>
            <td colspan="3" class="px-4 py-2 text-sm text-kore-muted-fg">
                Total: {{ count($rows) }} registros
            </td>
        </tr>
    </x-slot:footer>
</x-kore::table>
```

---

## Sub-componentes

Los sub-componentes se usan internamente pero estan disponibles para composicion manual:

| Componente | Descripcion |
|---|---|
| `<x-kore::table.header>` | Celda de header con sorting opcional |
| `<x-kore::table.row>` | Fila con striped/hoverable |
| `<x-kore::table.cell>` | Celda con align y density |
| `<x-kore::table.empty>` | Fila de estado vacio |
| `<x-kore::table.pagination>` | Barra de paginacion |

### table.header props

| Prop | Tipo | Default |
|---|---|---|
| `label` | `string` | `''` |
| `align` | `string` | `'left'` |
| `sortable` | `bool` | `false` |
| `sortDirection` | `string\|null` | `null` |
| `densityClasses` | `string` | `'px-4 py-2 text-xs'` |

### table.cell props

| Prop | Tipo | Default |
|---|---|---|
| `align` | `string` | `'left'` |
| `wrap` | `bool` | `true` |
| `densityClasses` | `string` | `'px-4 py-2.5 text-sm'` |

### table.pagination props

| Prop | Tipo | Default |
|---|---|---|
| `paginator` | `Paginator\|null` | `null` |
| `perPage` | `int` | `25` |
| `perPageOptions` | `array` | `config` |
| `showingText` | `string\|null` | `null` |

## Aspecto

`bordered`, `shadow`, `padding` y `compact` se pueden fijar también para toda la
librería desde `config/kore-ui.php`. Ver [aspecto de las superficies](../ui/look.md).

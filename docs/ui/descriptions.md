# Descriptions

Vista de detalle de solo lectura para mostrar pares etiqueta/valor de un registro (páginas "ver", perfiles, resúmenes). Es el espejo read-only del formulario y el compañero natural del DataTable. Ofrece dos APIs: subcomponentes (para valores con formato rico) y un atajo data-driven con `:items`.

## Uso básico

```blade
<x-kore::descriptions title="Usuario">
    <x-kore::descriptions.item label="Nombre" value="Oscar Villa" />
    <x-kore::descriptions.item label="Email" value="oscar@mail.com" />
    <x-kore::descriptions.item label="Estado">
        <x-kore::badge label="Activo" color="success" />
    </x-kore::descriptions.item>
</x-kore::descriptions>
```

## API data-driven

Para datos simples, pasa un array a `:items`. Cada entrada acepta `label`, `value`, `icon`, `span` y `html` (para contenido crudo con formato).

```blade
<x-kore::descriptions
    title="Usuario"
    :columns="2"
    :items="[
        ['label' => 'Nombre', 'value' => 'Oscar Villa'],
        ['label' => 'Email', 'value' => 'oscar@mail.com'],
        ['label' => 'Rol', 'html' => view('components.role-badge')->render()],
    ]"
/>
```

## Props del contenedor

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `title` | `string\|null` | `null` | Encabezado opcional |
| `description` | `string\|null` | `null` | Subtítulo opcional |
| `columns` | `int` | `1` | Columnas de la rejilla (`1`, `2`, `3`) — responsive desde `sm` |
| `layout` | `string` | `horizontal` | `horizontal` (label al lado) o `vertical` (label encima) |
| `bordered` | `bool` | `false` | Rejilla con celdas y bordes tipo tarjeta |
| `size` | `string` | `md` | Densidad: `sm`, `md`, `lg` |
| `items` | `array\|null` | `null` | Atajo data-driven; si se usa, ignora el slot |

## Props de item

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string` | `''` | Etiqueta del campo |
| `value` | `string\|null` | `null` | Valor simple; si es `null` se usa el slot (para badge, boolean, etc.) |
| `icon` | `string\|null` | `null` | Icono Lucide junto a la etiqueta |
| `span` | `int` | `1` | Columnas que ocupa la entrada |

> Los items heredan `layout`, `bordered` y `size` del contenedor vía `@aware`, así que no hace falta repetirlos en cada uno.

## Layout vertical

```blade
<x-kore::descriptions layout="vertical" :columns="3">
    <x-kore::descriptions.item label="Plan" value="Pro" />
    <x-kore::descriptions.item label="Ciclo" value="Anual" />
    <x-kore::descriptions.item label="Renovación" value="15 jul 2026" />
</x-kore::descriptions>
```

## Bordered (rejilla)

```blade
<x-kore::descriptions title="Factura" :columns="2" bordered>
    <x-kore::descriptions.item label="Folio" value="A-1024" />
    <x-kore::descriptions.item label="Fecha" value="15 jul 2026" />
    <x-kore::descriptions.item label="Notas" span="2" value="Entrega en almacén central" />
</x-kore::descriptions>
```

## Valores con formato

El slot del item admite cualquier componente, ideal para estados, verificaciones o avatares:

```blade
<x-kore::descriptions :columns="2">
    <x-kore::descriptions.item label="Estado">
        <x-kore::badge label="Activo" color="success" />
    </x-kore::descriptions.item>
    <x-kore::descriptions.item label="Verificado">
        <x-kore::boolean :value="true" />
    </x-kore::descriptions.item>
</x-kore::descriptions>
```

## Aspecto

`bordered`, `shadow`, `padding` y `compact` se pueden fijar también para toda la
librería desde `config/kore-ui.php`. Ver [aspecto de las superficies](look.md).

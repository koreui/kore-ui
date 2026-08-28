# Avatar Group

Varios avatares solapados, con un hueco al final para el «y N más».

## Uso básico

```blade
<x-kore::avatar-group>
    <x-kore::avatar src="/ana.jpg" name="Ana Ruiz" />
    <x-kore::avatar src="/luis.jpg" name="Luis Peña" />
    <x-kore::avatar name="Marta Gil" />
</x-kore::avatar-group>
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `size` | `string` | `md` | Cuánto se solapan: `xs`, `sm`, `md`, `lg`, `xl` |

## Slots

| Slot | Descripción |
|------|-------------|
| `default` | Los avatares |
| `overflow` | Lo que va al final, fuera del solapamiento. Normalmente un «+5» |

## `size` es el solape, no el avatar

Esta es la parte que sorprende: el `size` del grupo **no cambia el tamaño de los
avatares**, solo cuánto se montan unos sobre otros. El tamaño de cada avatar lo
pone cada avatar.

Con avatares grandes y el solape por defecto, el resultado queda espaciado; hay
que decirlo en los dos sitios:

```blade
<x-kore::avatar-group size="lg">
    <x-kore::avatar size="lg" name="Ana Ruiz" />
    <x-kore::avatar size="lg" name="Luis Peña" />
</x-kore::avatar-group>
```

| Tamaño | Solape |
|--------|--------|
| `xs` | `-space-x-1.5` |
| `sm` | `-space-x-2` |
| `md` | `-space-x-3` |
| `lg` | `-space-x-4` |
| `xl` | `-space-x-5` |

## El resto

```blade
<x-kore::avatar-group>
    <x-kore::avatar src="/ana.jpg" name="Ana Ruiz" />
    <x-kore::avatar src="/luis.jpg" name="Luis Peña" />

    <x-slot:overflow>
        <x-kore::avatar name="+5" />
    </x-slot:overflow>
</x-kore::avatar-group>
```

El slot `overflow` va detrás de los avatares del slot principal, en su propio
contenedor y con el mismo solape. Recibe los estilos del grupo igual que los
demás hijos —anillo y esquinas redondeadas—, así que no hace falta que sea un
avatar: vale cualquier cosa que quepa en ese hueco.

## El anillo

El grupo le pone a cada hijo un `ring-2` del color de la superficie
(`ring-kore-surface`) y lo redondea. Es lo que separa un avatar del de al lado
cuando se solapan.

Ojo si el grupo va sobre un fondo que no es el de la superficie: el anillo
seguirá siendo del color de la superficie y se verá el corte. Se arregla
pasándole otro anillo por la etiqueta, que se suma a los del componente:

```blade
<x-kore::avatar-group class="[&>*]:ring-kore-bg">…</x-kore::avatar-group>
```

Ver [avatar](avatar.md) para las props de cada avatar: imagen, iniciales,
presencia y tamaños.

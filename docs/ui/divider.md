# Divider

Separador horizontal o vertical con soporte para etiquetas, iconos y contenido personalizado.

## Uso básico

```blade
<x-kore::divider />
<x-kore::divider label="O continúa con" />
<x-kore::divider icon="star" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Texto del separador |
| `icon` | `string\|null` | `null` | Icono Lucide |
| `align` | `string` | `center` | Alineación del contenido: `left`, `center`, `right` |
| `orientation` | `string` | `horizontal` | Orientación: `horizontal`, `vertical` |
| `type` | `string` | `solid` | Estilo de línea: `solid`, `dashed`, `dotted` |

## Con etiqueta y alineación

```blade
<x-kore::divider label="Sección" align="left" />
<x-kore::divider label="Medio" align="center" />
<x-kore::divider label="Final" align="right" />
```

## Con icono

```blade
<x-kore::divider icon="arrow-down" />
<x-kore::divider icon="star" type="dashed" />
```

## Contenido personalizado (slot)

```blade
<x-kore::divider>
    <x-kore::badge label="Nuevo" color="success" />
</x-kore::divider>
```

## Vertical

```blade
<div class="flex items-center gap-4 h-20">
    <span>Opción A</span>
    <x-kore::divider orientation="vertical" />
    <span>Opción B</span>
</div>
```

> **Nota:** El modo vertical no soporta slots ni label/icon. Solo renderiza la línea divisoria.

## Tipos de línea

```blade
<x-kore::divider type="solid" />
<x-kore::divider type="dashed" label="Sección" />
<x-kore::divider type="dotted" />
```

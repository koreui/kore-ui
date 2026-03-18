# Kbd

Componente para representar teclas de teclado. Ideal para atajos y documentación de shortcuts.

## Uso básico

```blade
<x-kore::kbd>K</x-kore::kbd>
<x-kore::kbd>Ctrl</x-kore::kbd> + <x-kore::kbd>C</x-kore::kbd>
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `size` | `string` | `config(md)` | Tamaño: `sm`, `md`, `lg` |

## Tamaños

```blade
<x-kore::kbd size="sm">A</x-kore::kbd>
<x-kore::kbd>A</x-kore::kbd>
<x-kore::kbd size="lg">A</x-kore::kbd>
```

## Combinaciones

```blade
<x-kore::kbd>⌘</x-kore::kbd> + <x-kore::kbd>Shift</x-kore::kbd> + <x-kore::kbd>P</x-kore::kbd>
```

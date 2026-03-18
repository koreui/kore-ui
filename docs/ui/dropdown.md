# Dropdown

Menú desplegable con posicionamiento inteligente, navegación por teclado y teleport al body.

## Uso básico

```blade
<x-kore::dropdown>
    <x-slot:trigger>
        <x-kore::button label="Acciones" icon-right="chevron-down" />
    </x-slot:trigger>

    <x-kore::dropdown.item label="Editar" icon="pencil" />
    <x-kore::dropdown.item label="Duplicar" icon="copy" />
    <x-kore::dropdown.separator />
    <x-kore::dropdown.item label="Eliminar" icon="trash" />
</x-kore::dropdown>
```

## Props (Dropdown)

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `position` | `string` | `config(bottom-start)` | Posición: `bottom-start`, `bottom-end`, `top-start`, `top-end` |
| `width` | `string` | `config(auto)` | Ancho: `auto`, `sm`, `md`, `lg` |
| `persistent` | `bool` | `false` | No cierra al hacer click fuera |

## Props (Item)

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `icon` | `string\|null` | `null` | Icono Lucide |
| `label` | `string\|null` | `null` | Texto |
| `description` | `string\|null` | `null` | Texto secundario |
| `href` | `string\|null` | `null` | URL — renderiza como `<a>` |
| `disabled` | `bool` | `false` | Deshabilitado |

## Sub-componentes

- `dropdown.item`: Elemento del menú
- `dropdown.separator`: Línea divisora
- `dropdown.header`: Encabezado de sección

## Teclado

- `ArrowDown/Up`: Navegar items
- `Enter/Space`: Seleccionar item
- `Escape`: Cerrar
- `Tab`: Cerrar

## Con secciones

```blade
<x-kore::dropdown>
    <x-slot:trigger>
        <x-kore::button label="Menu" />
    </x-slot:trigger>

    <x-kore::dropdown.header label="Navegación" />
    <x-kore::dropdown.item label="Inicio" icon="home" href="/" />
    <x-kore::dropdown.item label="Perfil" icon="user" href="/profile" />

    <x-kore::dropdown.separator />

    <x-kore::dropdown.header label="Acciones" />
    <x-kore::dropdown.item label="Configuración" icon="settings" />
    <x-kore::dropdown.item label="Cerrar sesión" icon="log-out" />
</x-kore::dropdown>
```

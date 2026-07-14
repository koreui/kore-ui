# Navbar

La barra superior del shell. Trae el botón de menú incorporado y tres zonas para colocar el resto.

## Uso básico

```blade
<x-kore::navbar>
    <x-slot:end>
        <x-kore::theme-switch size="sm" />
        <x-kore::avatar name="Ana García" size="sm" />
    </x-slot:end>
</x-kore::navbar>
```

Va en el slot `navbar` del [shell](shell.md), que la coloca sobre el contenido (no sobre el sidebar).

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `sticky` | `bool` | `true` | Se queda arriba al hacer scroll |
| `bordered` | `bool` | `true` | Borde inferior |
| `toggle` | `bool` | `true` | Incluir el botón de menú |
| `toggleFor` | `string` | `'main'` | A qué sidebar controla ese botón |

## Slots

| Slot | Descripción |
|------|-------------|
| `start` | Izquierda, después del botón de menú (breadcrumbs, título de página…) |
| `default` | Centro (una búsqueda, por ejemplo) |
| `end` | Derecha (tema, avatar, notificaciones…) |

## El botón de menú

Viene incluido y **hace lo correcto según el dispositivo**: en escritorio colapsa el sidebar y en móvil abre el drawer. Es lo que se espera de un botón de menú, y evita tener que poner dos.

Si prefieres colocarlo en otro sitio, quítalo y usa el componente suelto:

```blade
<x-kore::navbar :toggle="false">
    <x-slot:start>
        <x-kore::sidebar.toggle />
        <x-kore::breadcrumbs />
    </x-slot:start>
</x-kore::navbar>
```

`<x-kore::sidebar.toggle>` habla con el store de Alpine, así que **funciona desde cualquier parte de la página**: no necesita estar dentro del sidebar ni del shell.

## Un detalle de accesibilidad

La navbar reutiliza el `<x-kore::toolbar>` por dentro, pero **le quita el `role="toolbar"`**.

Ese rol le promete a un lector de pantalla un widget que se recorre con las flechas del teclado. Una cabecera de página no lo es, y anunciarlo así rompería la navegación de quien depende de ello. La navbar se emite como `<header>`, que es lo que realmente es.

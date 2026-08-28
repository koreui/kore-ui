# Sidebar Item

Un enlace de navegación del sidebar. Si le metes hijos, se convierte en un desplegable. Detecta solo si apunta a la página actual.

## Uso básico

```blade
<x-kore::sidebar.item label="Usuarios" icon="users" route="users.index" />
<x-kore::sidebar.item label="Documentación" icon="book" href="https://…" target="_blank" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string` | `null` | El texto del item |
| `icon` | `string` | `null` | Icono de Lucide (`'users'`, `'settings'`…) |
| `href` | `string` | `null` | URL directa |
| `route` | `string` | `null` | Nombre de ruta de Laravel |
| `routeParams` | `array` | `[]` | Parámetros de la ruta |
| `match` | `string\|array` | `null` | Patrón para decidir si está activo (`'users.*'`) |
| `active` | `bool` | `null` | Forzar el estado activo, ignorando la detección |
| `opened` | `bool` | `false` | Abrir el desplegable por defecto |
| `badge` | `string\|int` | `null` | Contenido del badge |
| `badgeVariant` | `'soft'\|'solid'\|'dot'` | `'soft'` | Estilo del badge |
| `badgeColor` | `string` | `'primary'` | `primary`, `destructive`, `success`, `warning`, `info` |
| `badgeMax` | `int` | `99` | Tope del contador en modo iconos. Por encima, `"99+"` |
| `disabled` | `bool` | `false` | Deshabilitar |
| `target` | `string` | `null` | `_blank`, etc. Añade `rel="noopener noreferrer"` |
| `navigate` | `bool\|null` | heredado | `wire:navigate` en el enlace. Sin valor, lo hereda del sidebar y luego de la configuración |
| `smart` | `bool\|null` | heredado | Detección de la ruta activa. Misma herencia que `navigate` |

Sobre la herencia de esos dos, ver [qué heredan los items](sidebar.md#qué-heredan-los-items).

## Ruta activa

El item se marca solo cuando apunta a la página actual. El orden de prioridad es:

1. `active` explícito (puede forzar `true` **o** `false`)
2. `match` → `request()->routeIs(...)`, admite comodines
3. `route` → nombre de ruta
4. `href` → comparación de URL
5. **Un hijo activo marca al padre**

Se resuelve **en el servidor**, así que sale ya marcado en el HTML.

> **Cuidado con los comodines.** `route="users.*"` **no** es un nombre de ruta resoluble: sirve para comparar, no para generar la URL. Si lo usas como `route`, el enlace se queda sin destino. Lo correcto es separarlos:
>
> ```blade
> <x-kore::sidebar.item label="Usuarios" route="users.index" match="users.*" />
> ```
>
> Así el enlace apunta a `users.index` y sigue marcándose activo en cualquier ruta `users.*`.

## Sub-items

Mete items dentro de un item y se convierte en un desplegable. A cualquier profundidad.

```blade
<x-kore::sidebar.item label="Ajustes" icon="settings">
    <x-kore::sidebar.item label="Perfil" route="settings.profile" />

    <x-kore::sidebar.item label="Seguridad">
        <x-kore::sidebar.item label="Contraseña" route="settings.password" />
        <x-kore::sidebar.item label="Dos factores" route="settings.2fa" />
    </x-kore::sidebar.item>
</x-kore::sidebar.item>
```

**La rama que contiene la página actual sale ya abierta del servidor.** Si estás en «Contraseña», tanto «Ajustes» como «Seguridad» aparecen desplegados en el HTML, sin esperar a que arranque Alpine. Esto es lo que evita el típico menú que se despliega de golpe al cargar la página.

### Con el sidebar colapsado

Los sub-items no caben en línea, así que salen en un **panel flotante** al pasar el ratón. Los menús anidados se abren **uno al lado del otro**: entrar en «Seguridad» no cierra el panel de «Ajustes».

Los items **sin** hijos muestran su nombre en un tooltip.

## Badges

```blade
<x-kore::sidebar.item label="Usuarios" icon="users" route="users.index" badge="12" />
<x-kore::sidebar.item label="Errores" icon="bug" href="/errors" badge="3" badge-color="destructive" badge-variant="solid" />
<x-kore::sidebar.item label="Mensajes" icon="mail" href="/inbox" badge="9 sin leer" badge-variant="dot" badge-color="destructive" />
```

Al colapsar, un badge numérico **se muda a la esquina del icono**, que es el único hueco que queda:

- Un número por encima de `badgeMax` se muestra como **`99+`**. Con `100000000` el contador reventaría el icono, y de todos modos nadie lee nueve dígitos a ese tamaño.
- Un texto corto (`!`, `new`) pasa tal cual.
- Uno que no cabe ni acortado se degrada a un **punto**: al menos queda constancia de que ese item tiene algo.

El valor que se acorta es solo el visual. **Un lector de pantalla sigue anunciando el número real**, no `99+`.

La variante `dot` se ve en los dos estados, porque con el sidebar ancho no hay ninguna píldora que la sustituya.

## Accesibilidad

- El item activo lleva `aria-current="page"`.
- Los desplegables son `<button>` con `aria-expanded`, no enlaces: no navegan a ninguna parte.
- Al colapsar, la etiqueta sale del DOM, pero el enlace **conserva su nombre** mediante un texto `sr-only`. Sin eso, un lector de pantalla anunciaría solo «enlace».
- Los enlaces de un sub-menú cerrado **salen del orden de tabulación**. De lo contrario se podría tabular hasta items invisibles.

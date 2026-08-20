# Sidebar

Navegación lateral colapsable, con drawer automático en móvil y persistencia en cookie. El estado lo resuelve el servidor, así que no hay parpadeo al cargar.

## Uso básico

```blade
<x-kore::sidebar>
    <x-slot:header>
        <span class="kore-sidebar-label font-bold">Mi App</span>
        <span class="kore-sidebar-mini font-bold">M</span>
    </x-slot:header>

    <x-kore::sidebar.item label="Panel" icon="layout-dashboard" route="dashboard" />
    <x-kore::sidebar.item label="Usuarios" icon="users" route="users.index" />
</x-kore::sidebar>
```

Normalmente va dentro de un [`<x-kore::shell>`](shell.md), que es quien reserva el espacio del contenido.

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `id` | `string` | `'main'` | Identifica el sidebar en la cookie y en el store. Necesario si hay más de uno |
| `collapsible` | `bool` | `true` | Permite colapsarlo a modo iconos |
| `collapsed` | `bool` | `false` | Estado en la **primera visita**, antes de que exista cookie |
| `placement` | `'left'\|'right'` | `'left'` | Lado de la pantalla |
| `width` | `string` | `'16rem'` | Ancho expandido. Longitud CSS, no clase de Tailwind |
| `collapsedWidth` | `string` | `'4rem'` | Ancho en modo iconos |
| `breakpoint` | `'sm'\|'md'\|'lg'\|'xl'` | `'lg'` | Por debajo de esto, el sidebar es un drawer |
| `persist` | `bool` | `true` | Recordar el estado entre visitas (cookie `kore_sidebar`) |
| `smart` | `bool` | `true` | Detectar sola la ruta activa. Lo heredan los items |
| `navigate` | `bool` | `false` | Añadir `wire:navigate` a los enlaces. Lo heredan los items |
| `overlay` | `bool` | `true` | Fondo oscuro tras el drawer móvil |
| `rail` | `bool` | `false` | Modo rail: solo iconos, se expande **sobre** el contenido al pasar el ratón |
| `expandOnHover` | `bool` | `false` | Como rail, pero partiendo de expandido |
| `ariaLabel` | `string` | `'Sidebar'` | Nombre de la región de navegación |

## Slots

| Slot | Descripción |
|------|-------------|
| `header` | La marca. Suele llevar dos versiones (ver abajo) |
| `default` | Items y grupos |
| `footer` | Contenido inferior (perfil, cerrar sesión) |

## La marca al colapsar

Cuando el sidebar se reduce a iconos, el nombre completo ya no cabe. Dos clases resuelven qué se ve en cada estado:

- **`kore-sidebar-label`** → se ve con el sidebar ancho, y se desvanece al colapsar.
- **`kore-sidebar-mini`** → justo al revés: solo aparece en modo iconos.

```blade
<x-slot:header>
    <img src="/logo.svg" class="kore-sidebar-label h-8" />
    <img src="/isotipo.svg" class="kore-sidebar-mini h-8" />
</x-slot:header>
```

Sin la versión `mini`, el hueco de la cabecera se queda vacío al colapsar.

## Persistencia

El estado se guarda en la cookie **`kore_sidebar`** (y no en `localStorage`) por un motivo concreto: **la cookie viaja al servidor**. Laravel la lee al renderizar y emite el HTML con el ancho correcto ya puesto.

Con `localStorage`, el estado solo se conocería tras arrancar Alpine, así que en cada carga el sidebar aparecería ancho y se encogería a la vista. Es el mismo patrón que usan shadcn/ui y Nuxt UI, y en Laravel sale gratis.

La cookie guarda **todos** los sidebars de la app en un solo mapa (`{"main":1,"tools":0}`), y se registra en `EncryptCookies::except()` — Laravel encripta las cookies, y una escrita por JavaScript la anularía en silencio.

> La cookie es entrada no confiable: cualquiera puede editarla desde la consola. Se valida su tamaño, su forma y sus claves, y lo único que llega al HTML es un valor de un enum cerrado.

## Modo rail

En modo rail el sidebar se muestra como iconos y **se expande por encima del contenido** al pasar el ratón, sin desplazarlo. Ideal cuando el espacio horizontal es oro.

```blade
<x-kore::sidebar :rail="true">
    <x-kore::sidebar.item label="Panel" icon="layout-dashboard" route="dashboard" />
</x-kore::sidebar>
```

También se expande al llegar con el **teclado** (`Tab`), no solo con el ratón: sin eso, quien navega tabulando no vería dónde está.

## Móvil

Por debajo del `breakpoint`, el sidebar se convierte en un drawer que entra desde el lado, con fondo oscuro y su botón de cierre. Se cierra al pulsar el fondo, con `Escape`, o al navegar con `wire:navigate`. Mientras está abierto, la página de detrás no hace scroll.

Todo esto es **CSS**, no JavaScript: es correcto en el primer paint y en cada cambio de tamaño de ventana. El drawer siempre se abre a ancho completo y con las etiquetas visibles, aunque en escritorio estuviera colapsado.

## Varios sidebars

Con un `id` distinto y `placement="right"` puedes tener un panel de herramientas a la derecha:

```blade
<x-kore::shell>
    <x-slot:sidebar>
        <x-kore::sidebar id="main">…</x-kore::sidebar>
    </x-slot:sidebar>

    <x-slot:aside>
        <x-kore::sidebar id="tools" placement="right" width="20rem">…</x-kore::sidebar>
    </x-slot:aside>

    {{ $slot }}
</x-kore::shell>
```

Cada uno recuerda su estado por separado, y el shell reserva el espacio de ambos.

## Teclado

| Tecla | Acción |
|---|---|
| `↑` `↓` | Moverse entre items |
| `Home` `End` | Primer / último item |
| `→` | Abrir un sub-menú; si ya está abierto, entrar en él |
| `←` | Cerrar el sub-menú; desde un hijo, subir al padre |
| `Escape` | Cerrar el flyout o el drawer |

En un sidebar a la derecha, `←` y `→` se invierten.

## Convivencia con Livewire

El estado de apertura de un sub-menú vive en el DOM (`data-kore-open`) y **lo emite el servidor**: la rama que contiene la ruta activa sale ya abierta, sin el parpadeo de abrirse de golpe cuando arranca el JavaScript. A partir de ahí lo cambia el usuario.

Eso obliga a proteger ese atributo del morph, y por eso los items con hijos llevan `wire:ignore.self`. Congela los atributos del propio `<li>` y del botón que lo abre; **no** congela su contenido:

| Qué | ¿Se sigue actualizando desde el servidor? |
|---|---|
| Labels, badges e iconos de los sub-items | Sí |
| Sub-items que aparecen o desaparecen | Sí |
| Estado de apertura (`data-kore-open`) | No: manda el usuario |
| Item activo (`data-kore-active`) | No por morph, pero sí al cambiar de ruta |

Lo último no es una pérdida: el item activo lo decide la ruta, y navegar con `wire:navigate` **reemplaza** el nodo en vez de hacerle morph, así que se recalcula entero.

## `Escape` y las capas

El drawer móvil escucha `Escape` en `window`, igual que el overlay manager, así que los dos reciben el mismo evento. Para que una pulsación no cierre las dos cosas, el drawer solo se queda la tecla si es la capa de arriba —nadie tomó el scroll lock después que él— y en ese caso la marca con `preventDefault()`, que es lo que hace que el manager ceda.

Con un modal abierto sobre el drawer hacen falta dos pulsaciones: la primera cierra el modal y la segunda el drawer. Es el mismo contrato que siguen los paneles flotantes de la librería (ver `docs/overlay/behavior.md`).

## Store de Alpine

El estado vive en `$store.koreSidebar`, así que se puede leer y cambiar **desde cualquier parte de la página**, sin estar anidado dentro del sidebar.

| Método | Descripción |
|---|---|
| `toggle(id)` | Colapsar / expandir (escritorio) |
| `setCollapsed(id, bool)` | Fijar el estado |
| `isCollapsed(id)` | ¿Está en modo iconos? |
| `openMobile(id)` / `closeMobile(id)` | Abrir / cerrar el drawer |
| `isOpen(id)` | ¿El drawer está abierto? |
| `isMobile(id)` | ¿El viewport está por debajo del breakpoint? |
| `handleToggle(id)` | Colapsa en escritorio y abre el drawer en móvil. Es lo que quiere un botón de menú |

```blade
<button x-data x-on:click="$store.koreSidebar.handleToggle('main')">Menú</button>
```

## Eventos

Se despachan sobre `window`:

| Evento | Payload |
|---|---|
| `kore:sidebar-toggle` | `{ id, collapsed }` |
| `kore:sidebar-mobile-open` | `{ id }` |
| `kore:sidebar-mobile-close` | `{ id }` |

## Plugin Alpine

`KoreSidebar` gestiona lo local: sub-menús, el flyout y el tooltip del modo iconos, el teclado y el cierre al navegar. El estado compartido vive en el store `koreSidebar`. El layout no lo toca nadie desde JavaScript: lo deriva el CSS de los atributos `data-*` que estampa el servidor.

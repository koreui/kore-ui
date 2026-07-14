# App Shell

El chasis de la aplicación: sidebar de navegación, barra superior y el layout que los coordina. Con estos componentes montas un panel de administración completo sin escribir layout a mano.

## Los componentes

| Componente | Para qué |
|---|---|
| `<x-kore::shell>` | El layout. Coloca los sidebars y reserva el espacio del contenido |
| `<x-kore::navbar>` | La barra superior. Trae el botón de menú incorporado |
| `<x-kore::sidebar>` | El sidebar. Colapsable, con drawer en móvil |
| `<x-kore::sidebar.item>` | Un enlace de navegación (o un desplegable, si le metes hijos) |
| `<x-kore::sidebar.group>` | Una sección con su título |
| `<x-kore::sidebar.toggle>` | El botón de colapsar / abrir. Funciona desde cualquier parte de la página |

## Ejemplo completo

```blade
<x-kore::shell>
    <x-slot:sidebar>
        <x-kore::sidebar :navigate="true">
            <x-slot:header>
                <span class="kore-sidebar-label font-bold">Mi App</span>
                <span class="kore-sidebar-mini font-bold">M</span>
            </x-slot:header>

            <x-kore::sidebar.item label="Panel" icon="layout-dashboard" route="dashboard" />

            <x-kore::sidebar.group label="Gestión">
                <x-kore::sidebar.item label="Usuarios" icon="users" route="users.index" match="users.*" badge="12" />
                <x-kore::sidebar.item label="Roles" icon="shield" route="roles.index" match="roles.*" />
            </x-kore::sidebar.group>

            <x-slot:footer>
                <x-kore::sidebar.item label="Cerrar sesión" icon="log-out" href="/logout" />
            </x-slot:footer>
        </x-kore::sidebar>
    </x-slot:sidebar>

    <x-slot:navbar>
        <x-kore::navbar>
            <x-slot:end>
                <x-kore::theme-switch size="sm" />
            </x-slot:end>
        </x-kore::navbar>
    </x-slot:navbar>

    {{ $slot }}
</x-kore::shell>
```

## Cómo funciona (y por qué importa)

**El estado lo decide el servidor.** El sidebar guarda si está colapsado en la cookie `kore_sidebar`. Laravel la lee al renderizar y emite el HTML con el ancho correcto **en el primer paint**. No hay parpadeo ni salto de layout, y funciona aunque el JavaScript tarde en cargar o no cargue nunca.

Con `localStorage` esto sería imposible: el estado solo se conocería después de que arrancara Alpine, así que el sidebar aparecería ancho y se encogería a la vista del usuario en cada carga.

**La ruta activa también.** El item que apunta a la página actual se marca solo, en PHP. Y si un sub-item está activo, su padre —y el padre de su padre— salen **ya abiertos** en el HTML. Nada de menús que se despliegan de golpe al arrancar el JS.

**El layout móvil es CSS puro.** Por debajo del breakpoint el sidebar se convierte en un drawer, y eso lo resuelven media queries, no JavaScript. Es correcto en el primer paint y en cada cambio de tamaño de ventana.

## Instalación

Los componentes vienen con la librería; no hay que registrar nada. Solo dos cosas:

**1. `@koreScripts`** en el layout, como el resto de KoreUi:

```blade
<body>
    {{-- tu contenido --}}

    @livewireScripts
    @koreScripts
</body>
```

**2. Que Tailwind vea las vistas del paquete**, en tu CSS:

```css
@import 'tailwindcss';
@import '../../vendor/koreui/kore-ui/resources/css/kore-theme.css';

@source '../../vendor/koreui/kore-ui/resources/**/*.blade.php';
```

Sin el `@source`, Tailwind no generará las clases que usan los componentes y el sidebar se verá sin estilos.

## Configuración

En `config/kore-ui.php`, sección `shell`:

```php
'shell' => [
    'sidebar' => [
        'collapsible' => true,
        'collapsed' => false,        // estado en la primera visita, antes de que haya cookie
        'placement' => 'left',       // 'left' | 'right'
        'width' => '16rem',
        'collapsed_width' => '4rem',
        'breakpoint' => 'lg',        // por debajo de esto, el sidebar es un drawer
        'persist' => true,
        'smart' => true,             // detectar sola la ruta activa
        'navigate' => false,         // añadir wire:navigate a los enlaces
        'overlay' => true,           // fondo oscuro tras el drawer móvil
        'rail' => false,
        'expand_on_hover' => false,
        'duration' => 200,           // ms de la animación
        'badge_max' => 99,           // por encima, el contador muestra "99+"
    ],

    'navbar' => [
        'sticky' => true,
        'bordered' => true,
    ],
],
```

Los anchos son **longitudes CSS**, no clases de Tailwind: alimentan las custom properties que gobiernan el layout.

## Una limitación que conviene conocer

El sidebar es `position: fixed`. Eso deja de funcionar si algún **ancestro** del `<x-kore::shell>` tiene `transform`, `filter`, `perspective`, `contain` o `will-change`: cualquiera de esas propiedades convierte al ancestro en el bloque contenedor y el sidebar se posicionaría respecto a él en vez de respecto a la ventana.

No es un capricho de KoreUi, es cómo funciona CSS. Si el sidebar aparece en un sitio raro, busca un `transform` en los contenedores de arriba.

## Estado global

El sidebar publica su estado en un store de Alpine, así que **cualquier elemento de la página** puede consultarlo o cambiarlo, esté donde esté:

```blade
<button x-data x-on:click="$store.koreSidebar.toggle('main')">
    Colapsar
</button>

<div x-data x-show="$store.koreSidebar.isOpen('main')">
    El drawer está abierto
</div>
```

Ver [sidebar.md](sidebar.md) para la API completa del store.

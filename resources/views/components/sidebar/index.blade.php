@props([
    'id' => 'main',
    'collapsible' => null,
    'collapsed' => null,
    'placement' => null,
    'width' => null,
    'collapsedWidth' => null,
    'breakpoint' => null,
    'persist' => null,
    'smart' => null,
    'navigate' => null,
    'overlay' => null,
    'rail' => null,
    'expandOnHover' => null,
    'header' => null,
    'footer' => null,
    'ariaLabel' => 'Sidebar',
])

@php
    use KoreUi\Shell\Breakpoints;
    use KoreUi\Shell\ShellContext;
    use KoreUi\Shell\SidebarState;

    $config = config('kore-ui.shell.sidebar', []);

    $collapsible   = $collapsible   ?? ($config['collapsible'] ?? true);
    $placement     = $placement === 'right' ? 'right' : 'left';
    $persist       = $persist       ?? ($config['persist'] ?? true);
    $smart         = $smart         ?? ($config['smart'] ?? true);
    $navigate      = $navigate      ?? ($config['navigate'] ?? false);
    $overlay       = $overlay       ?? ($config['overlay'] ?? true);
    $rail          = $rail          ?? ($config['rail'] ?? false);
    $expandOnHover = $expandOnHover ?? ($config['expand_on_hover'] ?? false);
    $breakpoint    = Breakpoints::resolve($breakpoint, $config['breakpoint'] ?? 'lg');

    // El ancho va a un atributo style: se valida como longitud CSS o sería una vía
    // de inyección de CSS arbitrario.
    $width          = SidebarState::cssLength($width, $config['width'] ?? '16rem');
    $collapsedWidth = SidebarState::cssLength($collapsedWidth, $config['collapsed_width'] ?? '4rem');

    // Estado inicial: la cookie manda sobre la prop, porque es lo que el usuario
    // eligió; la prop solo aporta el valor de la primera visita. Al resolverse aquí,
    // en el servidor, el HTML ya sale con el ancho correcto y no hay salto.
    $isCollapsed = $persist
        ? SidebarState::collapsed($id, (bool) ($collapsed ?? $config['collapsed'] ?? false))
        : (bool) ($collapsed ?? $config['collapsed'] ?? false);

    // El modo rail se comporta como colapsado y se expande al hover.
    if ($rail) {
        $isCollapsed = true;
    }

    $hoverExpand = $rail || $expandOnHover;

    // El shell lee esto para reservar el espacio del contenido. Funciona porque Blade
    // renderiza los slots ANTES que la plantilla que los contiene: cuando corra
    // shell/index.blade.php, este registro ya está hecho.
    app(ShellContext::class)->register([
        'id' => $id,
        'placement' => $placement,
        'breakpoint' => $breakpoint,
        'collapsed' => $isCollapsed,
        'rail' => $rail,
    ]);

    $jsConfig = [
        'id' => $id,
        'collapsed' => $isCollapsed,
        'collapsible' => (bool) $collapsible,
        'placement' => $placement,
        'persist' => (bool) $persist,
        'rail' => (bool) $rail,
        'expandOnHover' => (bool) $expandOnHover,
        'overlay' => (bool) $overlay,
        'breakpointPx' => Breakpoints::px($breakpoint),
    ];
@endphp

<nav
    x-data="KoreSidebar({{ Js::from($jsConfig) }})"
    x-on:keydown="onKeydown($event)"
    {{-- Focus trap SOLO cuando es un drawer abierto sobre el contenido. En escritorio el
         sidebar es parte de la página y atrapar el foco sería un error. El scroll ya lo
         bloquea utils/scroll-lock.js, así que no se usa el modificador .noscroll. --}}
    x-trap="$store.koreSidebar.isMobile(@js($id)) && $store.koreSidebar.isOpen(@js($id))"
    data-kore-sidebar-id="{{ $id }}"
    data-kore-sidebar="{{ $isCollapsed ? 'collapsed' : 'expanded' }}"
    data-placement="{{ $placement }}"
    data-breakpoint="{{ $breakpoint }}"
    data-open="false"
    @if($hoverExpand) data-hover-expand="true" @endif
    style="--kore-sidebar-width: {{ $width }}; --kore-sidebar-width-collapsed: {{ $collapsedWidth }};"
    aria-label="{{ $ariaLabel }}"
    {{ $attributes->except(['class'])->class(['kore-sidebar', $attributes->get('class')]) }}
>
    @if($header)
        {{-- kore-sidebar-header centra la marca al colapsar, igual que hace el link con
             su icono. Sin eso, el logotipo compacto quedaría descolgado a la izquierda. --}}
        <div class="kore-sidebar-header h-16 shrink-0 border-b border-kore-border">
            {{ $header }}
        </div>
    @endif

    {{-- Cerrar el drawer. Solo existe por debajo del breakpoint (lo enciende el CSS): en
         escritorio el sidebar es parte de la página y no hay nada que cerrar. --}}
    <button
        type="button"
        class="kore-sidebar-close absolute end-3 top-3 size-8 items-center justify-center rounded-kore-md text-kore-muted-fg transition-colors hover:bg-kore-muted hover:text-kore-fg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring/50"
        x-on:click="$store.koreSidebar.closeMobile(@js($id))"
        aria-label="Cerrar navegación"
    >
        <x-dynamic-component component="lucide-x" class="size-5" />
    </button>

    <div class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden px-3 py-3">
        <ul role="list" class="space-y-1">
            {{ $slot }}
        </ul>
    </div>

    @if($footer)
        <div class="shrink-0 border-t border-kore-border px-3 py-3">
            <ul role="list" class="space-y-1">
                {{ $footer }}
            </ul>
        </div>
    @endif

    {{-- Tooltip del label para cuando el sidebar está en iconos. Hay UNO por sidebar y se
         recoloca según el item; montar veinte tooltips (uno por item) sería tirar memoria.
         Va dentro del <nav> para quedar en el scope de Alpine, pero es `position: fixed`,
         así que el overflow del contenedor no lo recorta. --}}
    <div x-ref="tooltip" role="tooltip" class="kore-sidebar-tooltip"></div>
</nav>

{{-- Backdrop del drawer móvil. Es hermano del <nav> (no hijo) porque el CSS lo
     activa con el combinador `~`, y porque un hijo del sidebar heredaría su
     transform en móvil y quedaría mal posicionado. --}}
@if($overlay)
    <div
        class="kore-sidebar-backdrop"
        x-on:click="$store.koreSidebar.closeMobile(@js($id))"
        {{-- Por `closeMobileOnEscape` y no por `closeMobile` a secas: el drawer
             y el overlay manager escuchan los dos en `window`, así que con un
             modal abierto encima una sola pulsación cerraba las dos cosas. --}}
        x-on:keydown.escape.window="$store.koreSidebar.closeMobileOnEscape(@js($id), $event)"
        aria-hidden="true"
    ></div>
@endif

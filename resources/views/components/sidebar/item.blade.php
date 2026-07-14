@props([
    'label' => null,
    'icon' => null,
    'href' => null,
    'route' => null,
    'routeParams' => [],
    'match' => null,
    'active' => null,
    'opened' => false,
    'badge' => null,
    'badgeVariant' => 'soft',
    'badgeColor' => 'primary',
    'badgeMax' => null,
    'disabled' => false,
    'target' => null,
    'navigate' => null,
])

{{-- Heredados del <x-kore::sidebar> que los envuelve. @aware es la vía de Blade para
     esto: atraviesa también el sidebar.group intermedio. --}}
@aware([
    'smart' => true,
    'navigate' => false,
])

@php
    use KoreUi\Shell\NavigationMatcher;

    // Blade renderiza el contenido del slot ANTES que esta plantilla (compila a
    // startComponent() → ob_start() → renderComponent()), así que aquí $slot ya es
    // HTML y podemos preguntarle si contiene un hijo activo. Cada item activo estampa
    // data-kore-active, de modo que la detección compone recursivamente a cualquier
    // profundidad. Es la única forma de que el sub-menú salga YA ABIERTO del servidor:
    // resolverlo en Alpine lo pintaría cerrado durante un frame.
    $slotHtml = (string) $slot;
    $hasChildren = trim($slotHtml) !== '';
    $hasActiveChild = $hasChildren && str_contains($slotHtml, 'data-kore-active="true"');

    $smartRouting = $attributes->has('smart') ? $attributes->get('smart') : $smart;
    $useNavigate = $navigate ?? false;

    $resolvedHref = NavigationMatcher::href($route, $routeParams, $href);

    $isActive = NavigationMatcher::isActive(
        active: $active === null ? null : (bool) $active,
        match: $match,
        route: $route,
        href: $href,
        smart: (bool) $smartRouting,
        hasActiveChild: $hasActiveChild,
    );

    $isOpen = $hasChildren && ((bool) $opened || $hasActiveChild);

    // La geometría (justify / gap / padding lateral) la lleva `kore-sidebar-link` desde
    // CSS, no clases de Tailwind: al colapsar tiene que centrarse y perder el hueco y el
    // padding, o el icono queda empujado contra el borde izquierdo en vez de centrado.
    $linkClasses = [
        'kore-sidebar-link group relative w-full rounded-kore-md py-2 text-sm transition-colors',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring/50',
        $isActive
            ? 'bg-kore-primary/10 font-medium text-kore-primary'
            : 'text-kore-fg hover:bg-kore-muted',
        $disabled ? 'pointer-events-none opacity-50' : '',
    ];

    // Las clases se escriben ENTERAS y a mano. Construirlas por concatenación
    // ("bg-kore-{$badgeColor}") las vuelve invisibles para el escáner de Tailwind, que
    // lee el código fuente como texto: la clase solo existiría si otro componente la
    // hubiera generado por casualidad, y el color fallaría el día que ese componente
    // cambie.
    $dotClass = match ($badgeColor) {
        'destructive', 'danger' => 'bg-kore-destructive',
        'success' => 'bg-kore-success',
        'warning' => 'bg-kore-warning',
        'info' => 'bg-kore-info',
        default => 'bg-kore-primary',
    };

    $pillClass = match ($badgeColor) {
        'destructive', 'danger' => $badgeVariant === 'solid'
            ? 'bg-kore-destructive text-kore-destructive-fg'
            : 'bg-kore-destructive/15 text-kore-destructive',
        'success' => $badgeVariant === 'solid'
            ? 'bg-kore-success text-kore-success-fg'
            : 'bg-kore-success/15 text-kore-success',
        'warning' => $badgeVariant === 'solid'
            ? 'bg-kore-warning text-kore-warning-fg'
            : 'bg-kore-warning/15 text-kore-warning-text',
        'info' => $badgeVariant === 'solid'
            ? 'bg-kore-info text-kore-info-fg'
            : 'bg-kore-info/15 text-kore-info',
        default => $badgeVariant === 'solid'
            ? 'bg-kore-primary text-kore-primary-fg'
            : 'bg-kore-primary/15 text-kore-primary',
    };

    // El contador de la esquina va siempre en sólido: en 10px, un fondo suave no se lee.
    $countClass = match ($badgeColor) {
        'destructive', 'danger' => 'bg-kore-destructive text-kore-destructive-fg',
        'success' => 'bg-kore-success text-kore-success-fg',
        'warning' => 'bg-kore-warning text-kore-warning-fg',
        'info' => 'bg-kore-info text-kore-info-fg',
        default => 'bg-kore-primary text-kore-primary-fg',
    };

    $isDot = $badgeVariant === 'dot';
    $hasBadge = $badge !== null && $badge !== '';

    // Al colapsar, el badge se muda a la esquina del icono. Ahí solo caben dos o tres
    // caracteres, así que hay que acortarlo:
    //   - un número por encima del tope se convierte en "99+" (con 100000000 el badge
    //     reventaría el icono y además nadie lee nueve dígitos a ese tamaño);
    //   - un texto corto ("!", "new") pasa tal cual;
    //   - cualquier otra cosa no cabe, y el item se queda solo con el punto.
    $badgeMax = (int) ($badgeMax ?? config('kore-ui.shell.sidebar.badge_max', 99));
    $compactBadge = null;

    if ($hasBadge && ! $isDot) {
        if (is_numeric($badge)) {
            $count = (int) $badge;
            $compactBadge = $count > $badgeMax ? $badgeMax.'+' : (string) $count;
        } elseif (mb_strlen((string) $badge) <= 3) {
            $compactBadge = (string) $badge;
        }
    }
@endphp

{{-- El hover se escucha en el <li> y no en el enlace: el panel del flyout es descendiente
     suyo, así que al mover el cursor del icono al panel NO se dispara mouseleave (el hover
     del DOM incluye a los descendientes, con independencia de dónde los pinte el layout).
     Eso hace que el flyout siga abierto mientras se navega por él, sin trucos de delay. --}}
<li
    x-on:mouseenter="onItemEnter($event)"
    x-on:mouseleave="onItemLeave($event)"
    x-on:focusin="onItemEnter($event)"
    x-on:focusout="onItemLeave($event)"
    @if($isActive) data-kore-active="true" @endif
    @if($hasChildren)
        data-kore-has-children
        data-kore-open="{{ $isOpen ? 'true' : 'false' }}"
    @endif
    {{ $attributes->except(['class', 'smart'])->class(['relative', $attributes->get('class')]) }}
>
    @if($hasChildren)
        {{-- Item con sub-items: es un disclosure, no un enlace. --}}
        <button
            type="button"
            x-on:click="toggleItem($event)"
            aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
            @if($disabled) disabled aria-disabled="true" @endif
            @class($linkClasses)
        >
            @include('kore::components.sidebar._item-content', [
                'icon' => $icon,
                'label' => $label,
                'badge' => $badge,
                'hasBadge' => $hasBadge,
                'isDot' => $isDot,
                'dotClass' => $dotClass,
                'pillClass' => $pillClass,
                'countClass' => $countClass,
                'compactBadge' => $compactBadge,
                'isActive' => $isActive,
            ])

            <x-dynamic-component
                component="lucide-chevron-right"
                class="kore-sidebar-chevron kore-sidebar-label size-4 shrink-0 text-kore-muted-fg"
            />
        </button>

        <div class="kore-sidebar-submenu">
            <div>
                <ul role="list" class="mt-1 space-y-1 border-l border-kore-border pl-3 ms-4">
                    {{ $slot }}
                </ul>
            </div>
        </div>
    @else
        <a
            href="{{ $resolvedHref ?? '#' }}"
            @if($target) target="{{ $target }}" rel="noopener noreferrer" @endif
            @if($useNavigate && $resolvedHref && ! $target) wire:navigate @endif
            @if($isActive) aria-current="page" @endif
            @if($disabled) aria-disabled="true" tabindex="-1" @endif
            @class($linkClasses)
        >
            {{-- Barra de acento del item activo. Sobrevive al colapso porque va
                 anclada al borde, no al texto. --}}
            @if($isActive)
                <span
                    class="absolute inset-y-1 start-0 rounded-full bg-kore-primary"
                    style="width: var(--kore-sidebar-accent-width);"
                    aria-hidden="true"
                ></span>
            @endif

            @include('kore::components.sidebar._item-content', [
                'icon' => $icon,
                'label' => $label,
                'badge' => $badge,
                'hasBadge' => $hasBadge,
                'isDot' => $isDot,
                'dotClass' => $dotClass,
                'pillClass' => $pillClass,
                'countClass' => $countClass,
                'compactBadge' => $compactBadge,
                'isActive' => $isActive,
            ])
        </a>
    @endif
</li>

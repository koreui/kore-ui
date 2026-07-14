@props([
    'sidebar' => null,
    'aside' => null,
    'navbar' => null,
])

@php
    use KoreUi\Shell\ShellContext;

    // Los sidebars ya se registraron solos: Blade renderiza el contenido de los slots
    // ANTES que esta plantilla, así que cuando llegamos aquí sabemos exactamente qué
    // hay a cada lado, con qué estado y qué breakpoint. Nada de inspeccionar el HTML.
    $sidebars = app(ShellContext::class)->consume();

    $left = $sidebars['left'] ?? null;
    $right = $sidebars['right'] ?? null;

    // El espacio que reserva el contenido. En rail es un estado propio y no "collapsed",
    // porque el sidebar se ensancha al hover PERO el contenido no debe moverse.
    $reserved = function (?array $sidebar): ?string {
        if ($sidebar === null) {
            return null;
        }

        if ($sidebar['rail'] ?? false) {
            return 'rail';
        }

        return ($sidebar['collapsed'] ?? false) ? 'collapsed' : 'expanded';
    };

    // Las media queries del drawer necesitan un breakpoint concreto en el shell. Si
    // hubiera dos sidebars con breakpoints distintos, manda el izquierdo (el principal).
    $breakpoint = $left['breakpoint'] ?? $right['breakpoint'] ?? 'lg';
@endphp

<div
    data-kore-shell
    data-breakpoint="{{ $breakpoint }}"
    @if($leftState = $reserved($left)) data-sidebar-left="{{ $leftState }}" @endif
    @if($rightState = $reserved($right)) data-sidebar-right="{{ $rightState }}" @endif
    {{ $attributes->except('class')->class(['bg-kore-bg text-kore-fg', $attributes->get('class')]) }}
>
    {{ $sidebar }}
    {{ $aside }}

    <div class="kore-shell-main flex min-h-dvh flex-col">
        {{ $navbar }}

        <main class="flex-1">
            {{ $slot }}
        </main>
    </div>
</div>

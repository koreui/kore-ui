@props([
    'sidebar' => null,
    'aside' => null,
    'navbar' => null,
    'skipLink' => true,
    'skipLabel' => null,
    'mainId' => 'kore-contenido',
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

    $skipLabel = $skipLabel ?? config('kore-ui.ui.translations.skip_to_content', 'Saltar al contenido');
@endphp

<div
    data-kore-shell
    data-breakpoint="{{ $breakpoint }}"
    @if($leftState = $reserved($left)) data-sidebar-left="{{ $leftState }}" @endif
    @if($rightState = $reserved($right)) data-sidebar-right="{{ $rightState }}" @endif
    {{ $attributes->except('class')->class(['bg-kore-bg text-kore-fg', $attributes->get('class')]) }}
>
    {{-- Saltar al contenido.

         Sin esto, quien navega con teclado tenía que pasar por todo el menú
         —seis pulsaciones con un sidebar de tres niveles— antes de llegar al
         contenido, y en CADA página. Es el primer elemento del documento y solo
         se ve al enfocarlo. --}}
    @if($skipLink)
        <a href="#{{ $mainId }}"
           class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded-kore-md focus:bg-kore-primary focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-kore-primary-fg focus:outline-none focus:ring-2 focus:ring-kore-ring focus:ring-offset-2">
            {{ $skipLabel }}
        </a>
    @endif

    {{ $sidebar }}
    {{ $aside }}

    <div class="kore-shell-main flex min-h-dvh flex-col">
        {{ $navbar }}

        {{-- `tabindex="-1"`: sin él, saltar aquí mueve el foco del navegador pero
             algunos lectores siguen leyendo desde donde estaban. --}}
        <main id="{{ $mainId }}" tabindex="-1" class="flex-1 focus:outline-none">
            {{ $slot }}
        </main>
    </div>
</div>

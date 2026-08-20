@props([
    'icon' => null,
    'direction' => null,
    'position' => null,
    'items' => [],
    'color' => 'primary',
    'size' => null,
])

@php
    $icon = $icon ?? config('kore-ui.ui.speed-dial.icon', 'plus');
    $direction = $direction ?? config('kore-ui.ui.speed-dial.direction', 'up');
    $size = $size ?? config('kore-ui.ui.speed-dial.size', 'sm');

    $positionClasses = match($position) {
        'bottom-right' => 'fixed bottom-6 right-6 z-40',
        'bottom-left'  => 'fixed bottom-6 left-6 z-40',
        'top-right'    => 'fixed top-6 right-6 z-40',
        'top-left'     => 'fixed top-6 left-6 z-40',
        default        => 'relative',
    };

    $fabSize = match($size) {
        'sm' => 'size-10',
        'lg' => 'size-16',
        default => 'size-14',
    };

    $itemSize = match($size) {
        'sm' => 'size-8',
        'lg' => 'size-12',
        default => 'size-10',
    };

    $itemIconSize = match($size) {
        'sm' => 'size-3.5',
        'lg' => 'size-5',
        default => 'size-4',
    };

    $fabIconSize = match($size) {
        'sm' => 'size-4',
        'lg' => 'size-7',
        default => 'size-6',
    };

    $containerDirection = match($direction) {
        'up'    => 'flex-col-reverse',
        'down'  => 'flex-col',
        'left'  => 'flex-row-reverse',
        'right' => 'flex-row',
        default => 'flex-col-reverse',
    };

    $colorClasses = match($color) {
        'primary' => 'bg-kore-primary text-kore-primary-fg hover:bg-kore-primary/90',
        'secondary' => 'bg-kore-secondary text-kore-secondary-fg hover:bg-kore-secondary/80',
        'destructive' => 'bg-kore-destructive text-kore-destructive-fg hover:bg-kore-destructive/90',
        default => 'bg-kore-primary text-kore-primary-fg hover:bg-kore-primary/90',
    };

    $tooltipPosition = match($direction) {
        'up', 'down' => 'left',
        'left' => 'top',
        'right' => 'top',
        default => 'left',
    };

    $tooltipClasses = match($tooltipPosition) {
        'left' => 'right-full mr-2 top-1/2 -translate-y-1/2',
        'right' => 'left-full ml-2 top-1/2 -translate-y-1/2',
        'top' => 'bottom-full mb-2 left-1/2 -translate-x-1/2',
        default => 'right-full mr-2 top-1/2 -translate-y-1/2',
    };
@endphp

{{-- El Escape se escucha AQUÍ y no en `window`.

     En window este manejador recibía el mismo evento que el overlay manager y
     ninguno de los dos lo marcaba, así que con el speed-dial abierto dentro de
     un modal una sola pulsación cerraba las dos cosas. Escuchando en el propio
     elemento, el orden de propagación decide solo: el evento pasa por aquí
     antes de llegar a window, y el `preventDefault()` le dice al manager que la
     tecla ya está atendida. Se marca únicamente si había algo que cerrar. --}}
<div {{ $attributes->class(['inline-flex', $positionClasses]) }}
     x-data="KoreSpeedDial({ direction: '{{ $direction }}' })"
     x-on:keydown.escape="if (open) { $event.preventDefault(); close() }">
    <div class="flex items-center {{ $containerDirection }} gap-2">
        {{-- FAB Button --}}
        <button type="button"
                x-on:click="toggle()"
                class="inline-flex items-center justify-center rounded-full shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-kore-ring focus:ring-offset-2 ring-offset-kore-bg {{ $fabSize }} {{ $colorClasses }}"
                :aria-expanded="open"
                aria-haspopup="true"
                aria-label="{{ config('kore-ui.ui.translations.speed_dial', 'Acciones rápidas') }}">
            <x-dynamic-component :component="'lucide-' . $icon"
                class="{{ $fabIconSize }} transition-transform duration-200"
                x-bind:class="open && 'rotate-45'" />
        </button>

        {{-- Items --}}
        <div x-show="open"
             x-on:click.outside="close()"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="flex items-center {{ $containerDirection }} gap-2"
             role="menu"
             aria-label="{{ config('kore-ui.ui.translations.speed_dial', 'Acciones rápidas') }}">
            @foreach($items as $index => $item)
                {{-- `role="none"` en el envoltorio y `menuitem` en el control.
                     Al revés —que es como estaba— un lector encontraba un
                     «elemento de menú» con un botón DENTRO: un menuitem no
                     puede contener un control, así que la relación se rompía y
                     la acción no se anunciaba como activable. --}}
                <div class="relative group" role="none">
                    @if(!empty($item['href']))
                        <a href="{{ $item['href'] }}"
                           role="menuitem"
                           class="inline-flex items-center justify-center rounded-full shadow-md transition-all duration-150 bg-kore-surface text-kore-surface-fg border border-kore-border hover:bg-kore-accent hover:text-kore-accent-fg focus:outline-none focus:ring-2 focus:ring-kore-ring {{ $itemSize }}"
                           @if(!empty($item['label'])) aria-label="{{ $item['label'] }}" @endif>
                            @if(!empty($item['icon']))
                                <x-dynamic-component :component="'lucide-' . $item['icon']" class="{{ $itemIconSize }}" />
                            @endif
                        </a>
                    @else
                        <button type="button"
                                role="menuitem"
                                @if(!empty($item['wireClick'])) wire:click="{{ $item['wireClick'] }}" @endif
                                x-on:click="close()"
                                class="inline-flex items-center justify-center rounded-full shadow-md transition-all duration-150 bg-kore-surface text-kore-surface-fg border border-kore-border hover:bg-kore-accent hover:text-kore-accent-fg focus:outline-none focus:ring-2 focus:ring-kore-ring {{ $itemSize }}"
                                @if(!empty($item['label'])) aria-label="{{ $item['label'] }}" @endif>
                            @if(!empty($item['icon']))
                                <x-dynamic-component :component="'lucide-' . $item['icon']" class="{{ $itemIconSize }}" />
                            @endif
                        </button>
                    @endif

                    {{-- Tooltip --}}
                    @if(!empty($item['label']))
                        <span class="pointer-events-none fixed z-50 whitespace-nowrap rounded-kore-md bg-kore-fg text-kore-bg px-2 py-1 text-xs font-medium opacity-0 group-hover:opacity-100 transition-opacity duration-150"
                              x-init="$nextTick(() => {
                                  const btn = $el.previousElementSibling;
                                  const pos = '{{ $tooltipPosition }}';
                                  const update = () => {
                                      const r = btn.getBoundingClientRect();
                                      if (pos === 'left') {
                                          $el.style.top = (r.top + r.height / 2) + 'px';
                                          $el.style.left = (r.left - 8) + 'px';
                                          $el.style.transform = 'translate(-100%, -50%)';
                                      } else if (pos === 'right') {
                                          $el.style.top = (r.top + r.height / 2) + 'px';
                                          $el.style.left = (r.right + 8) + 'px';
                                          $el.style.transform = 'translateY(-50%)';
                                      } else {
                                          $el.style.top = (r.top - 8) + 'px';
                                          $el.style.left = (r.left + r.width / 2) + 'px';
                                          $el.style.transform = 'translate(-50%, -100%)';
                                      }
                                  };
                                  btn.addEventListener('mouseenter', update);
                              })">
                            {{ $item['label'] }}
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<div
    x-data="KoreOverlay()"
    {{-- Con el $event: `closeOnEscape` necesita saber si alguien ya ha
         consumido este Escape (un select abierto, un calendario, un dropdown)
         para no cerrar el modal además del panel. --}}
    x-on:keydown.escape.window="closeOnEscape($event)"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto overflow-x-hidden"
    aria-live="assertive"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="closeOnClickAway()"
        {{-- `--kore-backdrop` y no `--kore-fg`: el color de texto se invierte con
             el tema, así que en modo oscuro este velo salía casi blanco y
             ACLARABA la página en vez de atenuarla —el fondo acababa más claro
             que el propio modal—. --}}
        class="fixed inset-0 bg-kore-backdrop/50 transition-opacity"
        x-bind:class="backdropBlur ? 'backdrop-blur-sm' : ''"
        aria-hidden="true"
    ></div>

    {{-- Overlay container --}}
    <div class="fixed inset-0 overflow-y-auto overflow-x-hidden pointer-events-none">
        <div
            class="flex h-full"
            x-bind:class="positionClasses"
        >
            @forelse($overlays as $id => $overlay)
                @php
                    $anim = $overlay['overlayAttributes']['animation'];
                    $type = $overlay['overlayAttributes']['type'];
                    $containerClass = $overlay['overlayAttributes']['containerClass'] ?? '';
                    $sizeClass = $overlay['overlayAttributes']['sizeClass'] ?? '';
                    $titulo = $overlay['overlayAttributes']['title'] ?? null;
                @endphp
                <div
                    x-show="current === '{{ $id }}' && contentVisible"
                    x-transition:enter="{{ $anim['enter']['duration'] }}"
                    x-transition:enter-start="{{ $anim['enter']['from'] }}"
                    x-transition:enter-end="{{ $anim['enter']['to'] }}"
                    x-transition:leave="{{ $anim['leave']['duration'] }}"
                    x-transition:leave-start="{{ $anim['leave']['from'] }}"
                    x-transition:leave-end="{{ $anim['leave']['to'] }}"
                    x-trap.inert="show && current === '{{ $id }}'"
                    :style="getSwipeStyle()"
                    class="pointer-events-auto relative w-full transform bg-kore-surface text-kore-surface-fg shadow-xl transition-all {{ $containerClass }} {{ $sizeClass }}"
                    role="dialog"
                    aria-modal="true"
                    {{-- Sin nombre, un lector anuncia «diálogo» y nada más. El
                         nombre lo sabe el componente que se abre, no el manager:
                         llega por `overlayTitle()` o por `overlayAttributes`. --}}
                    @if($titulo) aria-label="{{ $titulo }}" @endif
                    wire:key="{{ $id }}"
                >
                    @if($type === 'bottom-sheet')
                        {{-- Swipe handle for bottom-sheet --}}
                        <div
                            class="flex justify-center pt-3 pb-1 cursor-grab active:cursor-grabbing touch-none"
                            @pointerdown="startSwipe($event)"
                            @pointermove="moveSwipe($event)"
                            @pointerup="endSwipe($event)"
                        >
                            <div class="w-10 h-1 rounded-full bg-kore-muted-fg/30"></div>
                        </div>
                    @endif
                    @livewire($overlay['name'], $overlay['arguments'], key($id))
                </div>
            @empty
            @endforelse
        </div>
    </div>
</div>

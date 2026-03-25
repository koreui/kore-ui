<div
    x-data="KoreOverlay()"
    x-on:keydown.escape.window="closeOnEscape()"
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
        class="fixed inset-0 bg-kore-fg/50 transition-opacity"
        x-bind:class="backdropBlur ? 'backdrop-blur-sm' : ''"
        aria-hidden="true"
    ></div>

    {{-- Overlay container --}}
    <div class="fixed inset-0 overflow-y-auto overflow-x-hidden pointer-events-none">
        <div
            class="flex"
            x-bind:class="positionClasses"
        >
            @forelse($overlays as $id => $overlay)
                @php
                    $anim = $overlay['overlayAttributes']['animation'];
                    $type = $overlay['overlayAttributes']['type'];
                    $containerClass = $overlay['overlayAttributes']['containerClass'] ?? '';
                    $sizeClass = $overlay['overlayAttributes']['sizeClass'] ?? '';
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

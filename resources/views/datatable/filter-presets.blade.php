@php
    $presets = $presets ?? [];
    $activePreset = $activePreset ?? null;
    $presetCounts = $presetCounts ?? [];
@endphp

@if(count($presets) > 0)
    <div class="relative border-b border-kore-border bg-kore-bg">
        <div class="flex items-center gap-1 overflow-x-auto px-4 py-2 scrollbar-none">
        @foreach($presets as $preset)
            @php
                $isActive = $activePreset === $preset->getIdentifier();
                $count = $presetCounts[$preset->getIdentifier()] ?? null;
            @endphp

            <button
                type="button"
                wire:click="applyPreset('{{ $preset->getIdentifier() }}')"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-kore-md border whitespace-nowrap transition-colors
                    {{ $isActive
                        ? 'bg-kore-primary/10 text-kore-primary border-kore-primary'
                        : 'text-kore-muted-fg hover:text-kore-fg hover:bg-kore-muted border-transparent'
                    }}"
            >
                @if($preset->getIcon())
                    <x-dynamic-component :component="'lucide-' . $preset->getIcon()" class="size-3.5" />
                @endif

                <span>{{ $preset->getLabel() }}</span>

                @if($count !== null)
                    <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 text-xs rounded-full {{ $isActive ? 'bg-kore-primary/20 text-kore-primary' : 'bg-kore-muted text-kore-muted-fg' }}">
                        {{ $count }}
                    </span>
                @endif
            </button>
        @endforeach

        @if($activePreset)
            <button
                type="button"
                wire:click="clearPreset"
                aria-label="Limpiar preset"
                class="inline-flex items-center gap-1 ml-1 px-2 py-1.5 text-xs text-kore-muted-fg hover:text-kore-fg transition-colors"
            >
                <x-lucide-x class="size-3" />
            </button>
        @endif
        </div>

        {{-- Edge fades hint at more presets when the rail overflows horizontally --}}
        <div class="pointer-events-none absolute inset-y-0 left-0 w-6 bg-gradient-to-r from-kore-bg to-transparent"></div>
        <div class="pointer-events-none absolute inset-y-0 right-0 w-6 bg-gradient-to-l from-kore-bg to-transparent"></div>
    </div>
@endif

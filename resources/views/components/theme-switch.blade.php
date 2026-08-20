@props([
    'variant' => 'segmented',
    'size' => 'md',
    'labels' => false,
    'lightLabel' => 'Light',
    'darkLabel' => 'Dark',
    'systemLabel' => 'System',
])

@php
    $iconSize = match($size) {
        'sm' => 'size-4',
        'lg' => 'size-6',
        default => 'size-5',
    };

    $buttonPadding = match($size) {
        'sm' => 'px-2 py-1',
        'lg' => 'px-3.5 py-2',
        default => 'px-3 py-1.5',
    };

    $labelSize = match($size) {
        'sm' => 'text-xs',
        'lg' => 'text-base',
        default => 'text-sm',
    };

    $trackSize = match($size) {
        'sm' => 'h-5 w-9',
        'lg' => 'h-7 w-14',
        default => 'h-6 w-11',
    };

    $thumbSize = match($size) {
        'sm' => 'size-3.5',
        'lg' => 'size-5.5',
        default => 'size-4.5',
    };

    $thumbTranslate = match($size) {
        'sm' => 'translate-x-4',
        'lg' => 'translate-x-7',
        default => 'translate-x-5',
    };

    $thumbIconSize = match($size) {
        'sm' => 'size-2.5',
        'lg' => 'size-4',
        default => 'size-3',
    };
@endphp

@if($variant === 'toggle')
    {{-- Toggle variant: simple light/dark switch --}}
    <button
        type="button"
        role="switch"
        {{-- x-data vacío, pero imprescindible: Alpine solo evalúa directivas dentro de un
             árbol que tenga x-data. Sin esto el componente depende de que la app haya
             puesto un x-data en algún ancestro por casualidad, y si no lo hay, los clicks
             no hacen nada — en silencio. --}}
        x-data
        x-bind:aria-checked="$store.koreTheme.isDark.toString()"
        x-on:click="$store.koreTheme.setMode($store.koreTheme.isDark ? 'light' : 'dark')"
        aria-label="Toggle dark mode"
        {{ $attributes->class([
            'kore-theme-switch relative inline-flex shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring focus-visible:ring-offset-2 ring-offset-kore-bg',
            $trackSize,
        ]) }}
        x-bind:class="$store.koreTheme.isDark ? 'bg-kore-primary' : 'bg-kore-muted'"
    >
        {{-- Thumb --}}
        <span
            class="{{ $thumbSize }} pointer-events-none relative inline-block transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
            x-bind:class="$store.koreTheme.isDark ? '{{ $thumbTranslate }}' : 'translate-x-0'"
        >
            {{-- Sun icon --}}
            <x-lucide-sun
                class="{{ $thumbIconSize }} absolute inset-0 m-auto text-amber-500 transition-opacity duration-200"
                x-bind:class="$store.koreTheme.isDark ? 'opacity-0' : 'opacity-100'"
            />
            {{-- Moon icon --}}
            <x-lucide-moon
                class="{{ $thumbIconSize }} absolute inset-0 m-auto text-slate-600 transition-opacity duration-200"
                x-bind:class="$store.koreTheme.isDark ? 'opacity-100' : 'opacity-0'"
            />
        </span>
    </button>

@elseif($variant === 'dropdown')
    {{-- Dropdown variant --}}
    <div
        x-data="{
            open: false,
            position: { top: '0px', left: '0px', width: '10rem' },
            toggle() {
                if (this.open) { this.open = false; return; }
                const rect = this.$refs.trigger.getBoundingClientRect();
                this.position = {
                    top: (rect.bottom + 4) + 'px',
                    left: Math.max(0, rect.right - 160) + 'px',
                    width: '10rem',
                };
                this.open = true;
            },
        }"
        {{-- El Escape se escucha aquí y en el panel, no en `window`: en window
             llegaba al mismo tiempo que al overlay manager y una sola pulsación
             cerraba el menú Y el modal que lo contenía. Al escuchar en el
             elemento, el orden de propagación decide y el `preventDefault()`
             avisa al manager de que la tecla ya está atendida. --}}
        x-on:keydown.escape="if (open) { $event.preventDefault(); open = false }"
        {{ $attributes->class(['kore-theme-switch relative inline-block']) }}
    >
        {{-- Trigger --}}
        <button
            type="button"
            x-ref="trigger"
            x-on:click="toggle()"
            aria-haspopup="true"
            x-bind:aria-expanded="open.toString()"
            aria-label="Theme selector"
            class="{{ $buttonPadding }} inline-flex items-center gap-2 rounded-kore-md border border-kore-border bg-kore-bg text-kore-fg transition-colors duration-200 hover:bg-kore-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring"
        >
            <x-lucide-sun
                class="{{ $iconSize }}"
                x-show="$store.koreTheme.mode === 'light'"
            />
            <x-lucide-moon
                class="{{ $iconSize }}"
                x-show="$store.koreTheme.mode === 'dark'"
            />
            <x-lucide-monitor
                class="{{ $iconSize }}"
                x-show="$store.koreTheme.mode === 'system'"
            />
            @if($labels)
                <span
                    class="{{ $labelSize }}"
                    x-text="$store.koreTheme.mode === 'light' ? '{{ $lightLabel }}' : ($store.koreTheme.mode === 'dark' ? '{{ $darkLabel }}' : '{{ $systemLabel }}')"
                ></span>
            @endif
        </button>

        {{-- Panel (teleported to body to escape overflow:hidden) --}}
        <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-on:click.outside="open = false"
            {{-- También aquí: el panel está teleportado a `<body>`, así que las
                 teclas pulsadas con el foco dentro no burbujean por la raíz. --}}
            x-on:keydown.escape="if (open) { $event.preventDefault(); open = false }"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed z-[9999] w-40 rounded-kore-lg border border-kore-border bg-kore-bg text-kore-fg p-1 shadow-lg"
            x-bind:style="'top:' + position.top + ';left:' + position.left"
            role="menu"
        >
            {{-- Light --}}
            <button
                type="button"
                role="menuitem"
                x-on:click="$store.koreTheme.setMode('light'); open = false"
                class="flex w-full items-center gap-2 rounded-kore-md px-2.5 py-2 {{ $labelSize }} text-kore-fg transition-colors duration-150 hover:bg-kore-muted focus-visible:outline-none focus-visible:bg-kore-muted"
                x-bind:class="$store.koreTheme.mode === 'light' && 'bg-kore-muted font-medium'"
            >
                <x-lucide-sun class="{{ $iconSize }}" />
                {{ $lightLabel }}
            </button>

            {{-- System --}}
            <button
                type="button"
                role="menuitem"
                x-on:click="$store.koreTheme.setMode('system'); open = false"
                class="flex w-full items-center gap-2 rounded-kore-md px-2.5 py-2 {{ $labelSize }} text-kore-fg transition-colors duration-150 hover:bg-kore-muted focus-visible:outline-none focus-visible:bg-kore-muted"
                x-bind:class="$store.koreTheme.mode === 'system' && 'bg-kore-muted font-medium'"
            >
                <x-lucide-monitor class="{{ $iconSize }}" />
                {{ $systemLabel }}
            </button>

            {{-- Dark --}}
            <button
                type="button"
                role="menuitem"
                x-on:click="$store.koreTheme.setMode('dark'); open = false"
                class="flex w-full items-center gap-2 rounded-kore-md px-2.5 py-2 {{ $labelSize }} text-kore-fg transition-colors duration-150 hover:bg-kore-muted focus-visible:outline-none focus-visible:bg-kore-muted"
                x-bind:class="$store.koreTheme.mode === 'dark' && 'bg-kore-muted font-medium'"
            >
                <x-lucide-moon class="{{ $iconSize }}" />
                {{ $darkLabel }}
            </button>
        </div>
        </template>
    </div>

@else
    {{-- Segmented variant (default) --}}
    <div
        role="radiogroup"
        aria-label="Theme selector"
        {{-- Ver la nota del x-data en la variante `toggle`: sin él, Alpine no procesa los
             x-on:click de los botones y el selector no hace nada. --}}
        x-data
        {{ $attributes->class([
            'kore-theme-switch inline-flex items-center rounded-kore-lg bg-kore-muted p-1 gap-1',
        ]) }}
    >
        {{-- Light --}}
        <button
            type="button"
            role="radio"
            {{-- El `aria-label` va SIEMPRE, no solo cuando `labels` está activo:
                 sin él, un botón que es únicamente un icono se anuncia como
                 «botón de radio» y nada más. Coincide con la etiqueta visible
                 cuando la hay, que es lo que pide WCAG 2.5.3. --}}
            aria-label="{{ $lightLabel }}"
            x-bind:aria-checked="($store.koreTheme.mode === 'light').toString()"
            x-on:click="$store.koreTheme.setMode('light')"
            class="{{ $buttonPadding }} inline-flex items-center gap-1.5 rounded-kore-md {{ $labelSize }} font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring"
            x-bind:class="$store.koreTheme.mode === 'light' ? 'bg-kore-bg text-kore-fg shadow-sm' : 'text-kore-muted-fg hover:text-kore-fg'"
        >
            <x-lucide-sun class="{{ $iconSize }}" />
            @if($labels)
                <span>{{ $lightLabel }}</span>
            @endif
        </button>

        {{-- System --}}
        <button
            type="button"
            role="radio"
            aria-label="{{ $systemLabel }}"
            x-bind:aria-checked="($store.koreTheme.mode === 'system').toString()"
            x-on:click="$store.koreTheme.setMode('system')"
            class="{{ $buttonPadding }} inline-flex items-center gap-1.5 rounded-kore-md {{ $labelSize }} font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring"
            x-bind:class="$store.koreTheme.mode === 'system' ? 'bg-kore-bg text-kore-fg shadow-sm' : 'text-kore-muted-fg hover:text-kore-fg'"
        >
            <x-lucide-monitor class="{{ $iconSize }}" />
            @if($labels)
                <span>{{ $systemLabel }}</span>
            @endif
        </button>

        {{-- Dark --}}
        <button
            type="button"
            role="radio"
            aria-label="{{ $darkLabel }}"
            x-bind:aria-checked="($store.koreTheme.mode === 'dark').toString()"
            x-on:click="$store.koreTheme.setMode('dark')"
            class="{{ $buttonPadding }} inline-flex items-center gap-1.5 rounded-kore-md {{ $labelSize }} font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring"
            x-bind:class="$store.koreTheme.mode === 'dark' ? 'bg-kore-bg text-kore-fg shadow-sm' : 'text-kore-muted-fg hover:text-kore-fg'"
        >
            <x-lucide-moon class="{{ $iconSize }}" />
            @if($labels)
                <span>{{ $darkLabel }}</span>
            @endif
        </button>
    </div>
@endif

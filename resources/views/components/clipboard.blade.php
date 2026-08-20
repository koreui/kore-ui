@props([
    'text' => null,
    'variant' => null,
    'label' => null,
    'secret' => false,
    'feedbackDuration' => 2000,
])

@php
    $variant = $variant ?? config('kore-ui.ui.clipboard.variant', 'input');

    $copiar = config('kore-ui.ui.translations.copy', 'Copiar');
    $copiado = config('kore-ui.ui.translations.copied', 'Copiado');

    // El campo de la variante `input` es un control de formulario: sin nombre,
    // un lector anuncia «cuadro de edición» y el valor, sin decir de qué es. La
    // etiqueta visible, cuando la hay, es un `<span>` decorativo dentro de la
    // caja, no un `<label for>`.
    $campoId = \KoreUi\Core\Support\IdContext::secuencia('kore-clipboard');
@endphp

<div x-data="KoreClipboard({ text: @js($text), feedbackDuration: @js($feedbackDuration) })"
     {{ $attributes->class(['inline-flex']) }}>
    @if($variant === 'input')
        <div class="flex rounded-kore-md border border-kore-border overflow-hidden w-full">
            @if($label)
                <label for="{{ $campoId }}"
                       class="px-3 py-2 bg-kore-muted text-kore-muted-fg text-sm border-r border-kore-border flex items-center shrink-0">
                    {{ $label }}
                </label>
            @endif
            <input type="{{ $secret ? 'password' : 'text' }}"
                   id="{{ $campoId }}"
                   value="{{ $text }}"
                   readonly
                   @unless($label) aria-label="{{ $copiar }}" @endunless
                   class="flex-1 px-3 py-2 text-sm bg-kore-surface text-kore-fg outline-none min-w-0" />
            <button type="button" x-on:click="copy()"
                    x-bind:aria-label="copied ? @js($copiado) : @js($copiar)"
                    class="px-3 border-l border-kore-border bg-kore-surface hover:bg-kore-muted transition-colors flex items-center">
                <template x-if="!copied">
                    <x-lucide-copy class="size-4 text-kore-muted-fg" />
                </template>
                <template x-if="copied">
                    <x-lucide-check class="size-4 text-kore-success" />
                </template>
            </button>
        </div>
    @elseif($variant === 'inline')
        <div class="inline-flex items-center gap-2">
            <span class="text-sm text-kore-fg">
                {{ $secret ? '••••••••' : $text }}
            </span>
            <button type="button" x-on:click="copy()"
                    x-bind:aria-label="copied ? @js($copiado) : @js($copiar)"
                    class="p-1 rounded-kore-sm hover:bg-kore-muted transition-colors">
                <template x-if="!copied">
                    <x-lucide-copy class="size-4 text-kore-muted-fg" />
                </template>
                <template x-if="copied">
                    <x-lucide-check class="size-4 text-kore-success" />
                </template>
            </button>
        </div>
    @elseif($variant === 'icon')
        {{-- Llevaba `title` y nada más. Un `title` no se expone de forma fiable
             en táctil ni en todos los lectores, así que el botón se quedaba sin
             nombre: es el mismo caso que ya se corrigió en el resto de la
             librería. --}}
        <button type="button" x-on:click="copy()"
                x-bind:aria-label="copied ? @js($copiado) : @js($copiar)"
                class="p-2 rounded-kore-md hover:bg-kore-muted transition-colors">
            <template x-if="!copied">
                <x-lucide-copy class="size-4 text-kore-muted-fg" />
            </template>
            <template x-if="copied">
                <x-lucide-check class="size-4 text-kore-success" />
            </template>
        </button>
    @endif

    {{-- El cambio de icono es la única señal de que se ha copiado, y es
         puramente visual. Un lector necesita que alguien se lo diga. --}}
    <span class="sr-only" role="status" aria-live="polite" x-text="copied ? @js($copiado) : ''"></span>
</div>

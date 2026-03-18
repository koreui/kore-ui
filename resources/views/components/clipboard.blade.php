@props([
    'text' => null,
    'variant' => null,
    'label' => null,
    'secret' => false,
    'feedbackDuration' => 2000,
])

@php
    $variant = $variant ?? config('kore-ui.ui.clipboard.variant', 'input');
@endphp

<div x-data="KoreClipboard({ text: @js($text), feedbackDuration: @js($feedbackDuration) })"
     {{ $attributes->class(['inline-flex']) }}>
    @if($variant === 'input')
        <div class="flex rounded-kore-md border border-kore-border overflow-hidden w-full">
            @if($label)
                <span class="px-3 py-2 bg-kore-muted text-kore-muted-fg text-sm border-r border-kore-border flex items-center shrink-0">
                    {{ $label }}
                </span>
            @endif
            <input type="{{ $secret ? 'password' : 'text' }}"
                   value="{{ $text }}"
                   readonly
                   class="flex-1 px-3 py-2 text-sm bg-kore-surface text-kore-fg outline-none min-w-0" />
            <button type="button" x-on:click="copy()"
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
        <button type="button" x-on:click="copy()"
                class="p-2 rounded-kore-md hover:bg-kore-muted transition-colors"
                title="Copy to clipboard">
            <template x-if="!copied">
                <x-lucide-copy class="size-4 text-kore-muted-fg" />
            </template>
            <template x-if="copied">
                <x-lucide-check class="size-4 text-kore-success" />
            </template>
        </button>
    @endif
</div>

@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'separator' => ',',
    'max' => null,
    'allowDuplicate' => false,
    'addOnBlur' => true,
    'placeholder' => null,
    'clearable' => false,
    'disabled' => false,
    'required' => false,
    'showError' => true,
])

@php
    $size = $size ?? config('kore-ui.form.size', 'md');

    $name = $name ?? $attributes->whereStartsWith('wire:model')->first();

    $hasError = false;
    $errorMessage = null;

    if ($showError) {
        if ($error) {
            $hasError = true;
            $errorMessage = $error;
        } elseif ($name && isset($errors) && $errors->has($name)) {
            $hasError = true;
            $errorMessage = $errors->first($name);
        }
    }

    $fieldId = $attributes->get('id', $name ? 'kore-' . str_replace('.', '-', $name) : 'kore-' . uniqid());

    $sizeClasses = match($size) {
        'sm' => 'text-xs py-1 px-2',
        'lg' => 'text-base py-2.5 px-3',
        default => 'text-sm py-1.5 px-2.5',
    };

    $chipSize = match($size) {
        'sm' => 'text-xs px-1.5 py-0.5',
        'lg' => 'text-sm px-2.5 py-1',
        default => 'text-xs px-2 py-0.5',
    };

    $iconSize = match($size) {
        'sm' => 'size-3',
        'lg' => 'size-4',
        default => 'size-3.5',
    };

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $borderClasses = $hasError
        ? 'border-kore-destructive focus-within:ring-kore-destructive/30 focus-within:border-kore-destructive'
        : 'border-kore-input focus-within:ring-kore-ring focus-within:border-kore-primary';

    $jsConfig = json_encode(array_filter([
        'separator' => $separator !== ',' ? $separator : null,
        'max' => $max,
        'allowDuplicate' => $allowDuplicate ?: null,
        'addOnBlur' => !$addOnBlur ? false : null,
    ], fn($v) => $v !== null));
@endphp

<x-kore::field
    :label="$label"
    :hint="$hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :field-id="$fieldId"
    :required="$required"
>
    <div
        x-data="KoreTagInput({{ $jsConfig }})"
        wire:ignore
        class="flex flex-wrap items-center gap-1.5 rounded-kore-md border {{ $borderClasses }} focus-within:ring-2 bg-kore-bg {{ $sizeClasses }} {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
        x-on:click="$refs.textInput.focus()"
    >
        {{-- Hidden input for wire:model --}}
        <input
            type="hidden"
            x-ref="hiddenInput"
            {{ $wireModelAttr }}
            @if($name) name="{{ $name }}" @endif
            id="{{ $fieldId }}"
        />

        {{-- Tag chips --}}
        <template x-for="(tag, index) in tags" :key="index">
            <span class="inline-flex items-center gap-1 rounded-kore-sm bg-kore-primary/10 text-kore-primary {{ $chipSize }}">
                <span x-text="tag"></span>
                @if(!$disabled)
                    <button
                        type="button"
                        x-on:click.stop="removeTag(index)"
                        class="text-kore-primary/60 hover:text-kore-primary transition-colors"
                    >
                        <x-lucide-x class="{{ $iconSize }}" />
                    </button>
                @endif
            </span>
        </template>

        {{-- Text input --}}
        <input
            type="text"
            x-ref="textInput"
            class="flex-1 min-w-[80px] bg-transparent border-0 outline-none focus:ring-0 p-0 text-kore-fg placeholder:text-kore-muted-fg {{ match($size) { 'sm' => 'text-xs', 'lg' => 'text-base', default => 'text-sm' } }}"
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($disabled) disabled @endif
            x-on:keydown="onKeydown($event)"
            x-on:paste="onPaste($event)"
            @if($addOnBlur)
                x-on:blur="addCurrentTag()"
            @endif
        />

        {{-- Clear all button --}}
        @if($clearable && !$disabled)
            <button
                type="button"
                x-show="tags.length > 0"
                x-cloak
                x-on:click.stop="clearAll()"
                class="ml-auto text-kore-muted-fg hover:text-kore-fg transition-colors shrink-0"
            >
                <x-lucide-x class="{{ $iconSize }}" />
            </button>
        @endif
    </div>
</x-kore::field>

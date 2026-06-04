@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'type' => 'text',
    'size' => null,
    'icon' => null,
    'iconRight' => null,
    'prefix' => null,
    'suffix' => null,
    'clearable' => false,
    'disabled' => false,
    'readonly' => false,
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

    // Associate the field's hint/error (rendered with these ids in field.blade.php)
    // with the control for assistive tech (WCAG 3.3.1 / 4.1.2).
    $describedBy = $hasError ? $fieldId . '-error' : ($hint ? $fieldId . '-hint' : null);

    $sizeClasses = match($size) {
        'sm' => 'text-xs py-1.5 px-2.5',
        'lg' => 'text-base py-2.5 px-3.5',
        default => 'text-sm py-2 px-3',
    };

    $iconSizeClasses = match($size) {
        'sm' => 'size-3.5',
        'lg' => 'size-5',
        default => 'size-4',
    };

    $hasAddon = $prefix || $suffix;

    $borderClasses = $hasError
        ? 'border-kore-destructive focus-within:ring-kore-destructive/30 focus-within:border-kore-destructive'
        : 'border-kore-input';

    $inputBaseClasses = 'bg-kore-bg text-kore-fg placeholder:text-kore-muted-fg transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed';

    // Icon-only padding (absolute positioned icons)
    $iconPaddingLeft = $icon ? match($size) {
        'sm' => 'pl-8',
        'lg' => 'pl-11',
        default => 'pl-10',
    } : '';

    $hasRightIcon = $iconRight || $clearable;
    $iconPaddingRight = $hasRightIcon ? match($size) {
        'sm' => 'pr-8',
        'lg' => 'pr-11',
        default => 'pr-10',
    } : '';

    $addonTextSize = match($size) {
        'sm' => 'text-xs',
        'lg' => 'text-base',
        default => 'text-sm',
    };

    $addonPadding = match($size) {
        'sm' => 'px-2',
        'lg' => 'px-3.5',
        default => 'px-3',
    };
@endphp

<x-kore::field
    :label="$label"
    :hint="$hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :field-id="$fieldId"
    :required="$required"
>
    @if($hasAddon)
        {{-- Flex layout for prefix/suffix --}}
        <div
            class="flex items-stretch rounded-kore-md border {{ $borderClasses }} focus-within:ring-2 focus-within:ring-kore-ring focus-within:border-kore-primary overflow-hidden {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
            @if($clearable)
                x-data="{ v: '{{ $attributes->get('value', '') }}' }"
            @endif
        >
            @if($prefix)
                <span class="flex items-center shrink-0 {{ $addonPadding }} bg-kore-muted text-kore-muted-fg {{ $addonTextSize }} border-r border-kore-input select-none whitespace-nowrap">{{ $prefix }}</span>
            @endif

            <div class="relative flex-1 min-w-0">
                @if($icon)
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconSizeClasses }} text-kore-muted-fg" />
                    </div>
                @endif

                <input
                    {{ $attributes->merge([
                        'type' => $type,
                        'id' => $fieldId,
                        'name' => $name,
                        'disabled' => $disabled,
                        'readonly' => $readonly,
                        'required' => $required,
                        'aria-invalid' => $hasError ? 'true' : null,
                        'aria-describedby' => $describedBy,
                        'class' => $inputBaseClasses . ' block w-full border-0 focus:outline-none focus:ring-0 ' . $sizeClasses . ' ' . $iconPaddingLeft . ' ' . $iconPaddingRight,
                    ])->except(['label', 'hint', 'error', 'size', 'icon', 'icon-right', 'prefix', 'suffix', 'clearable', 'show-error']) }}
                    @if($clearable)
                        x-ref="input"
                        x-on:input="v = $event.target.value"
                    @endif
                />

                @if($hasRightIcon)
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 gap-1">
                        @if($clearable)
                            <button
                                type="button"
                                x-show="v.length > 0"
                                x-cloak
                                x-on:click="v = ''; $refs.input.value = ''; $refs.input.dispatchEvent(new Event('input', { bubbles: true })); $refs.input.focus()"
                                class="text-kore-muted-fg hover:text-kore-fg transition-colors"
                            >
                                <x-lucide-x class="{{ $iconSizeClasses }}" />
                            </button>
                        @endif
                        @if($iconRight)
                            <x-dynamic-component :component="'lucide-' . $iconRight" class="{{ $iconSizeClasses }} text-kore-muted-fg" />
                        @endif
                    </div>
                @endif
            </div>

            @if($suffix)
                <span class="flex items-center shrink-0 {{ $addonPadding }} bg-kore-muted text-kore-muted-fg {{ $addonTextSize }} border-l border-kore-input select-none whitespace-nowrap">{{ $suffix }}</span>
            @endif
        </div>
    @else
        {{-- Standard layout with absolute icons --}}
        <div
            class="relative"
            @if($clearable)
                x-data="{ v: '{{ $attributes->get('value', '') }}' }"
            @endif
        >
            @if($icon)
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconSizeClasses }} text-kore-muted-fg" />
                </div>
            @endif

            <input
                {{ $attributes->merge([
                    'type' => $type,
                    'id' => $fieldId,
                    'name' => $name,
                    'disabled' => $disabled,
                    'readonly' => $readonly,
                    'required' => $required,
                    'aria-invalid' => $hasError ? 'true' : null,
                    'aria-describedby' => $describedBy,
                    'class' => $inputBaseClasses . ' block w-full rounded-kore-md border ' . ($hasError ? 'border-kore-destructive focus:ring-kore-destructive/30 focus:border-kore-destructive' : 'border-kore-input') . ' focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary ' . $sizeClasses . ' ' . $iconPaddingLeft . ' ' . $iconPaddingRight,
                ])->except(['label', 'hint', 'error', 'size', 'icon', 'icon-right', 'prefix', 'suffix', 'clearable', 'show-error']) }}
                @if($clearable)
                    x-ref="input"
                    x-on:input="v = $event.target.value"
                @endif
            />

            @if($hasRightIcon || $iconRight || $suffix)
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 gap-1">
                    @if($clearable)
                        <button
                            type="button"
                            x-show="v.length > 0"
                            x-cloak
                            x-on:click="v = ''; $refs.input.value = ''; $refs.input.dispatchEvent(new Event('input', { bubbles: true })); $refs.input.focus()"
                            class="text-kore-muted-fg hover:text-kore-fg transition-colors"
                        >
                            <x-lucide-x class="{{ $iconSizeClasses }}" />
                        </button>
                    @endif
                    @if($iconRight)
                        <x-dynamic-component :component="'lucide-' . $iconRight" class="{{ $iconSizeClasses }} text-kore-muted-fg" />
                    @endif
                </div>
            @endif
        </div>
    @endif
</x-kore::field>

@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'placeholder' => null,
    'clearable' => false,
    'disabled' => false,
    'required' => false,
    'showError' => true,

    // Mode
    'mode' => 'single',
    'multipleMax' => null,

    // Constraints
    'minDate' => null,
    'maxDate' => null,
    'disabledDates' => null,
    'disabledWeekdays' => null,
    'weekdaysOnly' => false,
    'weekendsOnly' => false,

    // Display
    'locale' => null,
    'startOfWeek' => null,
    'inline' => false,
    'months' => 1,
    'responsive' => true,
    'showWeekNumbers' => false,

    // Time
    'withTime' => false,
    'timeFormat' => '24',

    // Features
    'presets' => false,
    'helpers' => false,
    'manualInput' => false,
    'requiresConfirmation' => false,
])

@php
    $size = $size ?? config('kore-ui.form.size', 'md');
    $locale = $locale ?? config('kore-ui.form.datepicker.locale');
    $startOfWeek = $startOfWeek ?? config('kore-ui.form.datepicker.start_of_week', 1);

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
        'sm' => 'text-xs py-1.5 px-2.5',
        'lg' => 'text-base py-2.5 px-3.5',
        default => 'text-sm py-2 px-3',
    };

    $iconSizeClasses = match($size) {
        'sm' => 'size-3.5',
        'lg' => 'size-5',
        default => 'size-4',
    };

    $baseClasses = collect([
        'w-full rounded-kore-md border bg-kore-bg text-kore-fg',
        'transition-colors duration-150',
        'focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        $hasError ? 'border-kore-destructive focus:ring-kore-destructive/30 focus:border-kore-destructive' : 'border-kore-input',
        $sizeClasses,
    ])->filter()->implode(' ');

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $jsConfig = json_encode(array_filter([
        'mode' => $mode,
        'multipleMax' => $multipleMax,
        'minDate' => $minDate,
        'maxDate' => $maxDate,
        'disabledDates' => $disabledDates,
        'disabledWeekdays' => $disabledWeekdays,
        'weekdaysOnly' => $weekdaysOnly ?: null,
        'weekendsOnly' => $weekendsOnly ?: null,
        'locale' => $locale,
        'startOfWeek' => $startOfWeek,
        'inline' => $inline ?: null,
        'months' => $months > 1 ? $months : null,
        'responsive' => $responsive ?: null,
        'showWeekNumbers' => $showWeekNumbers ?: null,
        'withTime' => $withTime ?: null,
        'timeFormat' => $withTime ? $timeFormat : null,
        'presets' => $presets ?: null,
        'helpers' => $helpers ?: null,
        'manualInput' => $manualInput ?: null,
        'requiresConfirmation' => $requiresConfirmation ?: null,
    ], fn ($v) => $v !== null));
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
        x-data="KoreDatePicker({{ $jsConfig }})"
        x-on:keydown="onKeydown($event)"
        @if($mode === 'range' || $mode === 'multiple' || $withTime) wire:ignore @endif
        class="relative"
    >
        {{-- Hidden input for wire:model --}}
        <input
            type="hidden"
            x-ref="hiddenInput"
            {{ $wireModelAttr }}
            @if($name) name="{{ $name }}" @endif
        />

        @if(!$inline)
            {{-- Trigger --}}
            @if($manualInput)
                <input
                    type="text"
                    x-ref="trigger"
                    x-on:focus="openDropdown()"
                    x-on:input.debounce.500ms="onManualInput($event)"
                    x-bind:value="displayValue"
                    id="{{ $fieldId }}"
                    placeholder="{{ $placeholder ?? '' }}"
                    class="{{ $baseClasses }} cursor-text pr-10"
                    @if($disabled) disabled @endif
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 gap-1">
                    @if($clearable)
                        <button
                            type="button"
                            x-show="hasValue"
                            x-cloak
                            x-on:click.stop="clear()"
                            class="text-kore-muted-fg hover:text-kore-fg transition-colors"
                        >
                            <x-lucide-x class="{{ $iconSizeClasses }}" />
                        </button>
                    @endif
                    <x-lucide-calendar class="{{ $iconSizeClasses }} text-kore-muted-fg" />
                </div>
            @else
                <button
                    type="button"
                    x-ref="trigger"
                    x-on:click="toggle()"
                    id="{{ $fieldId }}"
                    class="{{ $baseClasses }} flex items-center justify-between gap-2 text-left cursor-pointer {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                    @if($disabled) disabled @endif
                    role="combobox"
                    aria-haspopup="dialog"
                    x-bind:aria-expanded="open"
                >
                    <div class="flex-1 truncate min-w-0">
                        <span x-show="hasValue" x-text="displayValue" class="block truncate"></span>
                        <span x-show="!hasValue" class="text-kore-muted-fg">{!! $placeholder ? e($placeholder) : '&nbsp;' !!}</span>
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                        @if($clearable)
                            <template x-if="hasValue">
                                <button
                                    type="button"
                                    x-on:click.stop="clear()"
                                    class="text-kore-muted-fg hover:text-kore-fg transition-colors"
                                >
                                    <x-lucide-x class="{{ $iconSizeClasses }}" />
                                </button>
                            </template>
                        @endif
                        <x-lucide-calendar class="{{ $iconSizeClasses }} text-kore-muted-fg" />
                    </div>
                </button>
            @endif
        @endif

        {{-- Calendar panel --}}
        @if($inline)
            <div class="rounded-kore-lg border border-kore-border bg-kore-bg text-kore-fg p-3">
                @include('kore::components._datepicker-panel')
            </div>
        @else
            <template x-teleport="body">
                <div
                    x-ref="dropdown"
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    x-on:mousedown.stop
                    class="fixed z-[9999] rounded-kore-lg border border-kore-border bg-kore-bg text-kore-fg shadow-lg p-3"
                    role="dialog"
                    aria-label="Choose date"
                >
                    @include('kore::components._datepicker-panel')
                </div>
            </template>
        @endif
    </div>
</x-kore::field>

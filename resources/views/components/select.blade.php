@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'options' => [],
    'optionLabel' => 'label',
    'optionValue' => 'value',
    'optionDescription' => null,
    'optionImage' => null,
    'placeholder' => null,
    'searchable' => false,
    'clearable' => false,
    'multiple' => false,
    'max' => null,
    'native' => false,
    'grouped' => false,
    'async' => null,
    'debounce' => null,
    'minSearch' => null,
    'creatable' => false,
    'disabled' => false,
    'required' => false,
    'showError' => true,
])

@php
    $size = $size ?? config('kore-ui.form.size', 'md');
    $debounce = $debounce ?? config('kore-ui.form.select.debounce', 300);
    $minSearch = $minSearch ?? config('kore-ui.form.select.min_search', 2);

    // Creatable implies searchable
    if ($creatable && !$native) {
        $searchable = true;
    }

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

    // Prepare JS options for custom select
    $jsOptions = collect($options)->map(function ($item, $key) use ($optionLabel, $optionValue) {
        if (is_array($item) || is_object($item)) {
            return (array) $item;
        }
        // Simple key => value array
        return [$optionValue => $key, $optionLabel => $item];
    })->values()->toJson();

    $wireModelAttr = $attributes->whereStartsWith('wire:model');
@endphp

<x-kore::field
    :label="$label"
    :hint="$hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :field-id="$fieldId"
    :required="$required"
>
    @if($native)
        {{-- Native HTML select --}}
        <select
            {{ $attributes->merge([
                'id' => $fieldId,
                'name' => $name,
                'disabled' => $disabled,
                'required' => $required,
                'class' => $baseClasses . ' pr-8',
            ])->except(['label', 'hint', 'error', 'size', 'options', 'option-label', 'option-value', 'option-description', 'option-image', 'placeholder', 'searchable', 'clearable', 'multiple', 'max', 'native', 'grouped', 'async', 'debounce', 'min-search', 'show-error']) }}
            @if($multiple) multiple @endif
        >
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif

            @if($grouped)
                @foreach($options as $group => $groupOptions)
                    <optgroup label="{{ $group }}">
                        @foreach($groupOptions as $key => $optLabel)
                            <option value="{{ $key }}">{{ $optLabel }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            @else
                @foreach($options as $key => $opt)
                    @if(is_array($opt) || is_object($opt))
                        @php $opt = (array) $opt; @endphp
                        <option value="{{ $opt[$optionValue] ?? $key }}">{{ $opt[$optionLabel] ?? '' }}</option>
                    @else
                        <option value="{{ $key }}">{{ $opt }}</option>
                    @endif
                @endforeach
            @endif
        </select>
    @else
        {{-- Custom Alpine select --}}
        <div
            x-data="KoreSelect({
                options: {{ $jsOptions }},
                multiple: {{ $multiple ? 'true' : 'false' }},
                max: {{ $max ?? 'null' }},
                optionLabel: '{{ $optionLabel }}',
                optionValue: '{{ $optionValue }}',
                optionDescription: {{ $optionDescription ? "'{$optionDescription}'" : 'null' }},
                optionImage: {{ $optionImage ? "'{$optionImage}'" : 'null' }},
                value: {{ $multiple ? '[]' : 'null' }},
                async: {{ $async ? "'{$async}'" : 'null' }},
                debounce: {{ $debounce }},
                minSearch: {{ $minSearch }},
                creatable: {{ $creatable ? 'true' : 'false' }},
            })"
            x-on:keydown="onKeydown($event)"
            @if($multiple) wire:ignore @endif
            class="relative"
        >
            {{-- Hidden input for wire:model --}}
            <input
                type="hidden"
                x-ref="hiddenInput"
                {{ $wireModelAttr }}
                @if($name) name="{{ $name }}" @endif
            />

            {{-- Trigger button --}}
            <button
                type="button"
                x-ref="trigger"
                x-on:click="toggle()"
                id="{{ $fieldId }}"
                class="{{ $baseClasses }} flex items-center justify-between gap-2 text-left cursor-pointer {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                @if($disabled) disabled @endif
                aria-haspopup="listbox"
                x-bind:aria-expanded="open"
            >
                <div class="flex-1 truncate min-w-0">
                    @if($multiple)
                        {{-- Multi-select chips --}}
                        <template x-if="selectedOptions.length > 0">
                            <div class="flex flex-wrap gap-1">
                                <template x-for="opt in selectedOptions" :key="opt.value">
                                    <span class="inline-flex items-center gap-1 rounded-kore-sm bg-kore-muted px-1.5 py-0.5 text-xs text-kore-fg">
                                        <span x-text="opt.label"></span>
                                        <button
                                            type="button"
                                            x-on:click.stop="deselect(opt.value)"
                                            class="text-kore-muted-fg hover:text-kore-fg"
                                        >
                                            <x-lucide-x class="size-3" />
                                        </button>
                                    </span>
                                </template>
                            </div>
                        </template>
                        <template x-if="selectedOptions.length === 0">
                            <span class="text-kore-muted-fg">{!! $placeholder ? e($placeholder) : '&nbsp;' !!}</span>
                        </template>
                    @else
                        <span x-show="hasValue" x-text="displayValue" class="block truncate"></span>
                        <span x-show="!hasValue" class="text-kore-muted-fg">{!! $placeholder ? e($placeholder) : '&nbsp;' !!}</span>
                    @endif
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
                    <x-lucide-chevron-down
                        class="{{ $iconSizeClasses }} text-kore-muted-fg transition-transform duration-200"
                        x-bind:class="{ 'rotate-180': open }"
                    />
                </div>
            </button>

            {{-- Dropdown panel (teleported to body to escape overflow:hidden) --}}
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
                class="fixed z-[9999] rounded-kore-md border border-kore-border bg-kore-surface text-kore-fg shadow-lg overflow-hidden"
                role="listbox"
            >
                @if($searchable)
                    <div class="p-2 border-b border-kore-border">
                        <input
                            type="text"
                            x-ref="search"
                            x-model="search"
                            @if($async) x-on:input="onSearch()" @endif
                            placeholder="Search..."
                            class="w-full rounded-kore-sm border border-kore-input bg-kore-bg px-2.5 py-1.5 text-sm text-kore-fg placeholder:text-kore-muted-fg focus:outline-none focus:ring-1 focus:ring-kore-ring"
                        />
                    </div>
                @endif

                {{-- Loading state --}}
                <template x-if="loading">
                    <div class="px-3 py-4 text-center text-sm text-kore-muted-fg">
                        <x-lucide-loader-2 class="size-4 animate-spin inline-block mr-1" />
                        Loading...
                    </div>
                </template>

                {{-- Options list --}}
                <ul
                    x-show="!loading"
                    class="max-h-60 overflow-auto"
                    role="listbox"
                >
                    <template x-if="filteredOptions.length === 0 && !loading && !showCreateOption">
                        <li class="px-3 py-2 text-sm text-kore-muted-fg text-center">No options found</li>
                    </template>

                    <template x-for="(opt, index) in filteredOptions" :key="getValue(opt)">
                        <li
                            x-on:click="select(opt)"
                            x-bind:data-index="index"
                            x-bind:class="{
                                'bg-kore-primary text-kore-primary-fg hover:bg-kore-primary/90': isSelected(opt),
                                'bg-kore-muted': highlighted === index && !isSelected(opt),
                                'hover:bg-kore-muted': !isSelected(opt),
                            }"
                            class="flex items-center gap-2 px-3 py-2 cursor-pointer text-sm transition-colors"
                            role="option"
                            x-bind:aria-selected="isSelected(opt)"
                            x-on:mouseenter="highlighted = index"
                        >
                            {{-- Option image --}}
                            <template x-if="getImage(opt)">
                                <img x-bind:src="getImage(opt)" class="size-6 rounded-full object-cover shrink-0" />
                            </template>

                            <div class="flex-1 min-w-0">
                                <span x-text="getLabel(opt)" class="block truncate"></span>
                                <template x-if="getDescription(opt)">
                                    <span x-text="getDescription(opt)" class="block text-xs opacity-70 truncate"></span>
                                </template>
                            </div>

                            {{-- Checkmark for selected --}}
                            <template x-if="isSelected(opt)">
                                <x-lucide-check class="size-4 shrink-0" />
                            </template>
                        </li>
                    </template>

                    {{-- Create option (creatable mode) --}}
                    @if($creatable && !$native)
                        <template x-if="showCreateOption">
                            <li
                                x-on:click="createFromSearch()"
                                x-bind:class="{ 'bg-kore-muted': highlighted === filteredOptions.length }"
                                class="flex items-center gap-2 px-3 py-2 cursor-pointer text-sm transition-colors hover:bg-kore-muted text-kore-primary"
                                role="option"
                                x-on:mouseenter="highlighted = filteredOptions.length"
                            >
                                <x-lucide-plus class="{{ $iconSizeClasses }} shrink-0" />
                                <span>Create "<span x-text="search" class="font-medium"></span>"</span>
                            </li>
                        </template>
                    @endif
                </ul>
            </div>
            </template>
        </div>
    @endif
</x-kore::field>

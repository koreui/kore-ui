@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'rows' => null,
    'autoResize' => false,
    'maxLength' => null,
    'disabled' => false,
    'readonly' => false,
    'required' => false,
    'showError' => true,
])

@php
    $size = $size ?? config('kore-ui.form.size', 'md');
    $rows = $rows ?? config('kore-ui.form.textarea.rows', 4);

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

    // Associate the field's hint/error with the control for assistive tech
    // (WCAG 3.3.1 / 4.1.2). The hint id is stable whether the hint is rendered by
    // field.blade.php or by the maxLength counter block below.
    $describedBy = $hasError ? $fieldId . '-error' : ($hint ? $fieldId . '-hint' : null);

    $sizeClasses = match($size) {
        'sm' => 'text-xs py-1.5 px-2.5',
        'lg' => 'text-base py-2.5 px-3.5',
        default => 'text-sm py-2 px-3',
    };

    $textareaClasses = collect([
        'block w-full rounded-kore-md border bg-kore-bg text-kore-fg placeholder:text-kore-muted-fg',
        'transition-colors duration-150',
        'focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        $hasError ? 'border-kore-destructive focus:ring-kore-destructive/30 focus:border-kore-destructive' : 'border-kore-input',
        $autoResize ? 'resize-none overflow-hidden' : '',
        $sizeClasses,
    ])->filter()->implode(' ');
@endphp

<x-kore::field
    :label="$label"
    :hint="$maxLength ? null : $hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :field-id="$fieldId"
    :required="$required"
>
    <div
        @if($autoResize || $maxLength)
            x-data="{
                count: 0,
                @if($autoResize) autoHeight: null, @endif
                resize() {
                    let el = $refs.textarea;
                    if (!el) return;
                    @if($autoResize)
                        let prev = el.style.height;
                        el.style.height = 'auto';
                        let h = el.scrollHeight;
                        el.style.height = prev;
                        this.autoHeight = h + 'px';
                    @endif
                    @if($maxLength)
                        this.count = el.value.length;
                    @endif
                }
            }"
            x-init="$nextTick(() => resize())"
        @endif
    >
        <textarea
            {{ $attributes->merge([
                'id' => $fieldId,
                'name' => $name,
                'rows' => $rows,
                'disabled' => $disabled,
                'readonly' => $readonly,
                'required' => $required,
                'aria-invalid' => $hasError ? 'true' : null,
                'aria-describedby' => $describedBy,
                'class' => $textareaClasses,
            ])->except(['label', 'hint', 'error', 'size', 'auto-resize', 'max-length', 'show-error']) }}
            @if($maxLength)
                maxlength="{{ $maxLength }}"
            @endif
            @if($autoResize || $maxLength)
                x-ref="textarea"
                x-on:input="resize()"
            @endif
            @if($autoResize)
                x-bind:style="autoHeight ? { height: autoHeight } : {}"
            @endif
        >{{ $slot }}</textarea>

        @if($maxLength)
            <div class="mt-1 flex justify-between items-center">
                @if($hint && !$hasError)
                    <p @if($fieldId) id="{{ $fieldId }}-hint" @endif class="text-sm text-kore-muted-fg">{{ $hint }}</p>
                @else
                    <span></span>
                @endif
                <span
                    class="text-xs text-kore-muted-fg"
                    x-text="`${count}/{{ $maxLength }}`"
                ></span>
            </div>
        @endif
    </div>
</x-kore::field>

@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'icon' => null,
    'toggleable' => null,
    'strength' => null,
    'minLength' => null,
    'showRules' => true,
    'disabled' => false,
    'readonly' => false,
    'required' => false,
    'showError' => true,
    // `false` apaga las transiciones de este componente y de su panel. Ver
    // KoreUi\Core\Support\Motion.
    'animated' => null,
])

@php $attributes = \KoreUi\Core\Support\Motion::marcar($attributes, $animated); @endphp

@php
    $size = $size ?? config('kore-ui.form.size', 'md');
    $toggleable = $toggleable ?? config('kore-ui.form.password.toggleable', true);
    $strength = $strength ?? config('kore-ui.form.password.strength', false);
    $minLength = $minLength ?? config('kore-ui.form.password.min_length', 8);
    $isStrengthEnabled = (bool) $strength;

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

    $fieldId = $attributes->get('id', \KoreUi\Core\Support\IdContext::para($name));

    // Associate the field's hint/error with the control (WCAG 3.3.1 / 4.1.2).
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

    $inputClasses = collect([
        'block w-full kore-control',
        $hasError ? 'kore-invalid' : '',
        $sizeClasses,
        $icon ? match($size) { 'sm' => 'pl-8', 'lg' => 'pl-11', default => 'pl-10' } : '',
        $toggleable ? match($size) { 'sm' => 'pr-8', 'lg' => 'pr-11', default => 'pr-10' } : '',
    ])->filter()->implode(' ');

    $tr = fn (string $clave, string $porDefecto) => config("kore-ui.form.translations.$clave", $porDefecto);

    $mostrarLabel = $tr('password_show', 'Mostrar la contraseña');
    $ocultarLabel = $tr('password_hide', 'Ocultar la contraseña');

    // Los textos del medidor viajan al plugin: estaban escritos en inglés dentro
    // de `resources/js/form/password.js`, y ahí no llegaba ni publicar las
    // vistas — había que recompilar el bundle para cambiarlos.
    $jsConfig = json_encode([
        'minLength' => $minLength,
        'textos' => [
            'niveles' => [
                $tr('password_weak', 'Débil'),
                $tr('password_fair', 'Regular'),
                $tr('password_good', 'Buena'),
                $tr('password_strong', 'Fuerte'),
            ],
            'reglas' => [
                'length' => str_replace(':min', (string) $minLength, $tr('password_rule_length', 'Al menos :min caracteres')),
                'uppercase' => $tr('password_rule_uppercase', 'Una letra mayúscula'),
                'lowercase' => $tr('password_rule_lowercase', 'Una letra minúscula'),
                'number' => $tr('password_rule_number', 'Un número'),
                'special' => $tr('password_rule_special', 'Un carácter especial'),
            ],
        ],
    ], JSON_UNESCAPED_UNICODE);
@endphp

<x-kore::field
    :label="$label"
    :hint="$hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :field-id="$fieldId"
    :required="$required"
>
    @if($isStrengthEnabled)
    <div x-data="KorePassword({{ $jsConfig }})">
        <div class="relative">
            @if($icon)
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconSizeClasses }} text-kore-muted-fg" />
                </div>
            @endif

            <input
                {{ $attributes->merge([
                    'id' => $fieldId,
                    'name' => $name,
                    'disabled' => $disabled,
                    'readonly' => $readonly,
                    'required' => $required,
                    'aria-invalid' => $hasError ? 'true' : null,
                    'aria-describedby' => $describedBy,
                    'autocomplete' => 'current-password',
                    'class' => $inputClasses,
                ])->except(['label', 'hint', 'error', 'size', 'icon', 'toggleable', 'strength', 'min-length', 'show-rules', 'show-error']) }}
                x-bind:type="show ? 'text' : 'password'"
                x-on:input="onInput($event)"
            />

            @if($toggleable)
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <button
                        type="button"
                        x-on:click="show = !show"
                        class="rounded-kore-sm text-kore-muted-fg hover:text-kore-fg kore-transition kore-focus"
                        x-bind:aria-label="show ? @js($ocultarLabel) : @js($mostrarLabel)"
                    >
                        <x-lucide-eye x-show="!show" class="{{ $iconSizeClasses }}" />
                        <x-lucide-eye-off x-show="show" x-cloak class="{{ $iconSizeClasses }}" />
                    </button>
                </div>
            @endif
        </div>

        {{-- Strength meter --}}
        <div class="mt-2 space-y-2" x-show="value.length > 0" x-cloak>
            {{-- Progress bar: 4 segments --}}
            <div class="flex gap-1">
                <template x-for="i in 4" :key="i">
                    <div
                        class="h-1.5 flex-1 rounded-full kore-transition-slow"
                        x-bind:class="i <= level ? levelColorClass : 'bg-kore-muted'"
                    ></div>
                </template>
            </div>
            {{-- Level label --}}
            <p class="text-xs font-medium" x-bind:class="levelTextClass" x-text="levelLabel"></p>
            {{-- Rules checklist --}}
            @if($showRules)
            <ul class="space-y-1">
                <template x-for="rule in rules" :key="rule.id">
                    <li class="flex items-center gap-1.5 text-xs">
                        <template x-if="rule.passed">
                            <x-lucide-check class="size-3 text-kore-success-text" />
                        </template>
                        <template x-if="!rule.passed">
                            <x-lucide-x class="size-3 text-kore-muted-fg" />
                        </template>
                        <span x-bind:class="rule.passed ? 'text-kore-success-text' : 'text-kore-muted-fg'" x-text="rule.label"></span>
                    </li>
                </template>
            </ul>
            @endif
        </div>
    </div>
    @else
    <div class="relative" x-data="{ show: false }">
        @if($icon)
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconSizeClasses }} text-kore-muted-fg" />
            </div>
        @endif

        <input
            {{ $attributes->merge([
                'id' => $fieldId,
                'name' => $name,
                'disabled' => $disabled,
                'readonly' => $readonly,
                'required' => $required,
                'aria-invalid' => $hasError ? 'true' : null,
                'aria-describedby' => $describedBy,
                'autocomplete' => 'current-password',
                'class' => $inputClasses,
            ])->except(['label', 'hint', 'error', 'size', 'icon', 'toggleable', 'strength', 'min-length', 'show-rules', 'show-error']) }}
            x-bind:type="show ? 'text' : 'password'"
        />

        @if($toggleable)
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <button
                    type="button"
                    x-on:click="show = !show"
                    class="rounded-kore-sm text-kore-muted-fg hover:text-kore-fg kore-transition kore-focus"
                    x-bind:aria-label="show ? @js($ocultarLabel) : @js($mostrarLabel)"
                >
                    <x-lucide-eye x-show="!show" class="{{ $iconSizeClasses }}" />
                    <x-lucide-eye-off x-show="show" x-cloak class="{{ $iconSizeClasses }}" />
                </button>
            </div>
        @endif
    </div>
    @endif
</x-kore::field>

@props([
    'selected' => null,
    'linear' => false,
    'variant' => null,
])

@php
    $variant = $variant ?? config('kore-ui.ui.stepper.variant', 'horizontal');
@endphp

<div
    {{ $attributes
        ->except(['selected', 'linear', 'variant'])
        ->class(['w-full'])
    }}
    x-data="KoreStepper({ selected: @js($selected), linear: @js($linear), variant: @js($variant) })"
>
    <input type="hidden" x-ref="hiddenInput" {{ $attributes->wire('model') }} />

    {{-- Step indicators --}}
    @if($variant === 'vertical')
        {{-- VERTICAL LAYOUT --}}
        <div class="mb-4">
            <template x-for="(step, index) in steps" :key="step.id">
                <div class="flex items-stretch">
                    {{-- Circle + connector column --}}
                    <div class="flex flex-col items-center shrink-0">
                        <button
                            type="button"
                            class="relative flex items-center justify-center size-8 rounded-full border-2 text-sm font-semibold transition-all shrink-0 focus-visible:ring-2 focus-visible:ring-kore-ring focus-visible:ring-offset-2 ring-offset-kore-bg outline-none"
                            x-on:click="select(step.id)"
                            x-bind:disabled="step.disabled"
                            x-bind:class="{
                                'border-kore-primary bg-kore-primary text-kore-primary-fg': getStepStatus(step.id) === 'active',
                                'border-kore-success bg-kore-success text-kore-success-fg': getStepStatus(step.id) === 'complete',
                                'border-kore-destructive bg-kore-destructive text-kore-destructive-fg': getStepStatus(step.id) === 'error',
                                'border-kore-border bg-kore-surface text-kore-muted-fg': getStepStatus(step.id) === 'pending',
                                'opacity-50 cursor-not-allowed': step.disabled,
                                'cursor-pointer': !step.disabled,
                            }"
                            x-bind:aria-current="getStepStatus(step.id) === 'active' ? 'step' : false"
                        >
                            <template x-if="getStepStatus(step.id) === 'complete'">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                            </template>
                            <template x-if="getStepStatus(step.id) === 'error'">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            </template>
                            <template x-if="getStepStatus(step.id) === 'active' || getStepStatus(step.id) === 'pending'">
                                <span>
                                    <span x-show="step.iconHtml" x-html="step.iconHtml" class="[&>svg]:size-4"></span>
                                    <span x-show="!step.iconHtml" x-text="getStepNumber(step.id)"></span>
                                </span>
                            </template>
                        </button>

                        {{-- Vertical connector --}}
                        <template x-if="index < steps.length - 1">
                            <div class="w-px flex-1 min-h-6 my-1 transition-colors"
                                 x-bind:class="getStepStatus(steps[index + 1]?.id) !== 'pending' ? 'bg-kore-success' : 'bg-kore-border'"></div>
                        </template>
                    </div>

                    {{-- Label + description --}}
                    <div class="ml-3 pb-6 flex-1 min-w-0" x-show="step.label">
                        <p class="text-sm font-medium pt-1 transition-colors"
                           x-bind:class="{
                               'text-kore-primary': getStepStatus(step.id) === 'active',
                               'text-kore-fg': getStepStatus(step.id) === 'complete',
                               'text-kore-destructive': getStepStatus(step.id) === 'error',
                               'text-kore-muted-fg': getStepStatus(step.id) === 'pending',
                           }"
                           x-text="step.label"></p>
                        <p class="text-xs text-kore-muted-fg mt-0.5" x-show="step.description" x-text="step.description"></p>
                    </div>
                </div>
            </template>
        </div>
    @else
        {{-- HORIZONTAL / COMPACT LAYOUT --}}
        {{-- Each step takes flex-1, circle centered, connectors split as halves --}}
        <div @class([
            'flex',
            'mb-6' => $variant === 'horizontal',
            'mb-4' => $variant === 'compact',
        ])>
            <template x-for="(step, index) in steps" :key="step.id">
                <div class="flex-1 flex flex-col items-center">
                    {{-- Circle row with split connector halves --}}
                    <div class="flex items-center w-full">
                        {{-- Left connector half --}}
                        <div class="flex-1 h-px transition-colors"
                             x-bind:class="index > 0 ? (getStepStatus(step.id) !== 'pending' ? 'bg-kore-success' : 'bg-kore-border') : 'bg-transparent'"></div>

                        {{-- Circle --}}
                        <button
                            type="button"
                            class="relative flex items-center justify-center size-8 rounded-full border-2 text-sm font-semibold transition-all shrink-0 mx-2 focus-visible:ring-2 focus-visible:ring-kore-ring focus-visible:ring-offset-2 ring-offset-kore-bg outline-none"
                            x-on:click="select(step.id)"
                            x-bind:disabled="step.disabled"
                            x-bind:class="{
                                'border-kore-primary bg-kore-primary text-kore-primary-fg': getStepStatus(step.id) === 'active',
                                'border-kore-success bg-kore-success text-kore-success-fg': getStepStatus(step.id) === 'complete',
                                'border-kore-destructive bg-kore-destructive text-kore-destructive-fg': getStepStatus(step.id) === 'error',
                                'border-kore-border bg-kore-surface text-kore-muted-fg': getStepStatus(step.id) === 'pending',
                                'opacity-50 cursor-not-allowed': step.disabled,
                                'cursor-pointer': !step.disabled,
                            }"
                            x-bind:aria-current="getStepStatus(step.id) === 'active' ? 'step' : false"
                        >
                            <template x-if="getStepStatus(step.id) === 'complete'">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                            </template>
                            <template x-if="getStepStatus(step.id) === 'error'">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            </template>
                            <template x-if="getStepStatus(step.id) === 'active' || getStepStatus(step.id) === 'pending'">
                                <span>
                                    <span x-show="step.iconHtml" x-html="step.iconHtml" class="[&>svg]:size-4"></span>
                                    <span x-show="!step.iconHtml" x-text="getStepNumber(step.id)"></span>
                                </span>
                            </template>
                        </button>

                        {{-- Right connector half --}}
                        <div class="flex-1 h-px transition-colors"
                             x-bind:class="index < steps.length - 1 ? (getStepStatus(steps[index + 1]?.id) !== 'pending' ? 'bg-kore-success' : 'bg-kore-border') : 'bg-transparent'"></div>
                    </div>

                    {{-- Label below circle (in-flow, no absolute) --}}
                    @if($variant !== 'compact')
                        <div class="mt-2 text-center px-1 w-full" x-show="step.label">
                            <p class="text-sm font-medium transition-colors"
                               x-bind:class="{
                                   'text-kore-primary': getStepStatus(step.id) === 'active',
                                   'text-kore-fg': getStepStatus(step.id) === 'complete',
                                   'text-kore-destructive': getStepStatus(step.id) === 'error',
                                   'text-kore-muted-fg': getStepStatus(step.id) === 'pending',
                               }"
                               x-text="step.label"></p>
                            <p class="text-xs text-kore-muted-fg mt-0.5" x-show="step.description" x-text="step.description"></p>
                        </div>
                    @endif
                </div>
            </template>
        </div>
    @endif

    {{-- Step panels --}}
    <div>
        {{ $slot }}
    </div>

    {{-- Navigation slot --}}
    @if(isset($navigation))
        <div class="mt-6">
            {{ $navigation }}
        </div>
    @endif
</div>

<div class="p-6">
    {{-- Icon --}}
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full
        @switch($type)
            @case('warning') bg-kore-warning/10 @break
            @case('error') bg-kore-destructive/10 @break
            @case('info') bg-kore-info/10 @break
            @default bg-kore-primary/10
        @endswitch
    ">
        @switch($type)
            @case('warning')
                <x-lucide-triangle-alert class="h-6 w-6 text-kore-warning-text" aria-hidden="true" />
            @break
            @case('error')
                <x-lucide-circle-x class="h-6 w-6 text-kore-destructive-text" aria-hidden="true" />
            @break
            @case('info')
                <x-lucide-info class="h-6 w-6 text-kore-info-text" aria-hidden="true" />
            @break
            @default
                <x-lucide-circle-help class="h-6 w-6 text-kore-primary-text" aria-hidden="true" />
        @endswitch
    </div>

    {{-- Title --}}
    <div class="mt-3 text-center sm:mt-5">
        <h3 class="text-base font-semibold text-kore-fg">
            {{ $title }}
        </h3>

        @if($description)
            <div class="mt-2">
                <p class="text-sm text-kore-muted-fg">
                    {{ $description }}
                </p>
            </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="mt-5 flex flex-col-reverse gap-3 sm:mt-6 sm:flex-row sm:justify-center">
        <button
            type="button"
            wire:click="reject"
            class="inline-flex w-full justify-center rounded-kore-md px-3.5 py-2 text-sm font-medium
                   border border-kore-input bg-kore-field text-kore-fg shadow-kore-xs
                   hover:bg-kore-muted hover:border-kore-input-hover
                   kore-transition kore-focus kore-press sm:w-auto"
        >
            {{ $cancelText }}
        </button>

        <button
            type="button"
            wire:click="accept"
            autofocus
            class="inline-flex w-full justify-center rounded-kore-md px-3.5 py-2 text-sm font-medium
                   border border-transparent shadow-kore-solid
                   kore-transition kore-focus kore-press sm:w-auto
                   @if(in_array($type, ['warning', 'error']))
                       bg-kore-destructive text-kore-destructive-fg hover:bg-kore-destructive/90
                   @else
                       bg-kore-primary text-kore-primary-fg hover:bg-kore-primary/90
                   @endif"
        >
            {{ $confirmText }}
        </button>
    </div>
</div>

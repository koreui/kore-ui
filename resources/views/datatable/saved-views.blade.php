{{-- Vistas guardadas por el usuario: el estado completo de la tabla con nombre.
     Van en el toolbar y no en la barra de presets porque son de quien usa la
     tabla, no de quien la escribió. --}}
@php
    $views = $savedViews ?? [];
    $t     = $translations ?? [];
@endphp

<x-kore::dropdown position="bottom-end" width="260" :persistent="true" max-height="70vh">
    <x-slot:trigger>
        <button
            type="button"
            class="kore-tool"
        >
            <x-lucide-bookmark class="size-4 text-kore-muted-fg" />
            <span>{{ $t['views'] ?? 'Vistas' }}</span>
            @if($activeSavedView)
                <span class="size-1.5 rounded-full bg-kore-primary"></span>
            @endif
        </button>
    </x-slot:trigger>

    @if(count($views) > 0)
        <div class="py-1">
            @foreach($views as $view)
                <div class="group flex items-center gap-1 px-1">
                    <button
                        type="button"
                        wire:click="applySavedView(@js($view->id))"
                        x-on:click="close()"
                        class="flex-1 flex items-center gap-2 px-2 py-2 text-sm text-left rounded-kore-sm kore-option hover:bg-kore-accent focus:bg-kore-accent {{ $activeSavedView === $view->id ? 'text-kore-primary-text font-medium' : 'text-kore-fg' }}"
                        role="menuitem"
                    >
                        <x-lucide-bookmark class="size-3.5 shrink-0 text-kore-muted-fg" />
                        <span class="truncate">{{ $view->name }}</span>
                    </button>

                    <button
                        type="button"
                        wire:click="deleteSavedView(@js($view->id))"
                        aria-label="{{ ($t['delete_view'] ?? 'Eliminar vista') . ': ' . $view->name }}"
                        class="p-1.5 rounded-kore-sm text-kore-muted-fg/0 group-hover:text-kore-muted-fg hover:!text-kore-destructive-text hover:bg-kore-destructive/10 kore-transition focus-visible:text-kore-muted-fg kore-focus"
                    >
                        <x-lucide-trash-2 class="size-3.5" />
                    </button>
                </div>
            @endforeach
        </div>

        <x-kore::dropdown.separator />
    @else
        <p class="px-3 py-2 text-xs text-kore-muted-fg">
            {{ $t['no_views'] ?? 'Aún no has guardado ninguna vista.' }}
        </p>
    @endif

    {{-- Guardar la combinación actual --}}
    <div class="p-2 space-y-2">
        <label for="kore-view-name-{{ $this->getId() }}" class="block text-xs font-medium text-kore-muted-fg">
            {{ $t['save_current_view'] ?? 'Guardar la vista actual' }}
        </label>
        <div class="flex items-center gap-1.5">
            <input
                type="text"
                id="kore-view-name-{{ $this->getId() }}"
                wire:model="savedViewName"
                wire:keydown.enter="saveCurrentView"
                maxlength="60"
                placeholder="{{ $t['view_name'] ?? 'Nombre' }}"
                class="flex-1 min-w-0 kore-control text-xs py-1.5 px-2.5"
            />
            <button
                type="button"
                wire:click="saveCurrentView"
                class="shrink-0 inline-flex items-center justify-center rounded-kore-md bg-kore-primary px-2.5 py-1.5 text-xs font-medium text-kore-primary-fg hover:bg-kore-primary/90 kore-transition"
            >
                {{ $t['save'] ?? 'Guardar' }}
            </button>
        </div>
    </div>

    @if($activeSavedView)
        <div class="border-t border-kore-border px-3 py-2">
            <button
                type="button"
                wire:click="clearSavedView"
                x-on:click="close()"
                class="text-xs text-kore-primary-text hover:text-kore-primary/80 kore-transition"
            >
                {{ $t['clear_view'] ?? 'Salir de la vista' }}
            </button>
        </div>
    @endif
</x-kore::dropdown>

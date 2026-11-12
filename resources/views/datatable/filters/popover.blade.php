@php
    $filterDefs = $filterDefs ?? [];
    $filterCount = $filterCount ?? 0;
    $translations = $translations ?? [];
@endphp

@if(count($filterDefs) > 0)
    <div wire:ignore>
        <x-kore::dropdown width="380" max-height="70vh">
            <x-slot:trigger>
                <button
                    type="button"
                    class="kore-tool"
                >
                    <x-lucide-filter class="size-4" />
                    <span>{{ $translations['filters'] ?? 'Filtros' }}</span>
                    {{-- El conteo se lee de $wire, no de Blade: este trigger vive dentro
                         del wire:ignore de este layout, así que su DOM nunca se morfea
                         y un valor impreso por PHP se quedaría en el del primer render.
                         $wire.filterCount es síncrono y reactivo (a diferencia de
                         $wire.getActiveFilterCount(), que devuelve una Promise y
                         además dispara un round-trip por evaluación). --}}
                    <span
                        x-show="$wire.filterCount > 0"
                        x-text="$wire.filterCount"
                        @if(($filterCount ?? 0) === 0) style="display: none" @endif
                        class="inline-flex items-center justify-center size-5 rounded-full bg-kore-primary text-kore-primary-fg text-xs font-medium"
                    >{{ $filterCount ?? 0 }}</span>
                </button>
            </x-slot:trigger>

            <div class="p-4 space-y-4">
                <div class="text-sm font-semibold text-kore-fg">
                    {{ $translations['filters'] ?? 'Filtros' }}
                </div>

                @include('kore::datatable.filters.fields', ['filterDefs' => $filterDefs])
            </div>
        </x-kore::dropdown>
    </div>
@endif

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
                    class="inline-flex items-center gap-1.5 rounded-kore-md border border-kore-input bg-kore-bg px-3 py-1.5 text-sm text-kore-fg hover:bg-kore-muted transition-colors"
                >
                    <x-lucide-filter class="size-4" />
                    <span>{{ $translations['filters'] ?? 'Filtros' }}</span>
                    <template x-if="$wire.getActiveFilterCount() > 0">
                        <span
                            class="inline-flex items-center justify-center size-5 rounded-full bg-kore-primary text-kore-primary-fg text-xs font-medium"
                            x-text="$wire.getActiveFilterCount()"
                        ></span>
                    </template>
                </button>
            </x-slot:trigger>

            <div class="p-4 space-y-4">
                <div class="text-xs font-semibold text-kore-muted-fg uppercase tracking-wider">
                    {{ $translations['filters'] ?? 'Filtros' }}
                </div>

                @foreach($filterDefs as $filter)
                    <div>
                        <label class="block text-xs font-medium text-kore-muted-fg mb-1.5">
                            {{ $filter->getLabel() }}
                        </label>
                        @include('kore::datatable.filters.types.' . $filter->getType(), ['filter' => $filter])
                    </div>
                @endforeach
            </div>
        </x-kore::dropdown>
    </div>
@endif

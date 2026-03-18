@php
    $searchDebounce = $searchDebounce ?? 300;
    $perPageOptions = $perPageOptions ?? [];
    $translations = $translations ?? [];
@endphp

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-4 py-3 border-b border-kore-border">
    {{-- Search --}}
    <div class="w-full sm:w-auto sm:min-w-[260px]">
        <x-kore::input
            type="search"
            icon="search"
            size="sm"
            :placeholder="$translations['search'] ?? 'Buscar...'"
            :clearable="true"
            wire:model.live.debounce.300ms="search"
            data-datatable-search
        />
    </div>

    {{-- Per Page --}}
    <div class="flex items-center gap-2 text-sm text-kore-muted-fg">
        <span>{{ $translations['per_page'] ?? 'Por página' }}</span>
        <select
            wire:model.live="perPage"
            class="bg-kore-bg text-kore-fg border border-kore-input rounded-kore-md text-sm py-1 px-2 focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary"
        >
            @foreach($perPageOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
    </div>
</div>

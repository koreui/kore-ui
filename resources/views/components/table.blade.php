@props([
    'headers' => [],
    'rows' => [],
    'striped' => false,
    'hoverable' => true,
    'bordered' => false,
    'headerless' => false,
    'compact' => false,
    'responsive' => true,
    'density' => null,
    'emptyText' => null,
    'emptyIcon' => null,
])

@php
    $density = $compact ? 'compact' : ($density ?? config('kore-ui.datatable.density', 'normal'));
    $emptyText = $emptyText ?? config('kore-ui.datatable.empty_text', 'No se encontraron resultados');
    $emptyIcon = $emptyIcon ?? config('kore-ui.datatable.empty_icon', 'inbox');

    $densityClasses = match($density) {
        'compact' => 'px-3 py-1 text-sm',
        'relaxed' => 'px-4 py-4 text-base',
        default   => 'px-4 py-2.5 text-sm',
    };

    $headerDensityClasses = match($density) {
        'compact' => 'px-3 py-1.5 text-xs',
        'relaxed' => 'px-4 py-3 text-sm',
        default   => 'px-4 py-2 text-xs',
    };

    $rows = $rows instanceof \Illuminate\Support\Collection ? $rows->toArray() : $rows;
    $hasRows = count($rows) > 0;

    // Normalize headers: support ['Name', 'Email'] or [['key' => 'name', 'label' => 'Name']]
    $normalizedHeaders = collect($headers)->map(function ($header, $index) {
        if (is_string($header)) {
            return ['key' => $index, 'label' => $header, 'align' => 'left'];
        }
        return array_merge(['align' => 'left'], $header);
    })->all();

    $colCount = count($normalizedHeaders);
@endphp

<div {{ $attributes->class([
    'rounded-kore-lg border border-kore-border bg-kore-surface',
    'overflow-x-auto' => $responsive,
]) }}>
    <table class="min-w-full divide-y divide-kore-border">
        @unless($headerless)
            <thead class="bg-kore-muted/50">
                <tr>
                    @foreach($normalizedHeaders as $header)
                        <x-kore::table.header
                            :label="$header['label']"
                            :align="$header['align'] ?? 'left'"
                            :density-classes="$headerDensityClasses"
                        />
                    @endforeach
                </tr>
            </thead>
        @endunless

        <tbody class="divide-y divide-kore-border">
            @if($hasRows)
                @foreach($rows as $index => $row)
                    <x-kore::table.row
                        :striped="$striped"
                        :hoverable="$hoverable"
                        :index="$index"
                    >
                        @foreach($normalizedHeaders as $header)
                            @php
                                $key = $header['key'];
                                $slotName = \Illuminate\Support\Str::camel('cell-' . str_replace('.', '-', $key));
                                $hasSlot = isset($__laravel_slots[$slotName]);
                            @endphp

                            <x-kore::table.cell
                                :align="$header['align'] ?? 'left'"
                                :density-classes="$densityClasses"
                            >
                                @if($hasSlot)
                                    {{ $__laravel_slots[$slotName] }}
                                @else
                                    {{ data_get($row, $key, '') }}
                                @endif
                            </x-kore::table.cell>
                        @endforeach
                    </x-kore::table.row>
                @endforeach
            @else
                <x-kore::table.empty
                    :colspan="$colCount"
                    :text="$emptyText"
                    :icon="$emptyIcon"
                />
            @endif
        </tbody>

        @if(isset($footer))
            <tfoot class="bg-kore-muted/30 border-t border-kore-border">
                {{ $footer }}
            </tfoot>
        @endif
    </table>
</div>

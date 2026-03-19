<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\Filters\BooleanFilter;
use KoreUi\DataTable\Filters\SelectFilter;
use KoreUi\DataTable\KoreDataTable;
use KoreUi\DataTable\Presets\FilterPreset;

class TestPresetsTable extends KoreDataTable
{
    public function query(): Builder
    {
        return TestUser::query();
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable()->width(80),
            Column::make('Nombre', 'name')->sortable()->searchable(),
            Column::make('Email', 'email')->sortable()->searchable(),
            Column::make('Ciudad', 'city'),
            Column::make('Activo', 'is_active'),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Ciudad', 'city')
                ->options([
                    ['label' => 'Madrid', 'value' => 'Madrid'],
                    ['label' => 'Barcelona', 'value' => 'Barcelona'],
                ])
                ->optionLabel('label')
                ->optionValue('value'),

            BooleanFilter::make('Activo', 'is_active'),
        ];
    }

    public function filterPresets(): array
    {
        return [
            FilterPreset::make('active', 'Activos')
                ->icon('check')
                ->filters(['is_active' => '1'])
                ->count(fn () => TestUser::where('is_active', true)->count()),

            FilterPreset::make('inactive', 'Inactivos')
                ->filters(['is_active' => '0']),

            FilterPreset::make('madrid', 'Madrid')
                ->filters(['city' => 'Madrid'])
                ->sorts(['name' => 'asc']),

            FilterPreset::make('hidden_preset', 'Oculto')
                ->hidden(),
        ];
    }
}

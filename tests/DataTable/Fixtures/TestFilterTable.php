<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\Filters\BooleanFilter;
use KoreUi\DataTable\Filters\MultiSelectFilter;
use KoreUi\DataTable\Filters\NumberFilter;
use KoreUi\DataTable\Filters\NumberRangeFilter;
use KoreUi\DataTable\Filters\SelectFilter;
use KoreUi\DataTable\Filters\TextFilter;
use KoreUi\DataTable\KoreDataTable;

class TestFilterTable extends KoreDataTable
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
            Column::make('Ciudad', 'city')->searchable(),
            Column::make('Activo', 'is_active'),
            Column::make('Edad', 'age'),
        ];
    }

    public function filters(): array
    {
        return [
            TextFilter::make('Nombre', 'name')
                ->placeholder('Buscar por nombre...'),

            SelectFilter::make('Ciudad', 'city')
                ->options([
                    ['label' => 'Madrid', 'value' => 'Madrid'],
                    ['label' => 'Barcelona', 'value' => 'Barcelona'],
                    ['label' => 'Sevilla', 'value' => 'Sevilla'],
                ])
                ->optionLabel('label')
                ->optionValue('value'),

            MultiSelectFilter::make('Ciudades', 'city')
                ->key('cities')
                ->options([
                    ['label' => 'Madrid', 'value' => 'Madrid'],
                    ['label' => 'Barcelona', 'value' => 'Barcelona'],
                    ['label' => 'Sevilla', 'value' => 'Sevilla'],
                ])
                ->optionLabel('label')
                ->optionValue('value'),

            BooleanFilter::make('Activo', 'is_active'),

            NumberFilter::make('Edad mínima', 'age')
                ->key('min_age')
                ->operator('>='),

            NumberRangeFilter::make('Rango edad', 'age')
                ->key('age_range'),
        ];
    }
}

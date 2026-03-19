<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\Columns\NumberColumn;
use KoreUi\DataTable\KoreDataTable;

class TestAggregationTable extends KoreDataTable
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
            Column::make('Edad', 'age')
                ->sortable()
                ->sum('Total'),
            NumberColumn::make('Salario', 'salary')
                ->avg(2, 'Promedio'),
        ];
    }
}

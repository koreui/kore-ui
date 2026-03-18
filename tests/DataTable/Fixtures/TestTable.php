<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;

class TestTable extends KoreDataTable
{
    public function query(): Builder
    {
        return TestUser::query();
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->width(80),

            Column::make('Nombre', 'name')
                ->sortable()
                ->searchable(),

            Column::make('Email', 'email')
                ->sortable()
                ->searchable(),

            Column::make('Ciudad', 'city')
                ->searchable(),
        ];
    }
}

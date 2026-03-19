<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\BooleanColumn;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;

class TestEditableTable extends KoreDataTable
{
    public function query(): Builder
    {
        return TestUser::query();
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable()->width(80),
            Column::make('Nombre', 'name')
                ->sortable()
                ->searchable()
                ->editable()
                ->editableRules(['required', 'min:2']),
            Column::make('Email', 'email')
                ->sortable()
                ->searchable()
                ->editable(),
            BooleanColumn::make('Activo', 'is_active')
                ->editable(),
            Column::make('Ciudad', 'city'),
        ];
    }
}

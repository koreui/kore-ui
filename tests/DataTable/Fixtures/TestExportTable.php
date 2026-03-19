<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Actions\RowAction;
use KoreUi\DataTable\Columns\ActionColumn;
use KoreUi\DataTable\Columns\BooleanColumn;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;

class TestExportTable extends KoreDataTable
{
    public function configure(): void
    {
        $this->setExportEnabled();
    }

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
            BooleanColumn::make('Activo', 'is_active'),
            ActionColumn::make()->actions([
                RowAction::make('view', 'Ver')->icon('eye'),
            ]),
        ];
    }
}

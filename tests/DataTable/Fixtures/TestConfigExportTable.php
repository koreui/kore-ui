<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;

/**
 * Sin configure(): todo lo del export debe venir de la configuración global.
 */
class TestConfigExportTable extends KoreDataTable
{
    public function query(): Builder
    {
        return TestUser::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Nombre', 'name')->sortable(),
        ];
    }
}

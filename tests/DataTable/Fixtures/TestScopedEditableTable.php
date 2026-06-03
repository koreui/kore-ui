<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;

/**
 * Editable table whose query() is scoped to active users only. Used to verify
 * that inline editing cannot reach records outside the authorized dataset.
 */
class TestScopedEditableTable extends KoreDataTable
{
    public function query(): Builder
    {
        return TestUser::query()->where('is_active', true);
    }

    public function columns(): array
    {
        return [
            Column::make('Nombre', 'name')->editable(),
        ];
    }
}

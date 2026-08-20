<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\Filters\SelectFilter;
use KoreUi\DataTable\Filters\TextFilter;
use KoreUi\DataTable\KoreDataTable;

/**
 * Filtros sobre una relación en dot-notation.
 */
class TestRelationFilterTable extends KoreDataTable
{
    public function query(): Builder
    {
        return TestUser::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Nombre', 'name')->sortable(),
            Column::make('Empresa', 'company.name'),
        ];
    }

    public function filters(): array
    {
        return [
            TextFilter::make('Empresa', 'company.name'),

            // Sin opciones declaradas: acepta cualquier escalar.
            SelectFilter::make('Ciudad empresa', 'company.city'),
        ];
    }
}

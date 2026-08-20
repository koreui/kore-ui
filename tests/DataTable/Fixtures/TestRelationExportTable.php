<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;

/**
 * Tabla con una columna en dot-notation, para comprobar que el export hace el
 * mismo eager loading que la pantalla y no una consulta por fila exportada.
 */
class TestRelationExportTable extends KoreDataTable
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
            Column::make('Nombre', 'name')->sortable(),
            Column::make('Empresa', 'company.name'),
        ];
    }
}

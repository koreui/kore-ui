<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;

class TestResponsiveTable extends KoreDataTable
{
    public function configure(): void
    {
        $this->setResponsiveMode('card');
    }

    public function query(): Builder
    {
        return TestUser::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Nombre', 'name'),
            Column::make('Ciudad', 'city'),
        ];
    }
}

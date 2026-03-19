<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;

class TestSearchTable extends KoreDataTable
{
    public function query(): Builder
    {
        return TestUser::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Nombre', 'name')->searchable(),

            // Invalid simple field (contains space and special char) → must be skipped
            Column::make('Bad Simple', 'name')->searchableAs('invalid field!'),

            // Invalid relation name (contains $) → skipped before orWhereHas is called
            Column::make('Bad Relation', 'name')->searchableAs('bad$.title'),

            // Valid relation name, invalid field name (contains !) → skipped before orWhereHas is called
            Column::make('Bad RelField', 'name')->searchableAs('posts.bad!col'),
        ];
    }
}

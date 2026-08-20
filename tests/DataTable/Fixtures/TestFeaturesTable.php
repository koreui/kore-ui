<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\Filters\SelectFilter;
use KoreUi\DataTable\KoreDataTable;

class TestFeaturesTable extends KoreDataTable
{
    public function configure(): void
    {
        $this->setSavedViewsEnabled();
    }

    public function query(): Builder
    {
        return TestUser::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Usuario', 'name')
                ->sortable()
                ->description(fn ($row) => $row->email),

            Column::make('Ciudad', 'city')->sortable(),

            Column::make('Edad', 'age')
                ->description('city', 'above'),
        ];
    }

    public function filters(): array
    {
        return [SelectFilter::make('Ciudad', 'city')];
    }
}

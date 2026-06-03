<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;
use KoreUi\DataTable\Presets\FilterPreset;

class TestDefaultPresetTable extends KoreDataTable
{
    public function query(): Builder
    {
        return TestUser::query();
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id'),
            Column::make('Nombre', 'name'),
        ];
    }

    public function filterPresets(): array
    {
        return [
            FilterPreset::make('all', 'Todos')->default(),
            FilterPreset::make('active', 'Activos')->filters(['is_active' => '1']),
        ];
    }
}

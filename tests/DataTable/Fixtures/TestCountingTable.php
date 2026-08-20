<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Actions\BulkAction;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\Filters\SelectFilter;
use KoreUi\DataTable\KoreDataTable;
use KoreUi\DataTable\Presets\FilterPreset;

/**
 * Cuenta cuántas veces se reconstruye la definición de la tabla en un render.
 */
class TestCountingTable extends KoreDataTable
{
    public static int $columnCalls = 0;

    public static int $filterCalls = 0;

    public static int $presetCalls = 0;

    public static int $bulkCalls = 0;

    public static function resetCounters(): void
    {
        self::$columnCalls = 0;
        self::$filterCalls = 0;
        self::$presetCalls = 0;
        self::$bulkCalls = 0;
    }

    public function query(): Builder
    {
        return TestUser::query();
    }

    public function columns(): array
    {
        self::$columnCalls++;

        return [
            Column::make('Nombre', 'name')->sortable()->searchable(),
            Column::make('Ciudad', 'city'),
        ];
    }

    public function filters(): array
    {
        self::$filterCalls++;

        return [SelectFilter::make('Ciudad', 'city')];
    }

    public function filterPresets(): array
    {
        self::$presetCalls++;

        return [FilterPreset::make('activos', 'Activos')->filters(['is_active' => '1'])];
    }

    public function bulkActions(): array
    {
        self::$bulkCalls++;

        return [BulkAction::make('noop', 'Nada')];
    }

    public function noop(array $ids): void
    {
        //
    }
}

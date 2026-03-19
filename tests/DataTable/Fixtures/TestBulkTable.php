<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Actions\BulkAction;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;

class TestBulkTable extends KoreDataTable
{
    public array $deletedIds = [];

    public array $activatedIds = [];

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
        ];
    }

    public function bulkActions(): array
    {
        return [
            BulkAction::make('activate', 'Activar')
                ->icon('check')
                ->color('success'),

            BulkAction::make('delete', 'Eliminar')
                ->icon('trash-2')
                ->color('destructive')
                ->confirm('¿Eliminar :count registro(s)?', 'Esta acción no se puede deshacer.')
                ->separator(),
        ];
    }

    public function activate(array $ids): void
    {
        $this->activatedIds = $ids;
        TestUser::whereIn('id', $ids)->update(['is_active' => true]);
        $this->toast()->success('Activados correctamente')->send();
    }

    public function delete(array $ids): void
    {
        $this->deletedIds = $ids;
        TestUser::whereIn('id', $ids)->delete();
        $this->toast()->success('Eliminados correctamente')->send();
    }
}

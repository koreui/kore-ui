<?php

namespace KoreUi\Tests\DataTable\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Actions\BulkAction;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;

/**
 * Acciones que la interfaz no ofrece: una oculta y otra sin autorización.
 * Ninguna debe poder ejecutarse desde el navegador.
 */
class TestAuthorizedBulkTable extends KoreDataTable
{
    public static bool $ranForbidden = false;

    public static bool $ranHidden = false;

    public function query(): Builder
    {
        // Solo usuarios activos: lo que quede fuera no es suyo.
        return TestUser::query()->where('is_active', true);
    }

    public function columns(): array
    {
        return [Column::make('Nombre', 'name')];
    }

    public function bulkActions(): array
    {
        return [
            BulkAction::make('allowed', 'Permitida'),

            BulkAction::make('forbidden', 'Prohibida')
                ->authorize(fn () => false),

            BulkAction::make('secret', 'Oculta')
                ->hidden(),
        ];
    }

    public array $touched = [];

    public function allowed(array $ids): void
    {
        $this->touched = $ids;
    }

    public function forbidden(array $ids): void
    {
        self::$ranForbidden = true;
    }

    public function secret(array $ids): void
    {
        self::$ranHidden = true;
    }
}

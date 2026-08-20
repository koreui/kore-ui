<?php

namespace KoreUi\DataTable\Concerns;

trait WithColumnSelect
{
    public array $deselectedColumns = [];

    protected bool $columnSelectEnabled = true;

    protected function applyColumnSelectConfig(): void
    {
        $this->columnSelectEnabled = config('kore-ui.datatable.column_select', true);
        $this->deselectedColumns = session($this->getColumnSelectSessionKey(), []);
    }

    public function toggleColumnVisibility(string $field): void
    {
        // Sin esto, la sesión acumula campos inventados indefinidamente.
        if (! collect($this->cachedColumns())->contains(fn ($column) => $column->getField() === $field)) {
            return;
        }

        if (in_array($field, $this->deselectedColumns, true)) {
            $this->deselectedColumns = array_values(
                array_diff($this->deselectedColumns, [$field])
            );
        } else {
            $this->deselectedColumns[] = $field;
        }

        session()->put($this->getColumnSelectSessionKey(), $this->deselectedColumns);
    }

    public function resetColumnSelect(): void
    {
        $this->deselectedColumns = [];
        session()->forget($this->getColumnSelectSessionKey());
    }

    public function isColumnDeselected(string $field): bool
    {
        return in_array($field, $this->deselectedColumns, true);
    }

    public function getSelectableColumns(): array
    {
        return collect($this->cachedColumns())
            ->reject(fn ($column) => $column->isHidden())
            ->values()
            ->all();
    }

    public function isColumnSelectEnabled(): bool
    {
        return $this->columnSelectEnabled;
    }

    public function setColumnSelectEnabled(bool $enabled): static
    {
        $this->columnSelectEnabled = $enabled;

        return $this;
    }

    /**
     * La clave incluye el prefijo de tabla igual que urlKey() y pageName(): sin
     * él, dos instancias de la misma clase en una página compartían las columnas
     * ocultas y esconder «Email» en una la escondía en la otra.
     */
    protected function getColumnSelectSessionKey(): string
    {
        $prefix = $this->tablePrefix();

        // Sin nombre de tabla la clave no cambia, así que las sesiones abiertas
        // conservan sus columnas ocultas.
        return 'kore-datatable-columns:' . static::class . ($prefix === '' ? '' : ':' . $prefix);
    }
}

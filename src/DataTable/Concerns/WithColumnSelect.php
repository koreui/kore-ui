<?php

namespace KoreUi\DataTable\Concerns;

trait WithColumnSelect
{
    public array $deselectedColumns = [];

    protected bool $columnSelectEnabled = true;

    public function mountWithColumnSelect(): void
    {
        $this->columnSelectEnabled = config('kore-ui.datatable.column_select', true);
        $this->deselectedColumns = session($this->getColumnSelectSessionKey(), []);
    }

    public function toggleColumnVisibility(string $field): void
    {
        if (in_array($field, $this->deselectedColumns)) {
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
        return in_array($field, $this->deselectedColumns);
    }

    public function getSelectableColumns(): array
    {
        return collect($this->columns())
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

    protected function getColumnSelectSessionKey(): string
    {
        return 'kore-datatable-columns:' . static::class;
    }
}

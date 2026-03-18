<?php

namespace KoreUi\DataTable\Columns\Concerns;

trait HasSorting
{
    protected bool $sortable = false;

    protected ?string $sortField = null;

    public function sortable(bool $condition = true): static
    {
        $this->sortable = $condition;

        return $this;
    }

    public function sortableAs(string $field): static
    {
        $this->sortable = true;
        $this->sortField = $field;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function getSortField(): string
    {
        return $this->sortField ?? $this->field;
    }
}

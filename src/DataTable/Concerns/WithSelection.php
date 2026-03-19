<?php

namespace KoreUi\DataTable\Concerns;

trait WithSelection
{
    protected bool $selectionEnabled = true;

    protected string $primaryKey = 'id';

    public function isSelectionEnabled(): bool
    {
        if (! $this->selectionEnabled) {
            return false;
        }

        return $this->hasBulkActions();
    }

    /**
     * Get all row IDs from the current page (for Alpine select-all).
     */
    public function getRowIds($rows): array
    {
        return collect($rows->items())
            ->pluck($this->primaryKey)
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function setPrimaryKey(string $key): static
    {
        $this->primaryKey = $key;

        return $this;
    }

    public function setSelectionEnabled(bool $enabled): static
    {
        $this->selectionEnabled = $enabled;

        return $this;
    }
}

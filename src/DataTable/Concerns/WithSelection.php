<?php

namespace KoreUi\DataTable\Concerns;

trait WithSelection
{
    protected bool $selectionEnabled = true;

    protected string $primaryKey = 'id';

    /**
     * Selected row IDs, stored as strings. Public so Livewire persists the
     * selection in the snapshot across morphs/pagination — this is what makes
     * cross-page selection survive a page change (the previous Alpine-only
     * state did not).
     */
    public array $selected = [];

    /**
     * "Select every row matching the current filters" mode. When true the
     * concrete IDs are resolved server-side at action time (getAllMatchingIds),
     * never trusted from the client.
     */
    public bool $selectAllMatching = false;

    public function isSelectionEnabled(): bool
    {
        if (! $this->selectionEnabled) {
            return false;
        }

        return $this->hasBulkActions();
    }

    /**
     * Get all row IDs from the current page (normalized to strings).
     */
    public function getRowIds($rows): array
    {
        return collect($rows->items())
            ->pluck($this->primaryKey)
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    // --- Mutators (wire entry points) ---

    /**
     * Toggle a single row. Leaving "select all matching" first materializes the
     * current page into an explicit selection (mirrors the previous Alpine
     * behavior) before toggling.
     */
    public function toggleRow(string $id): void
    {
        $id = (string) $id;

        if ($this->selectAllMatching) {
            $this->selected = $this->getRowIds($this->getRows());
            $this->selectAllMatching = false;
        }

        $index = array_search($id, $this->selected, true);

        if ($index === false) {
            $this->selected[] = $id;
        } else {
            unset($this->selected[$index]);
            $this->selected = array_values($this->selected);
        }
    }

    /**
     * Toggle the whole current page. Recomputes the page IDs server-side so the
     * client cannot inject arbitrary IDs into the selection.
     */
    public function toggleSelectAll(): void
    {
        if ($this->selectAllMatching) {
            $this->clearSelection();

            return;
        }

        $pageIds = $this->getRowIds($this->getRows());

        if ($this->isPageFullySelected($pageIds)) {
            $this->selected = array_values(array_diff($this->selected, $pageIds));
        } else {
            $this->selected = array_values(array_unique([...$this->selected, ...$pageIds]));
        }
    }

    /**
     * Add a contiguous range (computed client-side for shift-click) to the
     * selection, unioned with the existing one. This is the only entry point
     * that trusts a client-provided ID list, and it only ever adds.
     */
    public function selectRange(array $ids): void
    {
        if ($this->selectAllMatching) {
            $this->selected = $this->getRowIds($this->getRows());
            $this->selectAllMatching = false;
        }

        $ids = array_map(fn ($id) => (string) $id, $ids);
        $this->selected = array_values(array_unique([...$this->selected, ...$ids]));
    }

    public function enableSelectAllMatching(): void
    {
        $this->selectAllMatching = true;
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAllMatching = false;
    }

    // --- Server-side state getters (consumed by the Blade views) ---

    public function isRowSelected(string $id): bool
    {
        return $this->selectAllMatching || in_array((string) $id, $this->selected, true);
    }

    public function isPageFullySelected(array $pageIds): bool
    {
        if ($this->selectAllMatching) {
            return true;
        }

        return $pageIds !== [] && array_diff($pageIds, $this->selected) === [];
    }

    public function isPagePartiallySelected(array $pageIds): bool
    {
        if ($this->selectAllMatching || $pageIds === []) {
            return false;
        }

        $hasSome = array_intersect($pageIds, $this->selected) !== [];

        return $hasSome && ! $this->isPageFullySelected($pageIds);
    }

    public function hasSelection(): bool
    {
        return $this->selectAllMatching || $this->selected !== [];
    }

    /**
     * True when the selection includes rows that are not on the current page.
     */
    public function hasOffPageSelection(array $pageIds): bool
    {
        if ($this->selectAllMatching) {
            return false;
        }

        return array_diff($this->selected, $pageIds) !== [];
    }

    /**
     * Offer "select all matching" once the whole page is selected and more rows
     * match the current filters beyond the current selection.
     */
    public function canSelectAllMatching(array $pageIds, int $total): bool
    {
        return ! $this->selectAllMatching
            && $this->isPageFullySelected($pageIds)
            && $total > count($this->selected);
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

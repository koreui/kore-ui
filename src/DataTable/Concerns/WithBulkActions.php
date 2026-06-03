<?php

namespace KoreUi\DataTable\Concerns;

use KoreUi\Core\Concerns\InteractsWithFeedback;
use KoreUi\DataTable\Actions\BulkAction;
use KoreUi\DataTable\Events\BulkActionExecuted;

trait WithBulkActions
{
    use InteractsWithFeedback;

    private array $_selectedIds = [];

    public string $pendingBulkIdentifier = '';

    /**
     * Entry point called from Alpine. Handles confirm flow or direct execution.
     */
    public function executeBulkAction(string $identifier, array $selectedIds): void
    {
        $this->_selectedIds = $selectedIds;

        $action = $this->findBulkAction($identifier);

        if (! $action) {
            return;
        }

        if ($action->hasConfirm()) {
            $this->pendingBulkIdentifier = $identifier;
            $count = count($selectedIds);
            $message = strtr($action->getConfirmMessage(), [':count' => $count]);

            $confirm = $this->confirm($message)
                ->onConfirm('confirmBulkAction', [$identifier, $selectedIds]);

            if ($action->getConfirmDescription()) {
                $description = strtr($action->getConfirmDescription(), [':count' => $count]);
                $confirm->description($description);
            }

            $confirm->send();

            return;
        }

        $this->runBulkAction($identifier, $selectedIds);
    }

    /**
     * Execute a bulk action against EVERY row matching the current search/filters
     * (the "select all matching" mode), resolving ids server-side instead of
     * trusting the client-provided list.
     */
    public function executeBulkActionMatching(string $identifier): void
    {
        $this->executeBulkAction($identifier, $this->getAllMatchingIds());
    }

    /**
     * Primary keys of every row matching the current search/filters.
     *
     * @return array<int, string>
     */
    protected function getAllMatchingIds(): array
    {
        $query = $this->query();
        $query = $this->applySearch($query);
        $query = $this->applyFilters($query);

        $primaryKey = $this->getPrimaryKey();

        return $query->pluck($query->getModel()->qualifyColumn($primaryKey))
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /**
     * Called after user confirms the dialog.
     */
    public function confirmBulkAction(string $identifier, array $selectedIds): void
    {
        if ($this->pendingBulkIdentifier !== $identifier) {
            return;
        }

        $this->_selectedIds = $selectedIds;
        $this->runBulkAction($identifier, $selectedIds);
    }

    /**
     * Execute the actual bulk action method on the component.
     */
    protected function runBulkAction(string $identifier, array $selectedIds): void
    {
        try {
            if (method_exists($this, $identifier)) {
                $this->{$identifier}($selectedIds);
            }

            event(new BulkActionExecuted(static::class, $identifier, $selectedIds, count($selectedIds)));
        } finally {
            $this->clearSelected();

            // Bulk actions may change row counts → refresh preset badges.
            if (method_exists($this, 'invalidatePresetCounts')) {
                $this->invalidatePresetCounts();
            }
        }
    }

    public function getSelected(): array
    {
        return $this->_selectedIds;
    }

    public function clearSelected(): void
    {
        $this->_selectedIds          = [];
        $this->pendingBulkIdentifier = '';
        $this->dispatch('kore:datatable-clear-selection');
    }

    /**
     * Get visible bulk actions.
     *
     * @return BulkAction[]
     */
    public function resolveBulkActions(): array
    {
        return collect($this->bulkActions())
            ->reject(fn (BulkAction $action) => $action->isHidden())
            ->values()
            ->all();
    }

    public function hasBulkActions(): bool
    {
        return count($this->bulkActions()) > 0;
    }

    protected function findBulkAction(string $identifier): ?BulkAction
    {
        return collect($this->bulkActions())
            ->first(fn (BulkAction $action) => $action->getIdentifier() === $identifier);
    }
}

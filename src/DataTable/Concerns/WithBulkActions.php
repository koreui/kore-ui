<?php

namespace KoreUi\DataTable\Concerns;

use KoreUi\Core\Concerns\InteractsWithFeedback;
use KoreUi\DataTable\Actions\BulkAction;
use KoreUi\DataTable\Events\BulkActionExecuted;

trait WithBulkActions
{
    use InteractsWithFeedback;

    private array $_selectedIds = [];

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
     * Called after user confirms the dialog.
     */
    public function confirmBulkAction(string $identifier, array $selectedIds): void
    {
        $this->_selectedIds = $selectedIds;
        $this->runBulkAction($identifier, $selectedIds);
    }

    /**
     * Execute the actual bulk action method on the component.
     */
    protected function runBulkAction(string $identifier, array $selectedIds): void
    {
        if (method_exists($this, $identifier)) {
            $this->{$identifier}($selectedIds);
        }

        event(new BulkActionExecuted(static::class, $identifier, $selectedIds, count($selectedIds)));

        $this->clearSelected();
    }

    public function getSelected(): array
    {
        return $this->_selectedIds;
    }

    public function clearSelected(): void
    {
        $this->_selectedIds = [];
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

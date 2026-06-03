<?php

namespace KoreUi\DataTable\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

trait WithSorting
{
    public array $sorts = [];

    #[Locked]
    public ?string $defaultSortColumn = null;

    #[Locked]
    public string $defaultSortDirection = 'asc';

    #[Locked]
    public array $defaultSorts = [];

    public function sortBy(string $column): void
    {
        if (! in_array($column, $this->getSortableFields(), true)) {
            return;
        }

        $current = $this->sorts[$column] ?? null;

        $this->sorts[$column] = match ($current) {
            null    => 'asc',
            'asc'   => 'desc',
            'desc'  => null,
            default => null,
        };

        if ($this->sorts[$column] === null) {
            unset($this->sorts[$column]);
        }
    }

    public function setDefaultSort(string $column, string $direction = 'asc'): static
    {
        $this->defaultSortColumn = $column;
        $this->defaultSortDirection = $direction;
        $this->defaultSorts = [$column => $direction];

        return $this;
    }

    public function addDefaultSort(string $column, string $direction = 'asc'): static
    {
        $this->defaultSorts[$column] = $direction;

        return $this;
    }

    public function getSortDirection(string $column): ?string
    {
        return $this->sorts[$column] ?? null;
    }

    public function removeSortBy(string $column): void
    {
        unset($this->sorts[$column]);
    }

    public function clearSorts(): void
    {
        $this->sorts = [];
    }

    public function getActiveSorts(): array
    {
        $activeSorts = $this->getValidatedSorts();

        if (empty($activeSorts)) {
            return [];
        }

        $columnsMap = collect($this->columns())
            ->filter(fn ($col) => $col->isSortable())
            ->mapWithKeys(fn ($col) => [$col->getSortField() => $col->getLabel()])
            ->all();

        $result = [];

        foreach ($activeSorts as $field => $direction) {
            $result[] = [
                'field'     => $field,
                'label'     => $columnsMap[$field] ?? $field,
                'direction' => $direction,
            ];
        }

        return $result;
    }

    protected function applySorts(Builder $query): Builder
    {
        $activeSorts = $this->getValidatedSorts();

        if (empty($activeSorts)) {
            // Defaults are developer-controlled (#[Locked]) and trusted as-is.
            $activeSorts = $this->defaultSorts ?: (
                $this->defaultSortColumn
                    ? [$this->defaultSortColumn => $this->defaultSortDirection]
                    : []
            );
        }

        foreach ($activeSorts as $column => $direction) {
            $query->orderBy($column, $this->normalizeSortDirection($direction));
        }

        return $query;
    }

    /**
     * Filter the client-hydrated $sorts against the sortable-column whitelist.
     *
     * $sorts is a public property and can be tampered with from the browser,
     * so it must never reach the query builder unvalidated (prevents ordering
     * by arbitrary/unindexed columns and bypassing the declared whitelist).
     */
    protected function getValidatedSorts(): array
    {
        $sortableFields = $this->getSortableFields();

        return collect($this->sorts)
            ->filter(fn ($dir, $column) => $dir !== null && in_array($column, $sortableFields, true))
            ->all();
    }

    /**
     * The list of fields that columns have declared as sortable.
     */
    protected function getSortableFields(): array
    {
        return collect($this->columns())
            ->filter(fn ($col) => $col->isSortable())
            ->map(fn ($col) => $col->getSortField())
            ->values()
            ->all();
    }

    /**
     * Coerce any direction to a safe SQL keyword.
     */
    protected function normalizeSortDirection(mixed $direction): string
    {
        return strtolower((string) $direction) === 'desc' ? 'desc' : 'asc';
    }
}

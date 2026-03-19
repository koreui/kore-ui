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

    public function sortBy(string $column): void
    {
        $sortableFields = collect($this->columns())
            ->filter(fn ($col) => $col->isSortable())
            ->map(fn ($col) => $col->getSortField())
            ->values()
            ->all();

        if (! in_array($column, $sortableFields)) {
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

        return $this;
    }

    public function getSortDirection(string $column): ?string
    {
        return $this->sorts[$column] ?? null;
    }

    protected function applySorts(Builder $query): Builder
    {
        $activeSorts = array_filter($this->sorts, fn ($dir) => $dir !== null);

        if (empty($activeSorts) && $this->defaultSortColumn) {
            $activeSorts = [$this->defaultSortColumn => $this->defaultSortDirection];
        }

        foreach ($activeSorts as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }
}

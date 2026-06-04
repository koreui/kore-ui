<?php

namespace KoreUi\DataTable\Concerns;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Events\FilterApplied;
use KoreUi\DataTable\Filters\Filter;
use Livewire\Attributes\Locked;

trait WithFiltering
{
    public array $filters = [];

    #[Locked]
    public ?string $filterLayout = null;

    #[Locked]
    public bool $filtersExpanded = false;

    public function mountWithFiltering(): void
    {
        foreach ($this->filters() as $filter) {
            $default = $filter->getDefault();

            if ($default !== null) {
                $this->filters[$filter->getKey()] = $default;
            }
        }
    }

    public function updatedFilters(): void
    {
        // Editing filters by hand changes the data universe: reset page +
        // "select all matching", and drop any active preset.
        $this->resetDataScope(deactivatePreset: true);

        event(new FilterApplied(static::class, $this->filters, $this->search ?? ''));
    }

    /**
     * Get visible filters, sorted by position.
     *
     * @return Filter[]
     */
    public function resolveFilters(): array
    {
        return collect($this->filters())
            ->reject(fn (Filter $filter) => $filter->isHidden())
            ->sortBy(fn (Filter $filter) => $filter->getPosition() ?? PHP_INT_MAX)
            ->values()
            ->all();
    }

    public function resetFilter(string $key): void
    {
        unset($this->filters[$key]);
    }

    public function resetAllFilters(): void
    {
        $this->filters = [];
        $this->resetDataScope();
    }

    /**
     * Get filters that currently have an active value.
     */
    public function getActiveFilters(): array
    {
        $active = [];

        foreach ($this->filters() as $filter) {
            $value = $this->filters[$filter->getKey()] ?? null;

            if ($filter->hasValue($value)) {
                $active[] = [
                    'key'   => $filter->getKey(),
                    'label' => $filter->getLabel(),
                    'pill'  => $filter->getPillText($value),
                    'value' => $value,
                ];
            }
        }

        return $active;
    }

    public function getActiveFilterCount(): int
    {
        return count($this->getActiveFilters());
    }

    protected function applyFilters(Builder $query): Builder
    {
        foreach ($this->filters() as $filter) {
            $value = $this->filters[$filter->getKey()] ?? null;

            if (! $filter->hasValue($value)) {
                continue;
            }

            $callback = $filter->getCallback();

            if ($callback !== null) {
                $callback($query, $value);
            } else {
                $filter->apply($query, $value);
            }
        }

        return $query;
    }

    public function setFilterLayout(string $layout): static
    {
        $this->filterLayout = $layout;

        return $this;
    }

    public function getFilterLayout(): string
    {
        return $this->filterLayout ?? config('kore-ui.datatable.filter_layout', 'popover');
    }

    public function setFiltersExpanded(bool $expanded = true): static
    {
        $this->filtersExpanded = $expanded;

        return $this;
    }

    public function getFiltersExpanded(): bool
    {
        return $this->filtersExpanded;
    }
}

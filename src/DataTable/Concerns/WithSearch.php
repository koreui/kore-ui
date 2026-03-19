<?php

namespace KoreUi\DataTable\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait WithSearch
{
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();

        // Deactivate preset when user changes search manually
        if (property_exists($this, 'activePreset')) {
            $this->activePreset = null;
        }
    }

    public function getSearchDebounce(): int
    {
        return (int) config('kore-ui.datatable.search_debounce', 300);
    }

    protected function applySearch(Builder $query): Builder
    {
        $term = trim($this->search);

        if ($term === '') {
            return $query;
        }

        $columns = collect($this->columns())->filter(fn ($col) => $col->isSearchable());

        if ($columns->isEmpty()) {
            return $query;
        }

        $query->where(function (Builder $query) use ($columns, $term) {
            foreach ($columns as $column) {
                $callback = $column->getSearchCallback();

                if ($callback !== null) {
                    $query->orWhere(fn (Builder $q) => $callback($q, $term));
                } else {
                    $field = $column->getSearchField();

                    if (str_contains($field, '.')) {
                        [$relation, $relationField] = $this->parseRelationField($field);

                        if (! preg_match('/^[a-zA-Z0-9_.]+$/', $relation)) {
                            continue;
                        }

                        if (! preg_match('/^[a-zA-Z0-9_]+$/', $relationField)) {
                            continue;
                        }

                        $query->orWhereHas($relation, function (Builder $q) use ($relationField, $term) {
                            $q->where($relationField, 'like', "%{$term}%");
                        });
                    } else {
                        if (! preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
                            continue;
                        }

                        $query->orWhere($field, 'like', "%{$term}%");
                    }
                }
            }
        });

        return $query;
    }

    protected function parseRelationField(string $field): array
    {
        $parts = explode('.', $field);
        $column = array_pop($parts);
        $relation = implode('.', $parts);

        return [$relation, $column];
    }
}

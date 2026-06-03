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

        // Escape LIKE wildcards so a literal "%" or "_" is matched as text
        // instead of acting as a wildcard (which forces a full table scan).
        // The ESCAPE character is set explicitly in whereLike() so behaviour is
        // identical across MySQL/PostgreSQL/SQLite. Callbacks get the raw term.
        $pattern = '%' . addcslashes($term, '%_\\') . '%';

        $query->where(function (Builder $query) use ($columns, $term, $pattern) {
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

                        $query->orWhereHas($relation, function (Builder $q) use ($relationField, $pattern) {
                            $this->whereLike($q, $relationField, $pattern);
                        });
                    } else {
                        if (! preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
                            continue;
                        }

                        $this->whereLike($query, $field, $pattern, true);
                    }
                }
            }
        });

        return $query;
    }

    /**
     * Add a LIKE clause with an explicit ESCAPE character so escaped wildcards
     * behave consistently across database drivers. $field must already be
     * validated against /^[a-zA-Z0-9_]+$/ by the caller.
     */
    protected function whereLike(Builder $query, string $field, string $pattern, bool $or = false): void
    {
        $column = $query->getQuery()->getGrammar()->wrap($field);
        $sql    = "{$column} LIKE ? ESCAPE '\\'";

        $or
            ? $query->orWhereRaw($sql, [$pattern])
            : $query->whereRaw($sql, [$pattern]);
    }

    protected function parseRelationField(string $field): array
    {
        $parts = explode('.', $field);
        $column = array_pop($parts);
        $relation = implode('.', $parts);

        return [$relation, $column];
    }
}

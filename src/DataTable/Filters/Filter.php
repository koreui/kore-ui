<?php

namespace KoreUi\DataTable\Filters;

use Illuminate\Database\Eloquent\Builder;

abstract class Filter
{
    use Concerns\IsFilter;

    public function __construct(string $label, string $column)
    {
        $this->label = $label;
        $this->column = $column;
    }

    public static function make(string $label, ?string $column = null): static
    {
        $column = $column ?? str($label)->snake()->toString();

        return new static($label, $column);
    }

    /**
     * Apply this filter to the query.
     */
    abstract public function apply(Builder $query, mixed $value): Builder;

    /**
     * Get the filter type identifier (e.g. 'text', 'select').
     */
    abstract public function getType(): string;

    /**
     * Get props to pass to the Blade component.
     */
    abstract public function getComponentProps(): array;
}

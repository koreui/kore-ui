<?php

namespace KoreUi\DataTable\Columns\Concerns;

use Closure;

trait HasSearch
{
    protected bool $searchable = false;

    protected ?string $searchField = null;

    protected ?Closure $searchCallback = null;

    public function searchable(bool $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    public function searchableAs(string $field): static
    {
        $this->searchable = true;
        $this->searchField = $field;

        return $this;
    }

    public function searchCallback(Closure $callback): static
    {
        $this->searchable = true;
        $this->searchCallback = $callback;

        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function getSearchField(): string
    {
        return $this->searchField ?? $this->field;
    }

    public function getSearchCallback(): ?Closure
    {
        return $this->searchCallback;
    }
}

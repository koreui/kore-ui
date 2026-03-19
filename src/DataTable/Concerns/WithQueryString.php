<?php

namespace KoreUi\DataTable\Concerns;

use Livewire\Attributes\Locked;

trait WithQueryString
{
    #[Locked]
    public bool $queryStringEnabled = false;

    public function setQueryStringEnabled(bool $enabled = true): static
    {
        $this->queryStringEnabled = $enabled;

        return $this;
    }

    public function isQueryStringEnabled(): bool
    {
        return $this->queryStringEnabled;
    }

    public function mountWithQueryString(): void
    {
        if (! $this->queryStringEnabled) {
            $this->queryStringEnabled = (bool) config('kore-ui.datatable.query_string', false);
        }
    }

    /**
     * Livewire evaluates queryString() before mount(), so we read
     * directly from config when property hasn't been set yet.
     */
    public function queryString(): array
    {
        $enabled = $this->queryStringEnabled ?? (bool) config('kore-ui.datatable.query_string', false);

        if (! $enabled) {
            return [];
        }

        return [
            'search'  => ['except' => '', 'as' => 'q'],
            'sorts'   => ['except' => [], 'as' => 'sort'],
            'filters' => ['except' => [], 'as' => 'filter'],
            'perPage' => ['except' => (int) config('kore-ui.datatable.per_page', 25), 'as' => 'per_page'],
        ];
    }
}

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
        // perPage always persists in the URL so it stays consistent with `page`
        // (which Livewire's pagination persists by default). Without this, a
        // reload restored the page but reset perPage, landing on an out-of-range
        // page ("no results"). search/sorts/filters remain opt-in.
        $queryString = [
            'perPage' => ['except' => (int) config('kore-ui.datatable.per_page', 25), 'as' => 'per_page'],
        ];

        $enabled = $this->queryStringEnabled ?? (bool) config('kore-ui.datatable.query_string', false);

        if (! $enabled) {
            return $queryString;
        }

        return array_merge($queryString, [
            'search'  => ['except' => '', 'as' => 'q'],
            'sorts'   => ['except' => [], 'as' => 'sort'],
            'filters' => ['except' => [], 'as' => 'filter'],
        ]);
    }
}

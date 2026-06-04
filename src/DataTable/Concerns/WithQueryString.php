<?php

namespace KoreUi\DataTable\Concerns;

use Livewire\Attributes\Locked;

trait WithQueryString
{
    #[Locked]
    public bool $queryStringEnabled = false;

    /**
     * Per-instance identifier used to namespace URL parameters. When two (or
     * more) DataTables share a page they'd otherwise fight over the same
     * `page`, `per_page`, `q`, `sort` and `filter` query-string keys. Set a
     * unique name (via the `table-name` mount param or by overriding this
     * property in a subclass) and every URL key is prefixed with it
     * (e.g. `users_page`, `users_per_page`). Left empty, keys stay unprefixed
     * for backward-compatible, clean single-table URLs.
     *
     * #[Locked]: a fixed identifier set at mount (or by a subclass), never by the
     * client — it namespaces query-string keys and must not be swappable at runtime.
     */
    #[Locked]
    public string $tableName = '';

    public function setQueryStringEnabled(bool $enabled = true): static
    {
        $this->queryStringEnabled = $enabled;

        return $this;
    }

    /**
     * Sanitized URL-safe prefix derived from $tableName ('' when unset).
     */
    public function tablePrefix(): string
    {
        if ($this->tableName === '') {
            return '';
        }

        return trim(preg_replace('/[^a-z0-9_]+/', '_', strtolower($this->tableName)), '_');
    }

    /**
     * Namespace a URL key with the table prefix. `urlKey('page')` returns
     * `page` for a single table and `users_page` when $tableName is "users".
     */
    public function urlKey(string $base): string
    {
        $prefix = $this->tablePrefix();

        return $prefix === '' ? $base : $prefix . '_' . $base;
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
        //
        // Every alias is namespaced via urlKey() so multiple tables on the same
        // page don't collide (no-op when $tableName is empty).
        $queryString = [
            'perPage' => ['except' => (int) config('kore-ui.datatable.per_page', 25), 'as' => $this->urlKey('per_page')],
        ];

        $enabled = $this->queryStringEnabled ?? (bool) config('kore-ui.datatable.query_string', false);

        if (! $enabled) {
            return $queryString;
        }

        return array_merge($queryString, [
            'search'  => ['except' => '', 'as' => $this->urlKey('q')],
            'sorts'   => ['except' => [], 'as' => $this->urlKey('sort')],
            'filters' => ['except' => [], 'as' => $this->urlKey('filter')],
        ]);
    }
}

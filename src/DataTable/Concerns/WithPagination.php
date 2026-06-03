<?php

namespace KoreUi\DataTable\Concerns;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

trait WithPagination
{
    protected ?string $paginationType = null;

    public function updatedPerPage(): void
    {
        $this->normalizePerPage();
        $this->resetPage();
    }

    /**
     * Coerce perPage to an allowed value. Needed both when the user changes the
     * select and when perPage is restored from the URL (?per_page=), where an
     * arbitrary/out-of-range value could otherwise load far too many rows.
     */
    public function normalizePerPage(): void
    {
        $options = $this->getPerPageOptions();

        if (empty($options)) {
            $this->perPage = 25;
        } elseif (! in_array($this->perPage, $options, true)) {
            $this->perPage = $options[0];
        }
    }

    public function getPerPageOptions(): array
    {
        return config('kore-ui.datatable.per_page_options', [10, 25, 50, 100]);
    }

    public function getPaginationType(): string
    {
        return $this->paginationType ?? config('kore-ui.datatable.pagination_type', 'standard');
    }

    public function setPaginationType(string $type): static
    {
        $this->paginationType = $type;

        return $this;
    }

    protected function applyPagination(Builder $query): LengthAwarePaginator|Paginator|CursorPaginator
    {
        return match ($this->getPaginationType()) {
            'simple' => $query->simplePaginate($this->perPage),
            'cursor' => $query->cursorPaginate($this->perPage),
            default  => $query->paginate($this->perPage),
        };
    }
}

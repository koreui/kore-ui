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
        if (!in_array($this->perPage, $this->getPerPageOptions())) {
            $this->perPage = $this->getPerPageOptions()[0];
        }

        $this->resetPage();
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

<?php

namespace KoreUi\DataTable\Concerns;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination as LivewireWithPagination;

trait WithPagination
{
    // Pull in Livewire's pagination but alias its page methods so we can wrap
    // them with a per-table page name. Keeping the trait in `class_uses_recursive`
    // is required: Livewire's SupportPagination hook is skipped without it.
    use LivewireWithPagination {
        setPage as protected livewireSetPage;
        resetPage as protected livewireResetPage;
        getPage as protected livewireGetPage;
        nextPage as protected livewireNextPage;
        previousPage as protected livewirePreviousPage;
        gotoPage as protected livewireGotoPage;
    }

    protected ?string $paginationType = null;

    /**
     * The page query-string key for this table. Prefixed with $tableName so two
     * tables on the same page paginate independently (`page` vs `users_page`).
     */
    public function pageName(): string
    {
        return $this->urlKey('page');
    }

    // The overrides below simply default $pageName to this table's prefixed name
    // and delegate to Livewire's original implementation. This keeps every call
    // site unchanged: the pagination view (`wire:click="nextPage"`) and internal
    // resetPage()/setPage() calls all resolve to the correct prefixed page key.

    public function setPage($page, $pageName = null): void
    {
        $this->livewireSetPage($page, $pageName ?? $this->pageName());
    }

    public function resetPage($pageName = null): void
    {
        $this->livewireResetPage($pageName ?? $this->pageName());
    }

    public function getPage($pageName = null)
    {
        return $this->livewireGetPage($pageName ?? $this->pageName());
    }

    public function nextPage($pageName = null): void
    {
        $this->livewireNextPage($pageName ?? $this->pageName());
    }

    public function previousPage($pageName = null): void
    {
        $this->livewirePreviousPage($pageName ?? $this->pageName());
    }

    public function gotoPage($page, $pageName = null): void
    {
        $this->livewireGotoPage($page, $pageName ?? $this->pageName());
    }

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
        $pageName = $this->pageName();

        return match ($this->getPaginationType()) {
            'simple' => $query->simplePaginate($this->perPage, ['*'], $pageName),
            'cursor' => $query->cursorPaginate($this->perPage, ['*'], $pageName),
            default  => $query->paginate($this->perPage, ['*'], $pageName),
        };
    }
}

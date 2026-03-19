<?php

namespace KoreUi\DataTable;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use Livewire\Component;
use Livewire\WithPagination as LivewireWithPagination;

abstract class KoreDataTable extends Component
{
    use LivewireWithPagination;
    use Concerns\WithSorting;
    use Concerns\WithSearch;
    use Concerns\WithPagination;
    use Concerns\WithFiltering;
    use Concerns\WithSelection;
    use Concerns\WithBulkActions;

    public int $perPage = 25;

    protected string $density = 'normal';

    protected ?string $emptyText = null;

    protected ?string $emptyIcon = null;

    /**
     * Return the base query for the DataTable.
     */
    abstract public function query(): Builder;

    /**
     * Define the columns for the DataTable.
     *
     * @return Column[]
     */
    abstract public function columns(): array;

    /**
     * Define the filters for the DataTable.
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Define bulk actions for the DataTable.
     */
    public function bulkActions(): array
    {
        return [];
    }

    /**
     * Override in subclass to customize configuration.
     */
    public function configure(): void
    {
        //
    }

    public function mount(): void
    {
        $this->perPage = (int) config('kore-ui.datatable.per_page', 25);
        $this->density = config('kore-ui.datatable.density', 'normal');
        $this->configure();
    }

    public function getRows()
    {
        $query = $this->query();
        $query = $this->applySearch($query);
        $query = $this->applyFilters($query);
        $query = $this->applySorts($query);
        $query = $this->applyEagerLoading($query);

        return $this->applyPagination($query);
    }

    /**
     * Get visible columns (filtered by hidden state).
     *
     * @return Column[]
     */
    public function resolveColumns(): array
    {
        return collect($this->columns())
            ->reject(fn (Column $column) => $column->isHidden())
            ->values()
            ->all();
    }

    public function getDensity(): string
    {
        return $this->density;
    }

    public function getEmptyText(): string
    {
        return $this->emptyText ?? config('kore-ui.datatable.empty_text', 'No se encontraron resultados');
    }

    public function getEmptyIcon(): string
    {
        return $this->emptyIcon ?? config('kore-ui.datatable.empty_icon', 'inbox');
    }

    public function getShowingText($paginator): ?string
    {
        if (! method_exists($paginator, 'total')) {
            return null;
        }

        $template = config('kore-ui.datatable.translations.showing', 'Mostrando :from a :to de :total resultados');

        return strtr($template, [
            ':from'  => $paginator->firstItem() ?? 0,
            ':to'    => $paginator->lastItem() ?? 0,
            ':total' => $paginator->total(),
        ]);
    }

    /**
     * Detect relations from dot-notation fields and eager load them.
     */
    protected function applyEagerLoading(Builder $query): Builder
    {
        $relations = collect($this->columns())
            ->map(fn (Column $col) => $col->getField())
            ->filter(fn (string $field) => str_contains($field, '.'))
            ->map(function (string $field) {
                $parts = explode('.', $field);
                array_pop($parts);

                return implode('.', $parts);
            })
            ->unique()
            ->values()
            ->all();

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query;
    }

    public function render()
    {
        $rows = $this->getRows();
        $columns = $this->resolveColumns();
        $selectionEnabled = $this->isSelectionEnabled();
        $rowIds = $selectionEnabled ? $this->getRowIds($rows) : [];

        // Keep Alpine rowIds in sync (x-data is not re-evaluated during morph)
        if ($selectionEnabled) {
            $this->dispatch('kore:datatable-rows-updated', rowIds: $rowIds);
        }

        return view('kore::datatable.datatable', [
            'rows'             => $rows,
            'columns'          => $columns,
            'density'          => $this->getDensity(),
            'emptyText'        => $this->getEmptyText(),
            'emptyIcon'        => $this->getEmptyIcon(),
            'showingText'      => $this->getShowingText($rows),
            'searchDebounce'   => $this->getSearchDebounce(),
            'perPageOptions'   => $this->getPerPageOptions(),
            'translations'     => config('kore-ui.datatable.translations', []),
            'filterDefs'       => $this->resolveFilters(),
            'activeFilters'    => $this->getActiveFilters(),
            'filterCount'      => $this->getActiveFilterCount(),
            'filterLayout'     => $this->getFilterLayout(),
            'filtersExpanded'  => $this->getFiltersExpanded(),
            'bulkActions'      => $this->resolveBulkActions(),
            'selectionEnabled' => $selectionEnabled,
            'primaryKey'       => $this->getPrimaryKey(),
            'rowIds'           => $rowIds,
        ]);
    }
}

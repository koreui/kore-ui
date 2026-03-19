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
    use Concerns\WithColumnSelect;
    use Concerns\WithResponsive;
    use Concerns\WithQueryString;
    use Concerns\WithFilterPresets;
    use Concerns\WithExport;
    use Concerns\WithInlineEditing;
    use Concerns\WithDeferredLoading;

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
        $this->deferredLoading = (bool) config('kore-ui.datatable.deferred_loading', false);
        $this->mountWithColumnSelect();
        $this->mountWithResponsive();
        $this->configure();
        $this->mountWithQueryString();
        $this->mountWithFilterPresets();

        // Deferred loading: mark data as loaded if not deferred
        if (! $this->deferredLoading) {
            $this->dataLoaded = true;
        }
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
            ->reject(fn (Column $column) => $this->isColumnDeselected($column->getField()))
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
     * Check if any column has aggregation defined.
     */
    public function hasAnyAggregation(): bool
    {
        return collect($this->columns())->contains(fn (Column $col) => $col->hasAggregation());
    }

    /**
     * Compute aggregation values for columns that have them.
     * Runs on the full filtered dataset (not paginated).
     */
    public function getAggregations(array $columns): array
    {
        $aggregations = [];

        foreach ($columns as $column) {
            if (! $column->hasAggregation()) {
                continue;
            }

            $field = $column->getField();

            // Custom footer callback
            if ($column->getFooterCallback() !== null) {
                $query = $this->query();
                $query = $this->applySearch($query);
                $query = $this->applyFilters($query);

                $aggregations[$field] = [
                    'value' => ($column->getFooterCallback())($query),
                    'label' => $column->getFooterLabel(),
                ];

                continue;
            }

            $type = $column->getAggregationType();

            $query = $this->query();
            $query = $this->applySearch($query);
            $query = $this->applyFilters($query);

            $value = match ($type) {
                'sum'   => $query->sum($field),
                'avg'   => round((float) $query->avg($field), $column->getAggregationDecimals()),
                'count' => $query->count(),
                'min'   => $query->min($field),
                'max'   => $query->max($field),
                default => null,
            };

            $aggregations[$field] = [
                'value' => $column->formatAggregationValue($value),
                'label' => $column->getFooterLabel(),
            ];
        }

        return $aggregations;
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
        $columns = $this->resolveColumns();

        // Deferred loading: pass null rows until data is loaded
        if ($this->isDeferredLoading() && ! $this->isDataLoaded()) {
            $rows = null;
        } else {
            $rows = $this->getRows();
        }

        $selectionEnabled = $this->isSelectionEnabled();
        $rowIds = [];

        if ($selectionEnabled && $rows !== null) {
            $rowIds = $this->getRowIds($rows);
        }

        // Keep Alpine rowIds in sync (x-data is not re-evaluated during morph)
        if ($selectionEnabled) {
            $this->dispatch('kore:datatable-rows-updated', rowIds: $rowIds);
        }

        $columnSelectEnabled = $this->isColumnSelectEnabled();
        $allColumns = $columnSelectEnabled ? $this->getSelectableColumns() : [];

        return view('kore::datatable.datatable', [
            'rows'                => $rows,
            'columns'             => $columns,
            'density'             => $this->getDensity(),
            'emptyText'           => $this->getEmptyText(),
            'emptyIcon'           => $this->getEmptyIcon(),
            'showingText'         => $rows !== null && method_exists($rows, 'total') ? $this->getShowingText($rows) : null,
            'searchDebounce'      => $this->getSearchDebounce(),
            'perPageOptions'      => $this->getPerPageOptions(),
            'translations'        => config('kore-ui.datatable.translations', []),
            'filterDefs'          => $this->resolveFilters(),
            'activeFilters'       => $this->getActiveFilters(),
            'filterCount'         => $this->getActiveFilterCount(),
            'filterLayout'        => $this->getFilterLayout(),
            'filtersExpanded'     => $this->getFiltersExpanded(),
            'bulkActions'         => $this->resolveBulkActions(),
            'selectionEnabled'    => $selectionEnabled,
            'primaryKey'          => $this->getPrimaryKey(),
            'rowIds'              => $rowIds,
            'columnSelectEnabled' => $columnSelectEnabled,
            'allColumns'          => $allColumns,
            'deselectedColumns'   => $this->deselectedColumns,
            'responsiveMode'      => $this->getResponsiveMode(),
            'responsiveBreakpoint' => $this->getResponsiveBreakpoint(),
            'collapsedColumns'    => $this->getCollapsedColumns(),
            // Phase 4
            'aggregations'        => $rows !== null && $this->hasAnyAggregation() ? $this->getAggregations($columns) : [],
            'activeSorts'         => $this->getActiveSorts(),
            'presets'             => $this->resolveFilterPresets(),
            'activePreset'        => $this->activePreset,
            'presetCounts'        => ! empty($this->filterPresets()) ? $this->getPresetCounts() : [],
            'exportEnabled'       => $this->isExportEnabled(),
            'exportFormats'       => $this->getExportFormats(),
            'editableColumns'     => $this->getEditableColumnsMap(),
            'hasEditing'          => $this->hasEditableColumns(),
            // Phase 5
            'deferredLoading'     => $this->isDeferredLoading(),
            'dataLoaded'          => $this->isDataLoaded(),
        ]);
    }
}

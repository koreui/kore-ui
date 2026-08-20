<?php

namespace KoreUi\DataTable;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use Livewire\Attributes\Locked;
use Livewire\Component;

abstract class KoreDataTable extends Component
{
    // Livewire's WithPagination is pulled in (and wrapped) by Concerns\WithPagination
    // so pagination page keys can be namespaced per table; see that trait.
    use Concerns\WithSorting;
    use Concerns\WithSearch;
    use Concerns\WithPagination;
    use Concerns\WithFiltering;
    use Concerns\WithSelection;
    use Concerns\WithBulkActions;
    use Concerns\WithColumnSelect;
    use Concerns\WithColumnMenu;
    use Concerns\WithResponsive;
    use Concerns\WithQueryString;
    use Concerns\WithFilterPresets;
    use Concerns\WithExport;
    use Concerns\WithInlineEditing;
    use Concerns\WithDeferredLoading;
    use Concerns\WithSavedViews;

    public int $perPage = 25;

    protected string $density = 'normal';

    /**
     * Max height (px) for the scrollable table region. When set, the table
     * scrolls internally and the header becomes sticky within it (a sticky
     * header cannot work while the wrapper relies on page scroll + overflow-x).
     */
    protected ?int $maxHeight = null;

    /**
     * Reparto de anchos de la tabla: 'auto' (por defecto) o 'fixed'.
     *
     * Con 'auto' el navegador reparte según el contenido y `Column::width()` es
     * una sugerencia que se ignora en cuanto otra columna necesita sitio — el
     * síntoma es una columna estrecha con el texto apilado palabra a palabra.
     * Con 'fixed' los anchos declarados se respetan y el resto se reparte a
     * partes iguales.
     */
    protected string $tableLayout = 'auto';

    protected ?string $emptyText = null;

    protected ?string $emptyIcon = null;

    #[Locked]
    public array $tableSlots = [];

    /**
     * Cachés por request de los cuatro métodos que definen la tabla.
     *
     * `columns()` se invoca trece veces desde el propio módulo, más las de
     * Blade, y cada llamada reconstruye todos los objetos Column con sus
     * closures. Con `filters()` el coste no es solo de objetos: el patrón normal
     * es `SelectFilter::options(Ciudad::pluck(...))`, así que cuatro llamadas
     * son cuatro consultas por render.
     *
     * Son `protected`, así que Livewire no las serializa: el caché dura lo que
     * dura la petición y la siguiente vuelve a preguntar. Es justo lo que
     * queremos — dentro de un render, la definición de la tabla no cambia.
     */
    protected ?array $columnCache = null;

    protected ?array $filterCache = null;

    protected ?array $bulkActionCache = null;

    protected ?array $presetCache = null;

    protected function cachedColumns(): array
    {
        return $this->columnCache ??= $this->columns();
    }

    protected function cachedFilters(): array
    {
        return $this->filterCache ??= $this->filters();
    }

    protected function cachedBulkActions(): array
    {
        return $this->bulkActionCache ??= $this->bulkActions();
    }

    protected function cachedFilterPresets(): array
    {
        return $this->presetCache ??= $this->filterPresets();
    }

    /**
     * Descarta los cachés. Necesario si una acción cambia algo de lo que
     * dependen las definiciones dentro del mismo request.
     */
    public function flushDefinitionCache(): void
    {
        $this->columnCache     = null;
        $this->filterCache     = null;
        $this->bulkActionCache = null;
        $this->presetCache     = null;
    }

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

    /**
     * Inject a Blade view into a predefined slot area of the DataTable layout.
     *
     * Available areas: before-toolbar, after-toolbar, toolbar-right-end, after-table.
     *
     * The injected view receives $component (the DataTable instance) plus any $params.
     */
    public function setSlot(string $area, string $view, array $params = []): static
    {
        $this->tableSlots[$area] = compact('view', 'params');

        return $this;
    }

    public function getTableSlots(): array
    {
        return $this->tableSlots;
    }

    /**
     * Configuración de la tabla, en CADA petición.
     *
     * Vive en booted() y no en mount() por una razón que costaba cara: las
     * propiedades de configuración (`density`, `responsiveMode`, `primaryKey`,
     * `exportEnabled`…) son `protected`, así que Livewire no las serializa. Con
     * `configure()` corriendo solo en el montaje, la tabla perdía su densidad,
     * su modo responsive, su clave primaria y hasta el export en cuanto el
     * usuario paginaba, buscaba o filtraba: todo volvía a los valores por
     * defecto de la clase. Reaplicarlo aquí es más barato que serializar
     * diecisiete propiedades en cada snapshot, y deja `configure()` como la
     * única fuente de verdad de la configuración.
     *
     * Los traits NO usan hooks `mount{Trait}`: Livewire los invoca por su cuenta,
     * así que un método con ese nombre se ejecutaría dos veces y en un orden que
     * no controlamos respecto a `configure()`. Se llaman aquí, en secuencia.
     *
     * Si una tabla necesita su propio `booted()`, que llame a `parent::booted()`.
     */
    public function booted(): void
    {
        // 1 · Valores por defecto de la configuración global.
        $this->density         = config('kore-ui.datatable.density', 'normal');
        $this->tableLayout     = config('kore-ui.datatable.table_layout', 'auto') === 'fixed' ? 'fixed' : 'auto';
        $this->deferredLoading = (bool) config('kore-ui.datatable.deferred_loading', false);
        $this->applyColumnSelectConfig();
        $this->applyColumnMenuConfig();
        $this->applyResponsiveConfig();
        $this->applyExportConfig();
        $this->applyQueryStringConfig();
        $this->applySavedViewsConfig();

        // 2 · La tabla manda sobre la configuración global.
        $this->configure();

        // 3 · Sin carga diferida, los datos están listos desde el primer render.
        //     Idempotente: si ya estaba marcado, no cambia nada.
        if (! $this->deferredLoading) {
            $this->dataLoaded = true;
        }
    }

    /**
     * Estado inicial, una sola vez.
     *
     * Livewire ejecuta boot → mount → booted, así que aquí `configure()` todavía
     * no ha corrido: solo va lo que no depende de él.
     */
    public function mount(): void
    {
        // Se respeta un perPage que venga de la URL (?per_page= /
        // ?{tabla}_per_page=) y solo se cae al valor de config cuando no está
        // presente: si no, se pisaría lo que BaseUrl acaba de restaurar y la
        // tabla ignoraría la URL al recargar.
        if (! request()->filled($this->urlKey('per_page'))) {
            $this->perPage = (int) config('kore-ui.datatable.per_page', 25);
        }

        // Un perPage venido de la URL no es de fiar: se acota a las opciones
        // permitidas para que ?per_page=999999 no cargue la tabla entera.
        $this->normalizePerPage();

        // El preset por defecto va después de los defaults de filtro porque su
        // trabajo es precisamente imponer un estado completo.
        $this->applyFilterDefaults();
        $this->applyDefaultPreset();
    }

    public function getRows()
    {
        $rows = $this->applyPagination($this->buildRowsQuery());

        // Clamp: if the requested page is past the last one (perPage grew,
        // filters shrank the set, rows were deleted, or a stale ?page= from the
        // URL) jump to the last valid page instead of an empty "no results"
        // screen. Only length-aware paginators expose total()/lastPage().
        if (method_exists($rows, 'lastPage')
            && $rows->total() > 0
            && $rows->currentPage() > $rows->lastPage()
        ) {
            $this->setPage($rows->lastPage());
            $rows = $this->applyPagination($this->buildRowsQuery());
        }

        return $rows;
    }

    protected function buildRowsQuery(): Builder
    {
        return $this->applyEagerLoading(
            $this->applySorts($this->baseFilteredQuery())
        );
    }

    /**
     * Base query with search + active filters applied, but WITHOUT sorting,
     * eager loading or pagination. This is the single definition of "the
     * current filtered set", shared by the rows, aggregations, export and
     * "select all matching" so they can never drift out of sync.
     */
    protected function baseFilteredQuery(): Builder
    {
        return $this->applyFilters($this->applySearch($this->query()));
    }

    /**
     * Reset pagination/selection after the data universe changes. Always jumps
     * back to page 1 and drops "select all matching" (its scope just changed).
     * When $deactivatePreset is true it also clears the active preset — used
     * when the user edits search/filters by hand; the preset-driven flows
     * (applyPreset/clearPreset) manage $activePreset themselves and pass false.
     *
     * The property_exists guards keep this safe if a table opts out of the
     * selection/preset traits.
     */
    protected function resetDataScope(bool $deactivatePreset = false): void
    {
        $this->resetPage();

        if (property_exists($this, 'selectAllMatching')) {
            $this->selectAllMatching = false;
        }

        if ($deactivatePreset && property_exists($this, 'activePreset')) {
            $this->activePreset = null;
        }

        // Una vista guardada describe un estado completo. En cuanto el usuario
        // edita los filtros a mano deja de describir lo que se está viendo, y
        // si siguiera marcada como activa, volver a pulsarla la interpretaría
        // como "salir de la vista" en vez de restaurarla — justo lo contrario
        // de lo que espera quien la creó.
        if ($deactivatePreset && property_exists($this, 'activeSavedView')) {
            $this->activeSavedView = null;
        }
    }

    /**
     * Get visible columns (filtered by hidden state).
     *
     * @return Column[]
     */
    public function resolveColumns(): array
    {
        return $this->applyColumnPins(
            collect($this->cachedColumns())
                ->reject(fn (Column $column) => $column->isHidden())
                ->reject(fn (Column $column) => $this->isColumnDeselected($column->getField()))
                ->values()
                ->all()
        );
    }

    public function setTableLayout(string $layout): static
    {
        $this->tableLayout = $layout === 'fixed' ? 'fixed' : 'auto';

        return $this;
    }

    public function getTableLayout(): string
    {
        return $this->tableLayout;
    }

    public function getDensity(): string
    {
        return $this->density;
    }

    public function getMaxHeight(): ?int
    {
        return $this->maxHeight;
    }

    public function setMaxHeight(?int $px): static
    {
        $this->maxHeight = $px;

        return $this;
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
        return collect($this->cachedColumns())->contains(fn (Column $col) => $col->hasAggregation());
    }

    /**
     * Compute aggregation values for columns that have them.
     * Runs on the full filtered dataset (not paginated).
     * Standard aggregations are batched into a single SQL query to avoid N+1.
     */
    public function getAggregations(array $columns): array
    {
        $aggregations = [];
        $standardColumns = [];

        foreach ($columns as $column) {
            if (! $column->hasAggregation()) {
                continue;
            }

            // Custom callback: needs its own query (arbitrary logic)
            if ($column->getFooterCallback() !== null) {
                $aggregations[$column->getField()] = [
                    'value' => ($column->getFooterCallback())($this->baseFilteredQuery()),
                    'label' => $column->getFooterLabel(),
                ];

                continue;
            }

            $standardColumns[] = $column;
        }

        // Batch: 1 query for all standard aggregations
        if (! empty($standardColumns)) {
            $aggregations = array_merge($aggregations, $this->batchStandardAggregations($standardColumns));
        }

        return $aggregations;
    }

    /**
     * Consolidates all standard (non-callback) aggregations into a single SQL query.
     */
    private function batchStandardAggregations(array $columns): array
    {
        $baseQuery = $this->baseFilteredQuery();

        $selects = [];

        foreach ($columns as $index => $column) {
            $field = $column->getField();
            $alias = 'kore_agg_' . $index;

            // Validate + quote the column so relations/reserved words/unexpected
            // values can't break or inject into the raw aggregate expression.
            $wrapped = preg_match('/^[a-zA-Z0-9_]+$/', $field)
                ? $baseQuery->getQuery()->getGrammar()->wrap($field)
                : null;

            $selects[] = match ($column->getAggregationType()) {
                'sum'   => $wrapped ? "SUM({$wrapped}) as {$alias}" : "NULL as {$alias}",
                'avg'   => $wrapped ? "AVG({$wrapped}) as {$alias}" : "NULL as {$alias}",
                'count' => "COUNT(*) as {$alias}",
                'min'   => $wrapped ? "MIN({$wrapped}) as {$alias}" : "NULL as {$alias}",
                'max'   => $wrapped ? "MAX({$wrapped}) as {$alias}" : "NULL as {$alias}",
                default => "NULL as {$alias}",
            };
        }

        // select([]) clears previous columns; selectRaw adds only the aggregates
        $result = $baseQuery->select([])->selectRaw(implode(', ', $selects))->first();

        $aggregations = [];

        foreach ($columns as $index => $column) {
            $alias = 'kore_agg_' . $index;
            $raw = $result?->{$alias};

            if ($column->getAggregationType() === 'avg') {
                // Preserve null (empty dataset) instead of coercing to 0.0.
                $raw = $raw !== null ? round((float) $raw, $column->getAggregationDecimals()) : null;
            }

            $aggregations[$column->getField()] = [
                'value' => $column->formatAggregationValue($raw),
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
        $relations = collect($this->cachedColumns())
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

        // Se publica en una propiedad para que los triggers de filtro que viven
        // dentro de un wire:ignore puedan leerla desde $wire (ver WithFiltering).
        $this->filterCount = $this->getActiveFilterCount();

        // Livewire vuelca las propiedades públicas en el scope de la vista y
        // ganan sobre los datos que se pasan aquí. `$filterLayout` es pública y
        // vale null hasta que alguien llame a setFilterLayout(), así que el
        // valor resuelto (con su fallback a config) nunca llegaba al Blade: la
        // opción `filter_layout` de la configuración no se aplicaba nunca.
        // Igualarlas evita que importe cuál de las dos gane.
        $this->filterLayout = $this->getFilterLayout();

        // Deferred loading: pass null rows until data is loaded
        if ($this->isDeferredLoading() && ! $this->isDataLoaded()) {
            $rows = null;
        } else {
            $rows = $this->getRows();
        }

        $selectionEnabled = $this->isSelectionEnabled();

        // Los IDs de la página se calculan SIEMPRE, no solo cuando hay selección:
        // Alpine los necesita para la navegación por teclado, que es una función
        // aparte. Atarlos a isSelectionEnabled() dejaba las flechas muertas en
        // cualquier tabla sin bulk actions.
        $rowIds = $rows !== null ? $this->getRowIds($rows) : [];
        $total  = ($rows !== null && method_exists($rows, 'total')) ? $rows->total() : count($rowIds);

        // Mantiene sincronizados los rowIds de Alpine para el teclado y el rango
        // con shift (x-data no se reevalúa durante el morph). El estado de
        // selección en sí vive en el servidor y viaja en el snapshot.
        $this->dispatch('kore:datatable-rows-updated', rowIds: $rowIds, total: $total);

        $columnSelectEnabled = $this->isColumnSelectEnabled();
        $allColumns = $columnSelectEnabled ? $this->getSelectableColumns() : [];

        return view('kore::datatable.datatable', [
            'rows'                => $rows,
            'columns'             => $columns,
            'density'             => $this->getDensity(),
            'tableLayout'         => $this->getTableLayout(),
            'maxHeight'           => $this->getMaxHeight(),
            'emptyText'           => $this->getEmptyText(),
            'emptyIcon'           => $this->getEmptyIcon(),
            'showingText'         => $rows !== null && method_exists($rows, 'total') ? $this->getShowingText($rows) : null,
            'searchDebounce'      => $this->getSearchDebounce(),
            'perPageOptions'      => $this->getPerPageChoices(),
            'translations'        => config('kore-ui.datatable.translations', []),
            'filterDefs'          => $this->resolveFilters(),
            'activeFilters'       => $this->getActiveFilters(),
            'filterCount'         => $this->filterCount,
            'filterLayout'        => $this->getFilterLayout(),
            'filtersExpanded'     => $this->getFiltersExpanded(),
            'bulkActions'         => $this->resolveBulkActions(),
            'selectionEnabled'    => $selectionEnabled,
            'primaryKey'          => $this->getPrimaryKey(),
            'rowIds'              => $rowIds,
            'total'               => $total,
            'columnSelectEnabled' => $columnSelectEnabled,
            'columnMenuEnabled'   => $this->isColumnMenuEnabled(),
            'savedViewsEnabled'   => $this->isSavedViewsEnabled(),
            'savedViews'          => $this->getSavedViews(),
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
            'presetCounts'        => ! empty($this->cachedFilterPresets()) ? $this->getPresetCounts() : [],
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

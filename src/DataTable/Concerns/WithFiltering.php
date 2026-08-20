<?php

namespace KoreUi\DataTable\Concerns;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Events\FilterApplied;
use KoreUi\DataTable\Filters\Filter;
use Livewire\Attributes\Locked;

trait WithFiltering
{
    public array $filters = [];

    #[Locked]
    public ?string $filterLayout = null;

    #[Locked]
    public bool $filtersExpanded = false;

    /**
     * Número de filtros con valor. Es una propiedad pública, y no solo una
     * variable de vista, porque los layouts `popover` y `drawer` envuelven su
     * trigger en un `wire:ignore`: ese DOM no se morfea, así que un valor
     * impreso por Blade se quedaría congelado en el conteo del primer render.
     * Leído desde `$wire`, en cambio, se actualiza en cada respuesta.
     *
     * #[Locked]: lo calcula el servidor en cada render; el cliente solo lee.
     */
    #[Locked]
    public int $filterCount = 0;

    protected function applyFilterDefaults(): void
    {
        foreach ($this->cachedFilters() as $filter) {
            $default = $filter->getDefault();

            if ($default !== null) {
                $this->filters[$filter->getKey()] = $default;
            }
        }
    }

    public function updatedFilters(): void
    {
        // Editing filters by hand changes the data universe: reset page +
        // "select all matching", and drop any active preset.
        $this->resetDataScope(deactivatePreset: true);

        event(new FilterApplied(static::class, $this->filters, $this->search ?? ''));
    }

    /**
     * Get visible filters, sorted by position.
     *
     * @return Filter[]
     */
    public function resolveFilters(): array
    {
        return collect($this->cachedFilters())
            ->reject(fn (Filter $filter) => $filter->isHidden())
            ->sortBy(fn (Filter $filter) => $filter->getPosition() ?? PHP_INT_MAX)
            ->values()
            ->all();
    }

    /**
     * Quitar un filtro cambia el universo de datos igual que editarlo: hay que
     * volver a la página 1, soltar "seleccionar todo lo que coincide" (su
     * alcance acaba de cambiar) y desactivar el preset, que ya no describe lo
     * que se está viendo. Los tres caminos —editar, quitar uno, quitarlos
     * todos— comparten ahora la misma semántica.
     */
    public function resetFilter(string $key): void
    {
        unset($this->filters[$key]);

        $this->resetDataScope(deactivatePreset: true);
    }

    public function resetAllFilters(): void
    {
        $this->filters = [];

        $this->resetDataScope(deactivatePreset: true);
    }

    /**
     * Get filters that currently have an active value.
     */
    /**
     * Valor saneado de un filtro, tal y como llegará a la consulta.
     *
     * Todo lo que lee `$this->filters` debe pasar por aquí: las pills, el
     * contador y `applyFilters()` tienen que coincidir, o la UI acabaría
     * anunciando un filtro que la consulta no aplica.
     */
    protected function filterValue(Filter $filter): mixed
    {
        return $filter->sanitize($this->filters[$filter->getKey()] ?? null);
    }

    public function getActiveFilters(): array
    {
        $active = [];

        foreach ($this->cachedFilters() as $filter) {
            $value = $this->filterValue($filter);

            if ($filter->hasValue($value)) {
                $active[] = [
                    'key'   => $filter->getKey(),
                    'label' => $filter->getLabel(),
                    'pill'  => $filter->getPillText($value),
                    'value' => $value,
                ];
            }
        }

        return $active;
    }

    public function getActiveFilterCount(): int
    {
        return count($this->getActiveFilters());
    }

    protected function applyFilters(Builder $query): Builder
    {
        foreach ($this->cachedFilters() as $filter) {
            $value = $this->filterValue($filter);

            if (! $filter->hasValue($value)) {
                continue;
            }

            $callback = $filter->getCallback();

            if ($callback !== null) {
                $callback($query, $value);
            } else {
                $filter->apply($query, $value);
            }
        }

        return $query;
    }

    public function setFilterLayout(string $layout): static
    {
        $this->filterLayout = $layout;

        return $this;
    }

    public function getFilterLayout(): string
    {
        return $this->filterLayout ?? config('kore-ui.datatable.filter_layout', 'popover');
    }

    public function setFiltersExpanded(bool $expanded = true): static
    {
        $this->filtersExpanded = $expanded;

        return $this;
    }

    public function getFiltersExpanded(): bool
    {
        return $this->filtersExpanded;
    }
}

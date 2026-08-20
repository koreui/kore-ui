<?php

namespace KoreUi\DataTable\Concerns;

use KoreUi\DataTable\Views\Contracts\SavedViewStore;
use KoreUi\DataTable\Views\SavedView;
use Livewire\Attributes\Locked;

/**
 * Vistas guardadas por el usuario.
 *
 * Un `FilterPreset` lo declara quien escribe la tabla y es fijo. Una vista la
 * crea quien la usa: guarda la combinación de filtros, orden, búsqueda,
 * columnas visibles y columnas fijadas con la que está trabajando, y vuelve a
 * ella cuando quiere.
 */
trait WithSavedViews
{
    public ?string $activeSavedView = null;

    /**
     * Nombre que se está escribiendo en el diálogo de guardar. Es entrada de
     * usuario, así que NO va bloqueada.
     */
    public string $savedViewName = '';

    #[Locked]
    public bool $savedViewsEnabled = false;

    protected function applySavedViewsConfig(): void
    {
        $this->savedViewsEnabled = (bool) config('kore-ui.datatable.saved_views', false);
    }

    public function setSavedViewsEnabled(bool $enabled = true): static
    {
        $this->savedViewsEnabled = $enabled;

        return $this;
    }

    public function isSavedViewsEnabled(): bool
    {
        return $this->savedViewsEnabled;
    }

    /**
     * Identifica esta tabla en el almacén. Incluye el nombre de instancia para
     * que dos tablas de la misma clase en una página no compartan vistas.
     */
    public function savedViewsKey(): string
    {
        $prefix = method_exists($this, 'tablePrefix') ? $this->tablePrefix() : '';

        return static::class . ($prefix === '' ? '' : ':' . $prefix);
    }

    protected function savedViewStore(): SavedViewStore
    {
        return app(SavedViewStore::class);
    }

    /**
     * @return SavedView[]
     */
    public function getSavedViews(): array
    {
        if (! $this->savedViewsEnabled) {
            return [];
        }

        return $this->savedViewStore()->all($this->savedViewsKey());
    }

    /**
     * Guarda el estado actual con un nombre.
     */
    public function saveCurrentView(): void
    {
        $name = trim($this->savedViewName);

        if (! $this->savedViewsEnabled || $name === '') {
            return;
        }

        // Se recorta porque es texto libre que acaba en un almacén y en un
        // botón; 60 caracteres caben en ambos sitios.
        $name = mb_substr($name, 0, 60);

        $view = new SavedView(
            id: (string) str()->uuid(),
            name: $name,
            filters: $this->filters,
            sorts: $this->sorts,
            search: $this->search,
            perPage: $this->perPage,
            deselectedColumns: property_exists($this, 'deselectedColumns') ? $this->deselectedColumns : [],
            columnPins: property_exists($this, 'columnPins') ? $this->columnPins : [],
        );

        $this->savedViewStore()->save($this->savedViewsKey(), $view);

        $this->activeSavedView = $view->id;
        $this->savedViewName = '';
    }

    /**
     * Restaura una vista. Si ya estaba activa, la suelta: mismo gesto de
     * alternar que los presets.
     */
    public function applySavedView(string $id): void
    {
        if (! $this->savedViewsEnabled) {
            return;
        }

        if ($this->activeSavedView === $id) {
            $this->clearSavedView();

            return;
        }

        $view = $this->savedViewStore()->find($this->savedViewsKey(), $id);

        if (! $view) {
            return;
        }

        $this->filters = $view->filters;
        $this->sorts   = $view->sorts;
        $this->search  = $view->search;

        if ($view->perPage !== null) {
            $this->perPage = $view->perPage;
            $this->normalizePerPage();
        }

        if (property_exists($this, 'deselectedColumns')) {
            $this->deselectedColumns = $view->deselectedColumns;
        }

        if (property_exists($this, 'columnPins')) {
            $this->columnPins = $view->columnPins;
        }

        $this->activeSavedView = $id;

        // Sin desactivar el preset: una vista y un preset son dos formas del
        // mismo estado y activar una tiene que soltar el otro.
        if (property_exists($this, 'activePreset')) {
            $this->activePreset = null;
        }

        $this->resetDataScope();
    }

    public function clearSavedView(): void
    {
        $this->activeSavedView = null;
        $this->filters = [];
        $this->sorts   = [];
        $this->search  = '';

        $this->resetDataScope();
    }

    public function deleteSavedView(string $id): void
    {
        if (! $this->savedViewsEnabled) {
            return;
        }

        $this->savedViewStore()->delete($this->savedViewsKey(), $id);

        if ($this->activeSavedView === $id) {
            $this->activeSavedView = null;
        }
    }
}

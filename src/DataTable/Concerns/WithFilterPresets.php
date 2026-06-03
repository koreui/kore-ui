<?php

namespace KoreUi\DataTable\Concerns;

use KoreUi\DataTable\Presets\FilterPreset;
use Livewire\Attributes\Locked;

trait WithFilterPresets
{
    public ?string $activePreset = null;

    /**
     * Cached preset counts. Computed once per component lifetime instead of on
     * every render/morph, and invalidated when the data changes (bulk actions,
     * inline edits). #[Locked] so the client cannot tamper with the values.
     */
    #[Locked]
    public array $presetCountsCache = [];

    #[Locked]
    public bool $presetCountsLoaded = false;

    /**
     * Override in subclass to define filter presets.
     *
     * @return FilterPreset[]
     */
    public function filterPresets(): array
    {
        return [];
    }

    public function mountWithFilterPresets(): void
    {
        foreach ($this->filterPresets() as $preset) {
            if ($preset->isDefault()) {
                // Apply the default preset WITHOUT resetPage(): a ?page coming
                // from the URL must be respected, and resetPage() here would
                // pre-initialize the paginator, making Livewire skip the ?page
                // URL binding (so page would stop persisting for this table).
                $this->applyPresetState($preset->getIdentifier());
                break;
            }
        }
    }

    public function applyPreset(string $identifier): void
    {
        if ($this->activePreset === $identifier) {
            $this->clearPreset();
            return;
        }

        if (! $this->applyPresetState($identifier)) {
            return;
        }

        $this->resetPage();

        if (property_exists($this, 'selectAllMatching')) {
            $this->selectAllMatching = false;
        }
    }

    /**
     * Apply a preset's filters/sorts/search/perPage. Returns false if the preset
     * doesn't exist. Does NOT reset the page — the caller decides, because
     * resetPage() during mount() pre-initializes the paginator and makes
     * Livewire skip registering the ?page URL binding.
     */
    protected function applyPresetState(string $identifier): bool
    {
        $preset = $this->findPreset($identifier);

        if (! $preset) {
            return false;
        }

        $this->filters = $preset->getFilters();
        $this->sorts = $preset->getSorts();
        $this->search = $preset->getSearch() ?? '';

        if ($preset->hasPerPage()) {
            $this->perPage = $preset->getPerPage();
        }

        $this->activePreset = $identifier;

        return true;
    }

    public function clearPreset(): void
    {
        $this->activePreset = null;
        $this->filters = [];
        $this->sorts = [];
        $this->search = '';
        $this->resetPage();

        if (property_exists($this, 'selectAllMatching')) {
            $this->selectAllMatching = false;
        }
    }

    /**
     * Get visible presets.
     *
     * @return FilterPreset[]
     */
    public function resolveFilterPresets(): array
    {
        return collect($this->filterPresets())
            ->reject(fn (FilterPreset $preset) => $preset->isHidden())
            ->values()
            ->all();
    }

    public function getPresetCounts(): array
    {
        if ($this->presetCountsLoaded) {
            return $this->presetCountsCache;
        }

        $counts = [];

        foreach ($this->filterPresets() as $preset) {
            if ($preset->hasCount()) {
                $counts[$preset->getIdentifier()] = ($preset->getCountCallback())();
            }
        }

        $this->presetCountsCache = $counts;
        $this->presetCountsLoaded = true;

        return $counts;
    }

    /**
     * Force preset counts to be recomputed on the next render. Call after any
     * operation that mutates the underlying data.
     */
    public function invalidatePresetCounts(): void
    {
        $this->presetCountsCache = [];
        $this->presetCountsLoaded = false;
    }

    public function findPreset(string $identifier): ?FilterPreset
    {
        foreach ($this->filterPresets() as $preset) {
            if ($preset->getIdentifier() === $identifier) {
                return $preset;
            }
        }

        return null;
    }
}

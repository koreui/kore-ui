<?php

namespace KoreUi\DataTable\Concerns;

use KoreUi\DataTable\Presets\FilterPreset;

trait WithFilterPresets
{
    public ?string $activePreset = null;

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
                $this->applyPreset($preset->getIdentifier());
                break;
            }
        }
    }

    public function applyPreset(string $identifier): void
    {
        $preset = $this->findPreset($identifier);

        if (! $preset) {
            return;
        }

        // Always reset state first, then apply preset values
        $this->filters = $preset->getFilters();
        $this->sorts = $preset->getSorts();
        $this->search = $preset->getSearch() ?? '';

        if ($preset->hasPerPage()) {
            $this->perPage = $preset->getPerPage();
        }

        $this->activePreset = $identifier;
        $this->resetPage();
    }

    public function clearPreset(): void
    {
        $this->activePreset = null;
        $this->filters = [];
        $this->sorts = [];
        $this->search = '';
        $this->resetPage();
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
        $counts = [];

        foreach ($this->filterPresets() as $preset) {
            if ($preset->hasCount()) {
                $counts[$preset->getIdentifier()] = ($preset->getCountCallback())();
            }
        }

        return $counts;
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

<?php

namespace KoreUi\Spotlight;

use Livewire\Component;

abstract class SpotlightProvider
{
    public function __construct(
        protected readonly ?Component $livewireComponent = null
    ) {}

    /**
     * Items that are always shown when the input is empty.
     *
     * @return array<SpotlightItem>
     */
    public function items(): array
    {
        return [];
    }

    /**
     * Items filtered by the given query.
     * Default implementation returns all items — Alpine handles fuzzy filtering.
     * Override this method to implement server-side filtering.
     *
     * @return array<SpotlightItem>
     */
    public function search(string $query): array
    {
        return $this->items();
    }

    /**
     * Display order between providers (lower = appears first).
     */
    public function priority(): int
    {
        return 100;
    }

    /**
     * Group/section name for items from this provider.
     */
    public function group(): string
    {
        return 'General';
    }

    /**
     * Serialize all items from this provider to arrays,
     * overriding each item's group with the provider's group if not explicitly set.
     *
     * @return array<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(function (SpotlightItem $item) {
            $data = $item->toArray();
            // Use provider group as fallback if item kept the default 'General'
            if ($data['group'] === 'General') {
                $data['group'] = $this->group();
            }

            return $data;
        }, array_filter($this->items(), fn (SpotlightItem $item) => $item->isVisible()));
    }
}

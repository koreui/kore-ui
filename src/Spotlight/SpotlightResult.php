<?php

namespace KoreUi\Spotlight;

use KoreUi\DataTable\Support\UrlSanitizer;

class SpotlightResult
{
    protected function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $icon,
        public readonly string $group,
        public readonly ?string $actionType,
        public readonly ?string $actionTarget,
        public readonly array $actionParams,
    ) {}

    /**
     * Create a SpotlightResult from a raw array (e.g. from a remote endpoint response).
     */
    public static function fromArray(array $data): static
    {
        $actionType = $data['actionType'] ?? $data['action_type'] ?? null;
        $actionTarget = $data['actionTarget'] ?? $data['action_target'] ?? null;

        // Remote responses are untrusted: a 'url' target navigates client-side, so
        // strip dangerous schemes (javascript:, data:, //host) before it reaches JS.
        if ($actionType === 'url') {
            $actionTarget = UrlSanitizer::sanitize($actionTarget);
        }

        return new static(
            id: $data['id'] ?? \Illuminate\Support\Str::slug($data['name'] ?? ''),
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
            icon: $data['icon'] ?? null,
            group: $data['group'] ?? 'Resultados',
            actionType: $actionType,
            actionTarget: $actionTarget,
            actionParams: $data['actionParams'] ?? $data['action_params'] ?? [],
        );
    }

    /**
     * Convert to a SpotlightItem instance.
     */
    public function toSpotlightItem(): SpotlightItem
    {
        $item = SpotlightItem::make($this->name)
            ->group($this->group);

        if ($this->description !== null) {
            $item->description($this->description);
        }

        if ($this->icon !== null) {
            $item->icon($this->icon);
        }

        if ($this->actionType === 'route' && $this->actionTarget) {
            $item->route($this->actionTarget, $this->actionParams);
        } elseif ($this->actionType === 'url' && $this->actionTarget) {
            $item->url($this->actionTarget);
        } elseif ($this->actionType === 'action' && $this->actionTarget) {
            $item->action($this->actionTarget, $this->actionParams);
        } elseif ($this->actionType === 'dispatch' && $this->actionTarget) {
            $item->dispatch($this->actionTarget, $this->actionParams);
        }

        return $item;
    }

    /**
     * Build the payload array for the frontend.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'description'  => $this->description,
            'icon'         => $this->icon,
            'iconHtml'     => $this->resolveIconHtml(),
            'group'        => $this->group,
            'actionType'   => $this->actionType,
            'actionTarget' => $this->actionTarget,
            'actionParams' => $this->actionParams,
            'synonyms'     => [],
            'hidden'       => false,
            'dependencies' => [],
        ];
    }

    protected function resolveIconHtml(): ?string
    {
        if ($this->icon === null) {
            return null;
        }

        try {
            return svg('lucide-'.$this->icon, 'size-4')->toHtml();
        } catch (\Throwable) {
            return null;
        }
    }
}

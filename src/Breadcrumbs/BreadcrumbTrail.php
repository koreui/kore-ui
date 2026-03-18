<?php

namespace KoreUi\Breadcrumbs;

use stdClass;

class BreadcrumbTrail
{
    protected array $items = [];

    protected array $visited = [];

    public function __construct(
        protected BreadcrumbManager $manager,
    ) {}

    public function push(
        string $label,
        ?string $url = null,
        ?string $icon = null,
        ?string $tooltip = null,
        array $data = [],
    ): self {
        $item = new stdClass;
        $item->label = $label;
        $item->url = $url;
        $item->icon = $icon;
        $item->tooltip = $tooltip;
        $item->current = false;

        foreach ($data as $key => $value) {
            $item->{$key} = $value;
        }

        $this->items[] = $item;

        return $this;
    }

    public function parent(string $route, mixed ...$params): self
    {
        if (in_array($route, $this->visited)) {
            throw new \RuntimeException("Circular breadcrumb reference detected for route [{$route}].");
        }

        $this->visited[] = $route;

        $parentTrail = new static($this->manager);
        $parentTrail->visited = $this->visited;

        $this->manager->call($route, $parentTrail, $params);

        array_unshift($this->items, ...$parentTrail->items);
        $this->visited = $parentTrail->visited;

        return $this;
    }

    public function items(): array
    {
        return $this->items;
    }
}

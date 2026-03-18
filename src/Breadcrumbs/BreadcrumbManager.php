<?php

namespace KoreUi\Breadcrumbs;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use KoreUi\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use KoreUi\Breadcrumbs\Exceptions\InvalidBreadcrumbException;
use KoreUi\Breadcrumbs\Exceptions\UnnamedRouteException;

class BreadcrumbManager
{
    use Macroable;

    protected array $definitions = [];

    protected array $beforeCallbacks = [];

    protected array $afterCallbacks = [];

    protected ?array $currentRouteOverride = null;

    // ── Registration ──

    public function for(string $route, Closure $callback): self
    {
        if (isset($this->definitions[$route]) && config('kore-ui.breadcrumbs.exceptions.duplicate', true)) {
            throw new DuplicateBreadcrumbException($route);
        }

        $this->definitions[$route] = $callback;

        return $this;
    }

    public function before(Closure $callback): self
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    public function after(Closure $callback): self
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    // ── Resolution ──

    public function generate(?string $route = null, mixed ...$params): Collection
    {
        if ($route === null) {
            [$route, $params] = $this->getCurrentRoute();
        }

        if (! isset($this->definitions[$route])) {
            if (config('kore-ui.breadcrumbs.exceptions.missing', true)) {
                throw new InvalidBreadcrumbException($route);
            }

            return collect();
        }

        $trail = new BreadcrumbTrail($this);

        foreach ($this->beforeCallbacks as $callback) {
            $callback($trail);
        }

        $this->call($route, $trail, $params);

        foreach ($this->afterCallbacks as $callback) {
            $callback($trail);
        }

        $items = collect($trail->items());

        if ($items->isNotEmpty()) {
            $items->last()->current = true;
        }

        return $items;
    }

    public function exists(?string $route = null): bool
    {
        if ($route === null) {
            try {
                [$route] = $this->getCurrentRoute();
            } catch (UnnamedRouteException) {
                return false;
            }
        }

        return isset($this->definitions[$route]);
    }

    public function current(): ?object
    {
        try {
            $items = $this->generate();

            return $items->last();
        } catch (InvalidBreadcrumbException|UnnamedRouteException) {
            return null;
        }
    }

    // ── Current Route Override ──

    public function setCurrentRoute(string $route, mixed ...$params): void
    {
        $this->currentRouteOverride = [$route, $params];
    }

    public function clearCurrentRoute(): void
    {
        $this->currentRouteOverride = null;
    }

    // ── Internal ──

    protected function getCurrentRoute(): array
    {
        if ($this->currentRouteOverride !== null) {
            return $this->currentRouteOverride;
        }

        $route = request()->route();

        if ($route === null || $route->getName() === null) {
            if (config('kore-ui.breadcrumbs.exceptions.unnamed', true)) {
                throw new UnnamedRouteException;
            }

            return ['', []];
        }

        return [$route->getName(), array_values($route->parameters())];
    }

    public function call(string $route, BreadcrumbTrail $trail, array $params): void
    {
        if (! isset($this->definitions[$route])) {
            if (config('kore-ui.breadcrumbs.exceptions.missing', true)) {
                throw new InvalidBreadcrumbException($route);
            }

            return;
        }

        $callback = $this->definitions[$route];

        $callback($trail, ...$params);
    }
}

<?php

namespace KoreUi\Spotlight;

use Illuminate\Support\Collection;
use KoreUi\Spotlight\Config\SpotlightDefaults;
use Livewire\Component;

class SpotlightManager extends Component
{
    public string $shortcut = 'k';

    public string $placeholder = '';

    public ?string $searchUrl = null;

    public string $searchMethod = 'GET';

    public int $debounce = 300;

    public bool $showRecent = true;

    public int $recentCount = 5;

    public int $maxResults = 50;

    public array $providers = [];

    public function mount(
        string $shortcut = 'k',
        string $placeholder = '',
        ?string $searchUrl = null,
        string $searchMethod = 'GET',
        int $debounce = 300,
        bool $showRecent = true,
        int $recentCount = 5,
        int $maxResults = 50,
        array $providers = [],
    ): void {
        $this->shortcut = $shortcut ?: SpotlightDefaults::shortcut();
        $this->placeholder = $placeholder ?: SpotlightDefaults::placeholder();
        $this->searchUrl = $searchUrl;
        $this->searchMethod = strtoupper($searchMethod);
        $this->debounce = $debounce;
        $this->showRecent = $showRecent;
        $this->recentCount = $recentCount;
        $this->maxResults = $maxResults;
        $this->providers = $providers ?: config('kore-ui.spotlight.providers', []);
    }

    /**
     * Resolve all providers and return their items as serialized arrays.
     *
     * @return array<array<string, mixed>>
     */
    public function getItems(): array
    {
        return collect($this->providers)
            ->map(fn ($class) => app($class))
            ->sortBy(fn (SpotlightProvider $p) => $p->priority())
            ->flatMap(fn (SpotlightProvider $p) => $p->toArray())
            ->take($this->maxResults)
            ->values()
            ->all();
    }

    /**
     * Server-side search — called via $wire.search() from Alpine with debounce.
     * Queries providers that override search() for remote results.
     */
    public function search(string $query): void
    {
        if (empty(trim($query))) {
            $this->dispatch('kore:spotlight-results', items: []);

            return;
        }

        $items = collect($this->providers)
            ->map(fn ($class) => app($class))
            ->sortBy(fn (SpotlightProvider $p) => $p->priority())
            ->flatMap(function (SpotlightProvider $provider) use ($query) {
                $results = $provider->search($query);

                return array_map(function (SpotlightItem $item) use ($provider) {
                    $data = $item->toArray();
                    if ($data['group'] === 'General') {
                        $data['group'] = $provider->group();
                    }

                    return $data;
                }, array_filter($results, fn (SpotlightItem $item) => $item->isVisible()));
            })
            ->take($this->maxResults)
            ->values()
            ->all();

        $this->dispatch('kore:spotlight-results', items: $items);
    }

    /**
     * Search within a dependency's remote endpoint.
     * Called via $wire.searchDependency() from Alpine.
     */
    public function searchDependency(string $commandId, array $dependency, string $query): void
    {
        if (empty(trim($query)) || empty($dependency['searchUrl'])) {
            $this->dispatch('kore:spotlight-dependency-results', items: []);

            return;
        }

        $url = $dependency['searchUrl'];

        // If it looks like a route name (no slash), resolve it
        if (! str_contains($url, '/') && ! str_starts_with($url, 'http')) {
            try {
                $url = route($url, ['query' => $query]);
            } catch (\Exception $e) {
                $url = $url.'?query='.urlencode($query);
            }
        } else {
            $url .= (str_contains($url, '?') ? '&' : '?').'query='.urlencode($query);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::get($url);
            $data = $response->json() ?? [];

            $items = collect($data)
                ->map(fn ($item) => SpotlightResult::fromArray($item)->toArray())
                ->take(20)
                ->values()
                ->all();
        } catch (\Exception $e) {
            $items = [];
        }

        $this->dispatch('kore:spotlight-dependency-results', items: $items);
    }

    /**
     * Execute a route navigation action.
     */
    public function executeRoute(string $routeName, array $params = []): void
    {
        $url = route($routeName, $params);
        $this->redirect($url);
    }

    /**
     * Execute a Livewire method action on the caller component.
     * The caller component ID is passed from Alpine so we can find it.
     */
    public function executeAction(string $callerComponentId, string $method, array $params = [], array $resolvedDependencies = []): void
    {
        $component = \Livewire\Livewire::current();

        // Merge resolved dependencies as trailing parameters
        $allParams = array_merge($params, $resolvedDependencies);

        $this->dispatch('kore:spotlight-execute-action', [
            'componentId' => $callerComponentId,
            'method'      => $method,
            'params'      => $allParams,
        ]);
    }

    public function render()
    {
        return view('kore::spotlight.manager', [
            'items'  => $this->getItems(),
            'config' => [
                'shortcut'    => $this->shortcut,
                'placeholder' => $this->placeholder,
                'searchUrl'   => $this->searchUrl,
                'searchMethod' => $this->searchMethod,
                'debounce'    => $this->debounce,
                'showRecent'  => $this->showRecent,
                'recentCount' => $this->recentCount,
                'maxResults'  => $this->maxResults,
                'zIndex'      => SpotlightDefaults::zIndex(),
                'maxHistory'  => SpotlightDefaults::maxHistory(),
            ],
        ]);
    }
}

<?php

namespace KoreUi\Spotlight;

use Illuminate\Support\Collection;
use KoreUi\Spotlight\Config\SpotlightDefaults;
use Livewire\Attributes\Locked;
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

    /**
     * Provider class names (or container bindings). #[Locked] so the client can't
     * swap them for an arbitrary class that getItems()/search() would then
     * instantiate via app(). Seeded only from mount params / config.
     *
     * @var array<int, string>
     */
    #[Locked]
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
     * Resolve the configured providers to instances, skipping anything that is
     * not a SpotlightProvider. Arbitrary class names are rejected *before* app()
     * so a tampered/invalid entry can't instantiate an unrelated class; runtime
     * container bindings (used in tests and custom registrations) are allowed.
     *
     * @return Collection<int, SpotlightProvider>
     */
    protected function resolveProviders(): Collection
    {
        return collect($this->providers)
            ->filter(fn ($class) => is_string($class)
                && (app()->bound($class) || is_subclass_of($class, SpotlightProvider::class)))
            ->map(fn ($class) => app($class))
            ->filter(fn ($provider) => $provider instanceof SpotlightProvider)
            ->values();
    }

    /**
     * Resolve all providers and return their items as serialized arrays.
     *
     * @return array<array<string, mixed>>
     */
    public function getItems(): array
    {
        return $this->resolveProviders()
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

        $items = $this->resolveProviders()
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

        // If it looks like a route name (no slash), resolve it server-side (safe:
        // the host is our own app, the client only supplies the route name).
        if (! str_contains($url, '/') && ! str_starts_with($url, 'http')) {
            try {
                $url = route($url, ['query' => $query]);
            } catch (\Exception $e) {
                $url = $url.'?query='.urlencode($query);
            }
        } else {
            // Absolute/relative URL supplied by the client → guard against SSRF
            // (cloud metadata, localhost services, private ranges) before any request.
            if (! $this->isSafeRemoteUrl($url)) {
                $this->dispatch('kore:spotlight-dependency-results', items: []);

                return;
            }

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
     * SSRF guard for client-supplied remote search URLs. Only http(s) is allowed,
     * and the resolved host must not fall in a private or reserved range
     * (loopback, link-local metadata 169.254.x, RFC1918, etc.).
     */
    protected function isSafeRemoteUrl(string $url): bool
    {
        $parts = parse_url($url);

        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        $ips = $this->resolveHostIps($host);

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            // NO_PRIV_RANGE + NO_RES_RANGE reject 10/8, 172.16/12, 192.168/16,
            // 127/8, 169.254/16, ::1, fc00::/7, etc. A private/reserved IP fails
            // the filter (returns false) → not safe.
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve a host to its IP addresses. Literal IPs pass through unchanged;
     * host names are resolved via DNS. Returns [] when resolution fails.
     *
     * @return array<int, string>
     */
    protected function resolveHostIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        return gethostbynamel($host) ?: [];
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

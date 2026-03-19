@props([
    'shortcut'    => config('kore-ui.spotlight.shortcut', 'k'),
    'placeholder' => config('kore-ui.spotlight.placeholder', 'Buscar acciones, páginas...'),
    'providers'   => config('kore-ui.spotlight.providers', []),
    'searchUrl'   => config('kore-ui.spotlight.search_url'),
    'searchMethod' => config('kore-ui.spotlight.search_method', 'GET'),
    'showRecent'  => config('kore-ui.spotlight.show_recent', true),
    'recentCount' => config('kore-ui.spotlight.recent_count', 5),
    'maxResults'  => config('kore-ui.spotlight.max_results', 50),
    'debounce'    => config('kore-ui.spotlight.debounce', 300),
])

@livewire('kore-spotlight-manager', [
    'shortcut'     => $shortcut,
    'placeholder'  => $placeholder,
    'providers'    => $providers,
    'searchUrl'    => $searchUrl,
    'searchMethod' => $searchMethod,
    'showRecent'   => $showRecent,
    'recentCount'  => $recentCount,
    'maxResults'   => $maxResults,
    'debounce'     => $debounce,
])

<?php

namespace KoreUi\Shell;

use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Resolución de enlaces y de "ruta activa" para los items del sidebar.
 *
 * Es PHP puro y sin estado: el item activo se decide EN EL SERVIDOR y se emite ya
 * marcado en el HTML. Nada de resolverlo en Alpine tras el render, que pintaría
 * el menú sin marcar durante un frame.
 */
final class NavigationMatcher
{
    /**
     * URL final del item.
     *
     * `route` admite patrones con comodín ("users.*"), que sirven para MATCHING
     * pero no son nombres de ruta resolubles. En ese caso —o si la ruta no existe,
     * o le faltan parámetros— caemos a `href` en vez de reventar el layout entero
     * por un enlace mal escrito.
     */
    public static function href(?string $route = null, array $routeParams = [], ?string $href = null): ?string
    {
        if ($route === null || Str::contains($route, '*') || ! Route::has($route)) {
            return $href;
        }

        try {
            return route($route, $routeParams);
        } catch (UrlGenerationException) {
            return $href;
        }
    }

    /**
     * Orden de prioridad (según la spec):
     *   1. `active` explícito — override total, puede forzar false
     *   2. `match`  → request()->routeIs(...), admite comodines
     *   3. `route`  → request()->routeIs($route)
     *   4. `href`   → comparación de URL
     *   5. hijo activo → el padre se marca activo también (aditivo)
     */
    public static function isActive(
        ?bool $active = null,
        string|array|null $match = null,
        ?string $route = null,
        ?string $href = null,
        bool $smart = true,
        bool $hasActiveChild = false,
    ): bool {
        if ($active !== null) {
            return $active;
        }

        if (! $smart) {
            return $hasActiveChild;
        }

        $self = false;

        if ($match !== null && $match !== []) {
            $self = request()->routeIs(...(array) $match);
        } elseif ($route !== null) {
            $self = request()->routeIs($route);
        } elseif ($href !== null) {
            $self = self::matchesUrl($href);
        }

        return $self || $hasActiveChild;
    }

    /**
     * ¿La URL actual es esta? Admite comodín ("/users/*").
     */
    public static function matchesUrl(string $href): bool
    {
        $href = trim($href);

        // Enlaces que no navegan a ninguna parte nunca están "activos".
        if ($href === '' || $href === '#' || Str::startsWith($href, ['mailto:', 'tel:', 'javascript:'])) {
            return false;
        }

        if (Str::contains($href, '*')) {
            $path = parse_url($href, PHP_URL_PATH) ?: $href;

            return request()->is(ltrim($path, '/'));
        }

        $current = rtrim(request()->url(), '/');
        $target = rtrim(url($href), '/');

        return $current !== '' && $current === $target;
    }
}

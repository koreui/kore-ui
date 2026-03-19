<?php

namespace KoreUi\Spotlight\Config;

class SpotlightDefaults
{
    /**
     * Default keyboard shortcut key (combined with Cmd/Ctrl).
     */
    public static function shortcut(): string
    {
        return config('kore-ui.spotlight.shortcut', 'k');
    }

    /**
     * Default input placeholder text.
     */
    public static function placeholder(): string
    {
        return config('kore-ui.spotlight.placeholder', 'Buscar acciones, páginas...');
    }

    /**
     * Default debounce delay in milliseconds for remote search.
     */
    public static function debounce(): int
    {
        return (int) config('kore-ui.spotlight.debounce', 300);
    }

    /**
     * Maximum number of results to show.
     */
    public static function maxResults(): int
    {
        return (int) config('kore-ui.spotlight.max_results', 50);
    }

    /**
     * Maximum history entries to keep in localStorage.
     */
    public static function maxHistory(): int
    {
        return (int) config('kore-ui.spotlight.max_history', 10);
    }

    /**
     * Number of recent items to display when input is empty.
     */
    public static function recentCount(): int
    {
        return (int) config('kore-ui.spotlight.recent_count', 5);
    }

    /**
     * Whether to show recent history when input is empty.
     */
    public static function showRecent(): bool
    {
        return (bool) config('kore-ui.spotlight.show_recent', true);
    }

    /**
     * Whether to show results when input is empty.
     */
    public static function showResultsWithoutInput(): bool
    {
        return (bool) config('kore-ui.spotlight.show_results_without_input', true);
    }

    /**
     * z-index class for the spotlight panel.
     */
    public static function zIndex(): string
    {
        return config('kore-ui.spotlight.z_index', 'z-[70]');
    }

    /**
     * Valid action types for SpotlightItem.
     *
     * @return array<string>
     */
    public static function actionTypes(): array
    {
        return ['route', 'url', 'action', 'dispatch'];
    }

    /**
     * Check if an action type is valid.
     */
    public static function isValidActionType(string $type): bool
    {
        return in_array($type, static::actionTypes(), true);
    }

    /**
     * Valid dependency types for SpotlightDependency.
     *
     * @return array<string>
     */
    public static function dependencyTypes(): array
    {
        return ['search', 'input', 'select'];
    }
}

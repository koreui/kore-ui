<?php

namespace KoreUi\Feedback\Config;

class FeedbackDefaults
{
    /**
     * Default timeout (seconds) per toast type.
     * 0 = persistent (no auto-dismiss).
     *
     * @return array<string, int>
     */
    public static function timeouts(): array
    {
        return [
            'success'  => 5,
            'error'    => 0,
            'warning'  => 8,
            'info'     => 5,
            'question' => 0,
            'loading'  => 0,
        ];
    }

    /**
     * Get the default timeout for a toast type.
     */
    public static function timeout(string $type): int
    {
        return static::timeouts()[$type] ?? (int) config('kore-ui.feedback.toast.timeout', 5);
    }

    /**
     * Valid toast positions.
     *
     * @return array<int, string>
     */
    public static function positions(): array
    {
        return [
            'top-left',
            'top-center',
            'top-right',
            'bottom-left',
            'bottom-center',
            'bottom-right',
        ];
    }

    /**
     * Check if a position string is valid.
     */
    public static function isValidPosition(string $position): bool
    {
        return in_array($position, static::positions(), true);
    }

    /**
     * SVG icon names per toast type.
     *
     * @return array<string, string>
     */
    public static function icons(): array
    {
        return [
            'success'  => 'check-circle',
            'error'    => 'x-circle',
            'warning'  => 'alert-triangle',
            'info'     => 'info-circle',
            'question' => 'question-mark-circle',
            'loading'  => 'spinner',
        ];
    }

    /**
     * Get the icon name for a toast type.
     */
    public static function icon(string $type): string
    {
        return static::icons()[$type] ?? 'info-circle';
    }

    /**
     * Color token per toast type (maps to kore-theme.css tokens).
     *
     * @return array<string, string>
     */
    public static function colorTokens(): array
    {
        return [
            'success'  => 'kore-success',
            'error'    => 'kore-destructive',
            'warning'  => 'kore-warning',
            'info'     => 'kore-info',
            'question' => 'kore-primary',
            'loading'  => 'kore-muted',
        ];
    }

    /**
     * Get the color token for a toast type.
     */
    public static function colorToken(string $type): string
    {
        return static::colorTokens()[$type] ?? 'kore-info';
    }
}

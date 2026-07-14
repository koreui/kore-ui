<?php

namespace KoreUi\Shell;

/**
 * Fuente única de los breakpoints del shell.
 *
 * El layout responsive se resuelve con media queries estáticas en CSS (para que
 * sea correcto en el primer paint, sin JS), pero el COMPORTAMIENTO —auto-cerrar
 * el drawer, tomar el scroll-lock— necesita el mismo umbral en JavaScript. Los
 * dos lados leen de aquí para que no puedan divergir.
 *
 * Coinciden con los breakpoints por defecto de Tailwind.
 */
final class Breakpoints
{
    private const MAP = [
        'sm' => 640,
        'md' => 768,
        'lg' => 1024,
        'xl' => 1280,
    ];

    /**
     * @return array<string, int>
     */
    public static function all(): array
    {
        return self::MAP;
    }

    public static function isValid(string $breakpoint): bool
    {
        return isset(self::MAP[$breakpoint]);
    }

    /**
     * Píxeles a partir de los cuales el sidebar deja de ser un drawer.
     */
    public static function px(string $breakpoint): int
    {
        return self::MAP[$breakpoint] ?? self::MAP['lg'];
    }

    /**
     * Normaliza a un breakpoint conocido; cualquier basura cae al default.
     */
    public static function resolve(?string $breakpoint, string $fallback = 'lg'): string
    {
        if ($breakpoint !== null && self::isValid($breakpoint)) {
            return $breakpoint;
        }

        return self::isValid($fallback) ? $fallback : 'lg';
    }
}

<?php

namespace KoreUi\Shell;

/**
 * Estado persistido del sidebar, leído de la cookie `kore_sidebar`.
 *
 * La cookie guarda un mapa JSON con TODOS los sidebars de la app —
 * `{"main":1,"tools":0}` — y no una cookie por sidebar, porque
 * `EncryptCookies::except()` no admite comodines: un nombre dinámico por
 * sidebar sería imposible de registrar y Laravel los anularía todos.
 *
 * La escribe JavaScript, así que es INPUT NO CONFIABLE: cualquiera puede editarla
 * desde la consola del navegador. Todo lo que sale de aquí está saneado, y el
 * único valor que llega al HTML es un enum cerrado (`expanded` | `collapsed`).
 */
final class SidebarState
{
    public const COOKIE = 'kore_sidebar';

    /** Una cookie legítima ocupa decenas de bytes; esto solo frena abusos. */
    private const MAX_BYTES = 512;

    private const MAX_ENTRIES = 10;

    private const ID_PATTERN = '/^[A-Za-z0-9_-]{1,32}$/';

    /**
     * Estado colapsado de cada sidebar, por id.
     *
     * @return array<string, bool>
     */
    public static function all(): array
    {
        $raw = self::raw();

        if ($raw === null || $raw === '' || strlen($raw) > self::MAX_BYTES) {
            return [];
        }

        $decoded = json_decode($raw, true);

        // Tiene que ser un objeto JSON: una lista o un escalar no dicen nada.
        if (! is_array($decoded) || array_is_list($decoded)) {
            return [];
        }

        $state = [];

        foreach ($decoded as $id => $collapsed) {
            if (count($state) >= self::MAX_ENTRIES) {
                break;
            }

            if (! is_string($id) || preg_match(self::ID_PATTERN, $id) !== 1) {
                continue;
            }

            $state[$id] = $collapsed === 1 || $collapsed === true || $collapsed === '1';
        }

        return $state;
    }

    /**
     * ¿Está colapsado este sidebar?
     *
     * La cookie manda sobre la prop, porque representa lo que el usuario eligió
     * en esta sesión; la prop solo aporta el valor inicial de la primera visita.
     */
    public static function collapsed(string $id, bool $default = false): bool
    {
        return self::all()[$id] ?? $default;
    }

    /**
     * El valor que va al atributo `data-kore-sidebar`. Enum cerrado a propósito:
     * es lo único de la cookie que acaba en el HTML.
     */
    public static function attribute(string $id, bool $default = false): string
    {
        return self::collapsed($id, $default) ? 'collapsed' : 'expanded';
    }

    /**
     * Valida una longitud CSS antes de que entre en un atributo `style`.
     * Sin esto, la prop `width` sería una vía de inyección de CSS arbitrario.
     */
    public static function cssLength(?string $value, string $fallback): string
    {
        if ($value !== null && preg_match('/^\d+(\.\d+)?(rem|px|em|ch|vw|%)$/', trim($value)) === 1) {
            return trim($value);
        }

        return $fallback;
    }

    /**
     * El valor crudo de la cookie.
     *
     * Cae a $_COOKIE si `request()->cookie()` viene vacío: si alguien olvida
     * registrar la cookie en `EncryptCookies::except()`, Laravel no puede
     * desencriptarla y la deja a null SIN avisar. Esta segunda vía hace que el
     * sidebar siga funcionando en vez de "no persistir y nadie sabe por qué".
     */
    private static function raw(): ?string
    {
        $value = null;

        if (function_exists('request') && app()->bound('request')) {
            $value = request()->cookie(self::COOKIE);
        }

        if (! is_string($value) || $value === '') {
            $value = $_COOKIE[self::COOKIE] ?? null;
        }

        return is_string($value) ? $value : null;
    }
}

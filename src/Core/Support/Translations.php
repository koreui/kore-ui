<?php

namespace KoreUi\Core\Support;

/**
 * El idioma de los textos de la librería.
 *
 * Los textos viven en `config/kore-ui.php` (`form.translations`, `ui.translations`,
 * `datatable.translations` y tres sueltos), en español, y los componentes los leen
 * de ahí con `config()`. Eso sirve para una aplicación en un idioma; con dos, o en
 * inglés, había que traducir a mano doscientas claves en el config publicado.
 *
 * `kore-ui.locale` elige un paquete de `lang/{idioma}/kore-ui.php` y lo vuelca
 * sobre el config, así que ningún componente cambia:
 *
 *   null     como siempre: los textos del config, sin tocar (de fábrica)
 *   'auto'   el idioma de la aplicación, y lo sigue si cambia a mitad de petición
 *   'en'…    ese idioma, fijo
 *
 * `null` de fábrica y no `'auto'`: Laravel instala `APP_LOCALE=en`, y muchas
 * aplicaciones en español nunca lo cambian. Seguir el idioma de la aplicación
 * por defecto les habría pasado la interfaz al inglés al actualizar.
 *
 * Un texto que la aplicación ya cambió en su config se respeta: el paquete solo
 * sustituye los que siguen con su valor de fábrica. Y un idioma sin paquete deja
 * el config como está.
 */
final class Translations
{
    /** Las secciones del config con un array `translations`. */
    public const SECTIONS = ['form', 'ui', 'datatable'];

    /** Los textos del config que van sueltos, fuera de una sección `translations`. */
    public const LOOSE = ['datatable.empty_text', 'spotlight.placeholder', 'chart.empty_text'];

    private const STATE = 'kore-ui.translations.state';

    /** @var array<string, mixed>|null */
    private static ?array $factory = null;

    /**
     * Vuelca sobre el config el paquete del idioma que toque. Se puede llamar las veces
     * que haga falta: Livewire fija el idioma en cada petición, así que con `'auto'` se
     * llama en todas.
     *
     * Solo toca lo suyo. De cada texto que sustituye guarda qué había y qué puso; si al
     * volver el config ya no tiene lo que puso, es que la aplicación lo cambió después
     * (un `config()->set()` en un proveedor o en un middleware) y ese valor manda. Sin
     * `locale`, y sin nada aplicado antes, no hace nada.
     */
    public static function apply(): void
    {
        $app = app();

        // En el contenedor y no en una estática: cada aplicación (y cada test) tiene el suyo.
        /** @var array{original: array<string, mixed>, applied: array<string, string>} $state */
        $state = $app->bound(self::STATE) ? $app->make(self::STATE) : ['original' => [], 'applied' => []];

        $locale = self::locale();
        $pack = $locale === null ? [] : self::pack($locale);

        if ($pack === [] && $state['applied'] === []) {
            return;
        }

        $factory = self::factory();

        foreach (array_unique(array_merge(array_keys($pack), array_keys($state['applied']))) as $path) {
            $current = config('kore-ui.'.$path);

            // Lo que hay debajo de lo nuestro: lo que guardamos, salvo que alguien lo haya cambiado.
            $ours = array_key_exists($path, $state['applied']) && $state['applied'][$path] === $current;
            $base = $ours ? $state['original'][$path] : $current;
            unset($state['applied'][$path], $state['original'][$path]);

            // Solo se sustituye lo que sigue de fábrica: lo que la aplicación cambió, manda.
            $untouched = array_key_exists($path, $factory) && $factory[$path] === $base;

            if (isset($pack[$path]) && $untouched) {
                $state['original'][$path] = $base;
                $state['applied'][$path] = $pack[$path];
                config()->set('kore-ui.'.$path, $pack[$path]);
            } elseif ($ours) {
                config()->set('kore-ui.'.$path, $base);
            }
        }

        $app->instance(self::STATE, $state);
    }

    /** El idioma cuyo paquete se aplica, o `null` si el config se queda como está. */
    public static function locale(): ?string
    {
        $setting = config('kore-ui.locale');

        if (! is_string($setting) || trim($setting) === '') {
            return null;
        }

        $locale = $setting === 'auto' ? app()->getLocale() : $setting;

        // `en_GB`, `en-US` → `en`: los paquetes son por idioma, no por región.
        return strtolower(preg_split('/[-_.@]/', trim($locale), 2)[0]) ?: null;
    }

    /**
     * El paquete de un idioma, aplanado a rutas del config (`form.translations.clear`).
     * Sale del traductor de Laravel, así que una copia publicada en
     * `lang/vendor/kore-ui/{idioma}/kore-ui.php` gana a la del paquete — y sirve para
     * añadir un idioma que la librería no trae.
     *
     * @return array<string, string>
     */
    public static function pack(string $locale): array
    {
        $lines = app('translator')->get('kore-ui::kore-ui', [], $locale, false);

        if (! is_array($lines)) {
            return [];
        }

        $pack = [];

        foreach (self::SECTIONS as $section) {
            foreach ((array) ($lines[$section] ?? []) as $key => $text) {
                $pack[$section.'.translations.'.$key] = $text;
            }
        }

        foreach ((array) ($lines['loose'] ?? []) as $path => $text) {
            $pack[$path] = $text;
        }

        return array_filter($pack, 'is_string');
    }

    /**
     * Los textos de fábrica: los del `config/kore-ui.php` del paquete, sin lo que haya
     * publicado la aplicación.
     *
     * @return array<string, mixed>
     */
    public static function factory(): array
    {
        if (self::$factory === null) {
            $config = require __DIR__.'/../../../config/kore-ui.php';
            self::$factory = self::read(fn (string $path) => data_get($config, $path));
        }

        return self::$factory;
    }

    /**
     * Todas las rutas de texto con su valor, leídas con `$get`.
     *
     * @param  callable(string): mixed  $get
     * @return array<string, mixed>
     */
    private static function read(callable $get): array
    {
        $texts = [];

        foreach (self::SECTIONS as $section) {
            foreach ((array) $get($section.'.translations') as $key => $text) {
                $texts[$section.'.translations.'.$key] = $text;
            }
        }

        foreach (self::LOOSE as $path) {
            $texts[$path] = $get($path);
        }

        return $texts;
    }
}

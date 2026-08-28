<?php

namespace KoreUi\Editor\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use KoreUi\Editor\HtmlSanitizer;

/**
 * Rechaza el HTML que no pasaría entero por la lista blanca del editor.
 *
 * **Cuándo usar esto y cuándo no.** Una regla de validación *avisa*, no arregla:
 * si alguien manda `<script>`, aquí el formulario se rechaza y el usuario ve un
 * error que él no ha provocado —porque el editor de verdad nunca produce eso—.
 * Eso está bien para una API donde el cliente es de fiar y un HTML raro es una
 * señal, y es incómodo para un formulario normal.
 *
 * Para un formulario, lo que se quiere casi siempre es LIMPIAR y seguir, con el
 * cast `KoreUi\Editor\Casts\SanitizedHtml` o llamando a `HtmlSanitizer::limpiar()`
 * al guardar. Mejor todavía: guardar markdown y no tener nada que sanear.
 *
 * ```php
 * $request->validate([
 *     'cuerpo' => ['required', new SafeHtml],
 * ]);
 * ```
 */
class SafeHtml implements ValidationRule
{
    /** @param  array<int, string>|null  $etiquetas  una lista blanca más corta */
    public function __construct(protected ?array $etiquetas = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        // El mensaje va literal, no como clave: `->translate()` lo buscaría en
        // los archivos de idioma y, al no encontrarlo, enseñaría la frase entera
        // como si fuera un identificador.
        $mensaje = config('kore-ui.form.translations.editor_html_rechazado', 'El contenido tiene marcado que no se admite.');

        if (! is_string($value)) {
            $fail($mensaje);

            return;
        }

        if (HtmlSanitizer::limpiar($value, $this->etiquetas) !== trim($value)) {
            $fail($mensaje);
        }
    }
}

<?php

namespace KoreUi\Editor\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Limita el TEXTO, no el marcado.
 *
 * **Por qué no vale `max:200`.** La regla de Laravel mide la cadena entera, y en
 * un campo de texto enriquecido esa cadena lleva etiquetas: `<p><strong>Hola
 * </strong></p>` son 4 caracteres para quien escribe y 30 para `max`. El
 * contador del editor cuenta lo primero, así que con `max` el usuario ve «12/200»
 * y el formulario le dice que se ha pasado.
 *
 * Y hace falta en el servidor porque el límite del editor vive en el navegador:
 * es una comodidad para quien escribe, no una frontera.
 *
 * ```php
 * $request->validate([
 *     'cuerpo' => ['required', new MaxTextLength(500)],
 * ]);
 * ```
 */
class MaxTextLength implements ValidationRule
{
    public function __construct(protected int $maximo) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (self::contar((string) $value) > $this->maximo) {
            $fail(str_replace(
                [':max', ':attribute'],
                [(string) $this->maximo, $attribute],
                config('kore-ui.form.translations.editor_max_texto', 'El campo :attribute no puede pasar de :max caracteres.')
            ));
        }
    }

    /**
     * Cuántos caracteres ve quien lee.
     *
     * Se cuenta igual que el contador del editor: sin etiquetas, sin entidades a
     * medio decodificar y sin los espacios de sobra que deja el marcado entre
     * bloques. Si las dos cuentas no coinciden, el usuario ve un número y el
     * servidor otro.
     */
    public static function contar(string $valor): int
    {
        // Los bloques se separan con un espacio antes de quitar las etiquetas:
        // sin esto, `<p>uno</p><p>dos</p>` daría «unodos», una palabra que nadie
        // escribió.
        $texto = preg_replace('/<(p|br|div|li|h2|h3|blockquote|pre)\b[^>]*>/i', ' ', $valor);
        $texto = strip_tags($texto);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return mb_strlen(trim(preg_replace('/\s+/u', ' ', $texto)));
    }
}

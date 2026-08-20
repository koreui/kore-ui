<?php

namespace KoreUi\Core\Support;

/**
 * Ids deterministas para los componentes que se inventan el suyo.
 *
 * **Por qué existe.** Un campo sin `name` ni `wire:model`, o un item de acordeón
 * sin `id`, no tiene de dónde sacarlo, y hasta ahora se le daba con `uniqid()`.
 * Eso significa un id nuevo en cada render, y con Livewire de por medio deja de
 * ser un detalle estético:
 *
 * - Los componentes con estado en el cliente —select, datepicker, input-otp,
 *   tag-input, repeater, key-value, time-picker— llevan `wire:ignore` en su
 *   raíz para que un re-render ajeno no borre lo que el usuario estaba
 *   haciendo. Pero la etiqueta del campo vive FUERA de esa raíz, así que el
 *   morph sí la actualiza: la etiqueta estrenaba un `for` nuevo mientras el
 *   control, congelado, conservaba el viejo. Etiqueta huérfana y campo sin
 *   nombre accesible, a partir del segundo render y solo entonces.
 * - Un id distinto en cada render hace además que el morph reemplace el nodo en
 *   vez de actualizarlo, con lo que eso cuesta.
 *
 * El contador es `scoped()`, no `singleton()`: con Octane el contenedor se
 * reutiliza entre peticiones y el contador tiene que empezar en 1 en cada una,
 * que es justo lo que lo hace determinista. Mismo motivo que `ChartContext`.
 *
 * **Límite conocido.** Dos componentes Livewire distintos en la misma página
 * numeran cada uno desde 1 en su propia petición de actualización, así que sus
 * ids podrían chocar. Por eso el prefijo incluye el id del componente cuando lo
 * hay: dentro de Livewire los ids quedan acotados a su componente.
 */
final class IdContext
{
    private int $contador = 0;

    /** @var array<string, int> */
    private array $porAmbito = [];

    /**
     * Un id con prefijo propio, para lo que no es un campo.
     *
     * `IdContext::secuencia('accordion')` → `accordion-1`, `accordion-2`… Mismo
     * contador y mismo ámbito que `para()`, para que dos componentes distintos
     * de la misma página no puedan chocar.
     */
    public static function secuencia(string $prefijo): string
    {
        return $prefijo.'-'.str_replace('kore-', '', app(self::class)->nextId(static::ambitoActual()));
    }

    /**
     * El id de un campo, ya venga del `name` o del contador.
     *
     * Es lo que llaman las vistas: un solo punto, para que los treinta y tantos
     * componentes de formulario no repitan la misma expresión —ni se
     * desincronicen cuando cambie.
     */
    public static function para(?string $name): string
    {
        if ($name !== null && $name !== '') {
            return 'kore-'.str_replace(['.', '[', ']'], ['-', '-', ''], $name);
        }

        return app(self::class)->nextId(static::ambitoActual());
    }

    /**
     * El componente Livewire que se está renderizando, si lo hay.
     *
     * Acota el contador a su componente: dos componentes en la misma página
     * numeran cada uno desde 1 en su propia petición de actualización, y sin
     * este prefijo sus ids chocarían.
     */
    private static function ambitoActual(): ?string
    {
        if (! class_exists(\Livewire\Livewire::class)) {
            return null;
        }

        try {
            return \Livewire\Livewire::current()?->getId();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Siguiente id de campo.
     *
     * @param  string|null  $ambito  Id del componente Livewire, si se renderiza dentro de uno.
     */
    public function nextId(?string $ambito = null): string
    {
        if ($ambito === null) {
            return 'kore-f'.(++$this->contador);
        }

        $this->porAmbito[$ambito] = ($this->porAmbito[$ambito] ?? 0) + 1;

        return 'kore-'.$ambito.'-f'.$this->porAmbito[$ambito];
    }
}

<?php

namespace KoreUi\DataTable\Filters\Concerns;

use ArrayAccess;

/**
 * Extrae de `options()` la lista de valores aceptables, para que un filtro con
 * opciones declaradas no acepte cualquier cosa que llegue del navegador.
 *
 * Soporta los dos formatos que admite `options()`:
 *   ['activo' => 'Activo', 'baja' => 'De baja']
 *   [['label' => 'Activo', 'value' => 'activo'], …]
 */
trait HasOptionWhitelist
{
    /**
     * Devuelve null cuando no hay lista contra la que contrastar: opciones
     * cargadas dinámicamente o filtro con callback propio. En ese caso se acepta
     * cualquier escalar y la validación recae en la consulta.
     *
     * @return array<int, string>|null
     */
    protected function allowedValues(): ?array
    {
        if ($this->options === []) {
            return null;
        }

        $valueKey = $this->optionValue ?? 'value';
        $allowed  = [];

        foreach ($this->options as $key => $option) {
            if (is_array($option) || $option instanceof ArrayAccess) {
                if (isset($option[$valueKey])) {
                    $allowed[] = (string) $option[$valueKey];
                }

                continue;
            }

            // Formato ['valor' => 'Etiqueta']: la clave es el valor.
            $allowed[] = (string) $key;
        }

        return $allowed === [] ? null : $allowed;
    }
}

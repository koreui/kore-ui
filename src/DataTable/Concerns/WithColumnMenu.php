<?php

namespace KoreUi\DataTable\Concerns;

use KoreUi\DataTable\Columns\Column;

/**
 * Menú por cabecera de columna: ordenar, fijar y ocultar desde la propia
 * columna.
 *
 * Es lo que convierte `pinned()` y el selector de columnas en algo del usuario
 * final. Hasta ahora fijar una columna era una decisión que solo podía tomar
 * quien escribía la tabla.
 */
trait WithColumnMenu
{
    /**
     * Fijados elegidos por el usuario: campo → 'left' | 'right' | '' (soltada).
     *
     * La cadena vacía es significativa: distingue "el usuario soltó esta
     * columna" de "el usuario no ha tocado esta columna", que es lo que decide
     * si gana el estado o la definición de la tabla.
     */
    public array $columnPins = [];

    protected bool $columnMenuEnabled = true;

    protected function applyColumnMenuConfig(): void
    {
        $this->columnMenuEnabled = (bool) config('kore-ui.datatable.column_menu', true);
        $this->columnPins = session($this->getColumnPinsSessionKey(), []);
    }

    public function isColumnMenuEnabled(): bool
    {
        return $this->columnMenuEnabled;
    }

    public function setColumnMenuEnabled(bool $enabled): static
    {
        $this->columnMenuEnabled = $enabled;

        return $this;
    }

    /**
     * Fija una columna a un lado, o la suelta si ya estaba en ese lado.
     */
    public function toggleColumnPin(string $field, string $side = 'left'): void
    {
        if (! $this->isKnownColumn($field)) {
            return;
        }

        $side = in_array($side, ['left', 'right'], true) ? $side : 'left';

        $this->columnPins[$field] = ($this->effectivePin($field) === $side) ? '' : $side;

        session()->put($this->getColumnPinsSessionKey(), $this->columnPins);
    }

    /**
     * Devuelve a todas las columnas el estado de fijado que declara la tabla.
     */
    public function resetColumnPins(): void
    {
        $this->columnPins = [];
        session()->forget($this->getColumnPinsSessionKey());
    }

    /**
     * Ordena de forma explícita, sin el ciclo asc → desc → ninguno de sortBy().
     *
     * El menú ofrece "ascendente" y "descendente" como acciones directas, así
     * que necesita fijar la dirección, no rotarla.
     */
    public function setSort(string $field, string $direction = 'asc'): void
    {
        if (! in_array($field, $this->getSortableFields(), true)) {
            return;
        }

        $this->sorts = [$field => $direction === 'desc' ? 'desc' : 'asc'];

        $this->resetPage();
    }

    /**
     * Fijado efectivo de una columna: manda lo que eligió el usuario y, si no ha
     * tocado nada, lo que declara la tabla.
     */
    public function effectivePin(string $field): ?string
    {
        if (! array_key_exists($field, $this->columnPins)) {
            return null;
        }

        return $this->columnPins[$field] === '' ? null : $this->columnPins[$field];
    }

    public function hasCustomPins(): bool
    {
        return $this->columnPins !== [];
    }

    /**
     * Vuelca los fijados del usuario sobre los objetos Column.
     *
     * Se aplica sobre las instancias, que viven lo que dura la petición, para
     * que ni las vistas ni el cálculo de offsets tengan que saber que existe un
     * estado por usuario: siguen preguntando `isPinned()`.
     */
    protected function applyColumnPins(array $columns): array
    {
        if ($this->columnPins === []) {
            return $columns;
        }

        foreach ($columns as $column) {
            $field = $column->getField();

            if (array_key_exists($field, $this->columnPins)) {
                $column->pinned($this->columnPins[$field] === '' ? null : $this->columnPins[$field]);
            }
        }

        return $columns;
    }

    protected function isKnownColumn(string $field): bool
    {
        return collect($this->cachedColumns())
            ->contains(fn (Column $column) => $column->getField() === $field);
    }

    protected function getColumnPinsSessionKey(): string
    {
        $prefix = $this->tablePrefix();

        return 'kore-datatable-pins:' . static::class . ($prefix === '' ? '' : ':' . $prefix);
    }
}

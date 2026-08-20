<?php

namespace KoreUi\DataTable\Filters;

use Illuminate\Database\Eloquent\Builder;

class NumberFilter extends Filter
{
    protected ?float $min = null;

    protected ?float $max = null;

    protected ?float $step = null;

    protected string $operator = '=';

    public function sanitize(mixed $value): mixed
    {
        // En PostgreSQL comparar una columna numérica con texto es un error de
        // SQL, no una comparación vacía: sin esta coerción, un valor cualquiera
        // desde el navegador es un 500.
        return is_numeric($value) ? $value + 0 : null;
    }

    public function apply(Builder $query, mixed $value): Builder
    {
        $operator = $this->normalizedOperator();

        return $this->applyOnColumn(
            $query,
            fn (Builder $q, string $column) => $q->where($column, $operator, $value),
        );
    }

    /**
     * El operador lo fija quien construye la tabla, no el cliente, pero se acota
     * igualmente a la lista conocida: un typo en `operator()` produciría SQL
     * inválido en vez de un filtro que simplemente no compara como se esperaba.
     */
    protected function normalizedOperator(): string
    {
        return in_array($this->operator, ['=', '!=', '<>', '>', '>=', '<', '<='], true)
            ? $this->operator
            : '=';
    }

    public function getType(): string
    {
        return 'number';
    }

    public function getComponentProps(): array
    {
        return [
            'placeholder' => $this->placeholder ?? $this->label,
            'min'         => $this->min,
            'max'         => $this->max,
            'step'        => $this->step,
        ];
    }

    public function hasValue(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    public function min(float $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function max(float $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function step(float $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function operator(string $operator): static
    {
        $this->operator = $operator;

        return $this;
    }

    public function getMin(): ?float
    {
        return $this->min;
    }

    public function getMax(): ?float
    {
        return $this->max;
    }

    public function getStep(): ?float
    {
        return $this->step;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }
}

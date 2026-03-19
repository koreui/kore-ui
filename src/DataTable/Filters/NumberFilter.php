<?php

namespace KoreUi\DataTable\Filters;

use Illuminate\Database\Eloquent\Builder;

class NumberFilter extends Filter
{
    protected ?float $min = null;

    protected ?float $max = null;

    protected ?float $step = null;

    protected string $operator = '=';

    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where($this->column, $this->operator, $value);
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

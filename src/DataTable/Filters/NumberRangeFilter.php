<?php

namespace KoreUi\DataTable\Filters;

use Illuminate\Database\Eloquent\Builder;

class NumberRangeFilter extends Filter
{
    protected ?float $min = null;

    protected ?float $max = null;

    protected ?float $step = null;

    public function apply(Builder $query, mixed $value): Builder
    {
        if (is_array($value)) {
            $min = $value['min'] ?? null;
            $max = $value['max'] ?? null;

            if ($min !== null && $min !== '') {
                $query->where($this->column, '>=', $min);
            }

            if ($max !== null && $max !== '') {
                $query->where($this->column, '<=', $max);
            }
        }

        return $query;
    }

    public function getType(): string
    {
        return 'number-range';
    }

    public function getComponentProps(): array
    {
        return [
            'min'  => $this->min,
            'max'  => $this->max,
            'step' => $this->step,
        ];
    }

    public function hasValue(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        $min = $value['min'] ?? null;
        $max = $value['max'] ?? null;

        return ($min !== null && $min !== '') || ($max !== null && $max !== '');
    }

    public function getPillText(mixed $value): ?string
    {
        if (! $this->hasValue($value)) {
            return null;
        }

        if ($this->pillCallback !== null) {
            return ($this->pillCallback)($value);
        }

        $min = $value['min'] ?? null;
        $max = $value['max'] ?? null;

        if ($min !== null && $min !== '' && $max !== null && $max !== '') {
            return $this->label.': '.$min.' — '.$max;
        }

        if ($min !== null && $min !== '') {
            return $this->label.': >= '.$min;
        }

        return $this->label.': <= '.$max;
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
}

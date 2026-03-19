<?php

namespace KoreUi\DataTable\Filters;

use Illuminate\Database\Eloquent\Builder;

class TextFilter extends Filter
{
    protected int $debounce = 300;

    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->where($this->column, 'like', "%{$value}%");
    }

    public function getType(): string
    {
        return 'text';
    }

    public function getComponentProps(): array
    {
        return [
            'placeholder' => $this->placeholder ?? $this->label,
            'debounce'    => $this->debounce,
        ];
    }

    public function debounce(int $milliseconds): static
    {
        $this->debounce = $milliseconds;

        return $this;
    }

    public function getDebounce(): int
    {
        return $this->debounce;
    }
}

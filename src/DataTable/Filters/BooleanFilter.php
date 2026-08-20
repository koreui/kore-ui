<?php

namespace KoreUi\DataTable\Filters;

use Illuminate\Database\Eloquent\Builder;

class BooleanFilter extends Filter
{
    protected string $trueLabel = 'Sí';

    protected string $falseLabel = 'No';

    protected string $allLabel = 'Todos';

    public function sanitize(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_scalar($value)) {
            return null;
        }

        // filter_var entiende '0'/'1'/'true'/'false'/'on'; el cast directo daba
        // true para la cadena 'false'.
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
    }

    public function apply(Builder $query, mixed $value): Builder
    {
        if ($value === '' || $value === null) {
            return $query;
        }

        $flag = (bool) $value;

        return $this->applyOnColumn(
            $query,
            fn (Builder $q, string $column) => $q->where($column, $flag),
        );
    }

    public function getType(): string
    {
        return 'boolean';
    }

    public function getComponentProps(): array
    {
        return [
            'placeholder' => $this->placeholder ?? $this->label,
            'options'      => [
                ['label' => $this->allLabel, 'value' => ''],
                ['label' => $this->trueLabel, 'value' => '1'],
                ['label' => $this->falseLabel, 'value' => '0'],
            ],
            'option-label' => 'label',
            'option-value' => 'value',
        ];
    }

    public function hasValue(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    public function getPillText(mixed $value): ?string
    {
        if (! $this->hasValue($value)) {
            return null;
        }

        if ($this->pillCallback !== null) {
            return ($this->pillCallback)($value);
        }

        $text = $value ? $this->trueLabel : $this->falseLabel;

        return $this->label.': '.$text;
    }

    public function trueLabel(string $label): static
    {
        $this->trueLabel = $label;

        return $this;
    }

    public function falseLabel(string $label): static
    {
        $this->falseLabel = $label;

        return $this;
    }

    public function allLabel(string $label): static
    {
        $this->allLabel = $label;

        return $this;
    }

    public function getTrueLabel(): string
    {
        return $this->trueLabel;
    }

    public function getFalseLabel(): string
    {
        return $this->falseLabel;
    }

    public function getAllLabel(): string
    {
        return $this->allLabel;
    }
}

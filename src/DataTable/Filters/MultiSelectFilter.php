<?php

namespace KoreUi\DataTable\Filters;

use Illuminate\Database\Eloquent\Builder;

class MultiSelectFilter extends Filter
{
    protected array $options = [];

    protected ?string $optionLabel = null;

    protected ?string $optionValue = null;

    protected ?int $max = null;

    protected bool $searchable = false;

    public function apply(Builder $query, mixed $value): Builder
    {
        return $query->whereIn($this->column, (array) $value);
    }

    public function getType(): string
    {
        return 'multi-select';
    }

    public function getComponentProps(): array
    {
        return [
            'placeholder'  => $this->placeholder ?? $this->label,
            'options'       => $this->options,
            'option-label'  => $this->optionLabel,
            'option-value'  => $this->optionValue,
            'multiple'      => true,
            'max'           => $this->max,
            'searchable'    => $this->searchable,
        ];
    }

    public function hasValue(mixed $value): bool
    {
        return is_array($value) && count($value) > 0;
    }

    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function optionLabel(string $label): static
    {
        $this->optionLabel = $label;

        return $this;
    }

    public function optionValue(string $value): static
    {
        $this->optionValue = $value;

        return $this;
    }

    public function max(int $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function searchable(bool $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }
}

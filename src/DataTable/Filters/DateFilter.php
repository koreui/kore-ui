<?php

namespace KoreUi\DataTable\Filters;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Filters\Concerns\NormalizesDates;

class DateFilter extends Filter
{
    use NormalizesDates;

    protected ?string $minDate = null;

    protected ?string $maxDate = null;

    public function sanitize(mixed $value): mixed
    {
        return $this->normalizeDate($value);
    }

    public function apply(Builder $query, mixed $value): Builder
    {
        return $this->applyOnColumn(
            $query,
            fn (Builder $q, string $column) => $q->whereDate($column, $value),
        );
    }

    public function getType(): string
    {
        return 'date';
    }

    public function getComponentProps(): array
    {
        return [
            'placeholder' => $this->placeholder ?? $this->label,
            'min-date'    => $this->minDate,
            'max-date'    => $this->maxDate,
        ];
    }

    public function minDate(string $date): static
    {
        $this->minDate = $date;

        return $this;
    }

    public function maxDate(string $date): static
    {
        $this->maxDate = $date;

        return $this;
    }

    public function getMinDate(): ?string
    {
        return $this->minDate;
    }

    public function getMaxDate(): ?string
    {
        return $this->maxDate;
    }
}

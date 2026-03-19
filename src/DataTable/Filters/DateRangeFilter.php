<?php

namespace KoreUi\DataTable\Filters;

use Illuminate\Database\Eloquent\Builder;

class DateRangeFilter extends Filter
{
    protected ?string $minDate = null;

    protected ?string $maxDate = null;

    public function apply(Builder $query, mixed $value): Builder
    {
        if (is_array($value)) {
            $start = $value['start'] ?? $value[0] ?? null;
            $end = $value['end'] ?? $value[1] ?? null;

            if ($start) {
                $query->whereDate($this->column, '>=', $start);
            }

            if ($end) {
                $query->whereDate($this->column, '<=', $end);
            }
        }

        return $query;
    }

    public function getType(): string
    {
        return 'date-range';
    }

    public function getComponentProps(): array
    {
        return [
            'placeholder' => $this->placeholder ?? $this->label,
            'mode'        => 'range',
            'min-date'    => $this->minDate,
            'max-date'    => $this->maxDate,
        ];
    }

    public function hasValue(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        $start = $value['start'] ?? $value[0] ?? null;
        $end = $value['end'] ?? $value[1] ?? null;

        return $start !== null || $end !== null;
    }

    public function getPillText(mixed $value): ?string
    {
        if (! $this->hasValue($value)) {
            return null;
        }

        if ($this->pillCallback !== null) {
            return ($this->pillCallback)($value);
        }

        $start = $value['start'] ?? $value[0] ?? null;
        $end = $value['end'] ?? $value[1] ?? null;

        if ($start && $end) {
            return $this->label.': '.$start.' — '.$end;
        }

        if ($start) {
            return $this->label.': desde '.$start;
        }

        return $this->label.': hasta '.$end;
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

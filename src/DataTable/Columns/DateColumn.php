<?php

namespace KoreUi\DataTable\Columns;

use Carbon\Carbon;

class DateColumn extends Column
{
    protected string $dateFormat = 'd/m/Y';

    protected ?string $timezone = null;

    protected bool $diffForHumans = false;

    protected ?string $tooltipFormat = null;

    public function dateFormat(string $format): static
    {
        $this->dateFormat = $format;

        return $this;
    }

    public function timezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function diffForHumans(bool $condition = true): static
    {
        $this->diffForHumans = $condition;

        return $this;
    }

    public function tooltipFormat(string $format): static
    {
        $this->tooltipFormat = $format;

        return $this;
    }

    public function getValue(mixed $row): mixed
    {
        $value = data_get($row, $this->field, $this->default);

        if ($this->formatCallback !== null) {
            return ($this->formatCallback)($value, $row);
        }

        if ($value === null) {
            return $this->default;
        }

        $date = $value instanceof Carbon ? $value : Carbon::parse($value);

        if ($this->timezone) {
            $date = $date->timezone($this->timezone);
        }

        if ($this->diffForHumans) {
            return $date->diffForHumans();
        }

        return $date->format($this->dateFormat);
    }

    public function getTooltipValue(mixed $row): ?string
    {
        if (! $this->tooltipFormat) {
            return null;
        }

        $value = data_get($row, $this->field);

        if ($value === null) {
            return null;
        }

        $date = $value instanceof Carbon ? $value : Carbon::parse($value);

        if ($this->timezone) {
            $date = $date->timezone($this->timezone);
        }

        return $date->format($this->tooltipFormat);
    }

    public function getType(): string
    {
        return 'date';
    }

    public function getComponentProps(): array
    {
        return array_filter([
            'tooltipFormat' => $this->tooltipFormat,
        ], fn ($v) => $v !== null);
    }
}

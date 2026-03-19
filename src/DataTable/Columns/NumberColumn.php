<?php

namespace KoreUi\DataTable\Columns;

use NumberFormatter;

class NumberColumn extends Column
{
    protected int $decimals = 0;

    protected string $decimalSeparator = '.';

    protected string $thousandsSeparator = ',';

    protected ?string $prefix = null;

    protected ?string $suffix = null;

    protected ?string $locale = null;

    protected ?string $currency = null;

    public function __construct(string $label, string $field)
    {
        parent::__construct($label, $field);
        $this->align = 'right';
    }

    public function decimals(int $decimals): static
    {
        $this->decimals = $decimals;

        return $this;
    }

    public function separators(string $decimal = '.', string $thousands = ','): static
    {
        $this->decimalSeparator = $decimal;
        $this->thousandsSeparator = $thousands;

        return $this;
    }

    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function locale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function money(string $currency = 'USD', ?string $locale = null): static
    {
        $this->currency = $currency;
        $this->locale = $locale ?? app()->getLocale();

        return $this;
    }

    public function editable(bool $editable = true): static
    {
        parent::editable($editable);

        if ($editable) {
            $this->editableInputType = 'number';
        }

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

        $numericValue = (float) $value;

        if ($this->currency && $this->locale) {
            $formatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);

            return $formatter->formatCurrency($numericValue, $this->currency);
        }

        if ($this->locale) {
            $formatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $this->decimals);

            return ($this->prefix ?? '') . $formatter->format($numericValue) . ($this->suffix ?? '');
        }

        $formatted = number_format($numericValue, $this->decimals, $this->decimalSeparator, $this->thousandsSeparator);

        return ($this->prefix ?? '') . $formatted . ($this->suffix ?? '');
    }

    public function formatAggregationValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $numericValue = (float) $value;

        if ($this->currency && $this->locale) {
            $formatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);

            return $formatter->formatCurrency($numericValue, $this->currency);
        }

        if ($this->locale) {
            $formatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $this->aggregationDecimals ?? $this->decimals);

            return ($this->prefix ?? '').$formatter->format($numericValue).($this->suffix ?? '');
        }

        $formatted = number_format($numericValue, $this->aggregationDecimals ?? $this->decimals, $this->decimalSeparator, $this->thousandsSeparator);

        return ($this->prefix ?? '').$formatted.($this->suffix ?? '');
    }

    public function getType(): string
    {
        return 'number';
    }

    public function getComponentProps(): array
    {
        return [];
    }
}

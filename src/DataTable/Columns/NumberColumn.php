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

        // data_get() devuelve el default en cuanto el atributo es null, porque
        // resuelve objetos con isset(). Así que aquí puede llegar un marcador de
        // texto —'—', 'N/D'— y castearlo a float lo convertiría en un 0 que
        // parece un dato real. Peor que la celda vacía que se quería evitar.
        if (! is_numeric($value)) {
            return $value;
        }

        return $this->formatNumber((float) $value, $this->decimals);
    }

    public function formatAggregationValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return $this->formatNumber((float) $value, $this->aggregationDecimals ?? $this->decimals);
    }

    /**
     * Punto único de formato para celdas y agregaciones.
     *
     * locale()/money() usan NumberFormatter, que vive en ext-intl. La extensión
     * no viene activada por defecto en las imágenes PHP oficiales y el paquete
     * solo la sugiere, así que aquí se degrada a number_format() en vez de
     * lanzar "Class NumberFormatter not found" en mitad de un render.
     */
    protected function formatNumber(float $value, int $decimals): string
    {
        $hasIntl = class_exists(NumberFormatter::class);

        if ($this->currency && $this->locale && $hasIntl) {
            return (new NumberFormatter($this->locale, NumberFormatter::CURRENCY))
                ->formatCurrency($value, $this->currency);
        }

        if ($this->locale && $hasIntl) {
            $formatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

            return ($this->prefix ?? '') . $formatter->format($value) . ($this->suffix ?? '');
        }

        // Fallback sin intl. Con money() se antepone el código de moneda, que es
        // menos bonito que el símbolo localizado pero no pierde información.
        $formatted = number_format($value, $decimals, $this->decimalSeparator, $this->thousandsSeparator);
        $prefix    = $this->prefix ?? ($this->currency && ! $hasIntl ? $this->currency . ' ' : '');

        return $prefix . $formatted . ($this->suffix ?? '');
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

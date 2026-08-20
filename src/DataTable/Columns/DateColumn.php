<?php

namespace KoreUi\DataTable\Columns;

use Carbon\Carbon;
use Throwable;

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

        $date = $this->toCarbon($value);

        // Una sola fila con '0000-00-00', una cadena vacía en una columna string
        // o cualquier dato heredado sucio hacía saltar Carbon y tumbaba el render
        // de la tabla entera. Se devuelve el valor tal cual: la celda queda fea,
        // pero el dato es visible y la página funciona.
        if ($date === null) {
            return is_scalar($value) ? (string) $value : $this->default;
        }

        if ($this->diffForHumans) {
            return $date->diffForHumans();
        }

        return $date->format($this->dateFormat);
    }

    /**
     * Convierte a Carbon aplicando la zona horaria, o null si el valor no es
     * una fecha reconocible.
     */
    protected function toCarbon(mixed $value): ?Carbon
    {
        try {
            $date = $value instanceof Carbon ? $value : Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }

        return $this->timezone ? $date->timezone($this->timezone) : $date;
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

        $date = $this->toCarbon($value);

        return $date?->format($this->tooltipFormat);
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

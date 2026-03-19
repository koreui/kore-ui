<?php

namespace KoreUi\DataTable\Columns\Concerns;

use Closure;

trait HasAggregation
{
    protected ?string $aggregationType = null;

    protected ?Closure $footerCallback = null;

    protected ?string $footerLabel = null;

    protected int $aggregationDecimals = 2;

    public function sum(string $label = 'Total'): static
    {
        $this->aggregationType = 'sum';
        $this->footerLabel = $label;

        return $this;
    }

    public function avg(int $decimals = 2, string $label = 'Promedio'): static
    {
        $this->aggregationType = 'avg';
        $this->aggregationDecimals = $decimals;
        $this->footerLabel = $label;

        return $this;
    }

    public function count(string $label = 'Total'): static
    {
        $this->aggregationType = 'count';
        $this->footerLabel = $label;

        return $this;
    }

    public function footerMin(string $label = 'Mín'): static
    {
        $this->aggregationType = 'min';
        $this->footerLabel = $label;

        return $this;
    }

    public function footerMax(string $label = 'Máx'): static
    {
        $this->aggregationType = 'max';
        $this->footerLabel = $label;

        return $this;
    }

    public function footer(Closure $callback): static
    {
        $this->footerCallback = $callback;

        return $this;
    }

    public function footerLabel(string $label): static
    {
        $this->footerLabel = $label;

        return $this;
    }

    public function hasAggregation(): bool
    {
        return $this->aggregationType !== null || $this->footerCallback !== null;
    }

    public function getAggregationType(): ?string
    {
        return $this->aggregationType;
    }

    public function getAggregationDecimals(): int
    {
        return $this->aggregationDecimals;
    }

    public function getFooterCallback(): ?Closure
    {
        return $this->footerCallback;
    }

    public function getFooterLabel(): ?string
    {
        return $this->footerLabel;
    }

    /**
     * Format the aggregation value for display.
     * Override in column subclasses (e.g. NumberColumn) for custom formatting.
     */
    public function formatAggregationValue(mixed $value): string
    {
        return (string) $value;
    }
}

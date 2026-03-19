<?php

namespace KoreUi\DataTable\Columns;

class ColorColumn extends Column
{
    protected bool $showLabel = true;

    protected string $swatchSize = 'md';

    protected string $swatchShape = 'rounded';

    protected bool $copyable = false;

    public function showLabel(bool $condition = true): static
    {
        $this->showLabel = $condition;

        return $this;
    }

    public function swatchSize(string $size): static
    {
        $this->swatchSize = $size;

        return $this;
    }

    public function swatchShape(string $shape): static
    {
        $this->swatchShape = $shape;

        return $this;
    }

    public function copyable(bool $condition = true): static
    {
        $this->copyable = $condition;

        return $this;
    }

    public function getType(): string
    {
        return 'color';
    }

    public function getComponentProps(): array
    {
        return [
            'showLabel'   => $this->showLabel,
            'swatchSize'  => $this->swatchSize,
            'swatchShape' => $this->swatchShape,
            'copyable'    => $this->copyable,
        ];
    }
}

<?php

namespace KoreUi\DataTable\Columns;

class ProgressColumn extends Column
{
    protected int $max = 100;

    protected string $size = 'md';

    protected string $color = 'auto';

    protected bool $showValue = true;

    protected bool $circle = false;

    protected bool $striped = false;

    public function max(int $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function size(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function showValue(bool $condition = true): static
    {
        $this->showValue = $condition;

        return $this;
    }

    public function circle(bool $condition = true): static
    {
        $this->circle = $condition;

        return $this;
    }

    public function striped(bool $condition = true): static
    {
        $this->striped = $condition;

        return $this;
    }

    public function getType(): string
    {
        return 'progress';
    }

    public function getComponentProps(): array
    {
        return [
            'max'       => $this->max,
            'size'      => $this->size,
            'color'     => $this->color,
            'showValue' => $this->showValue,
            'circle'    => $this->circle,
            'striped'   => $this->striped,
        ];
    }
}

<?php

namespace KoreUi\DataTable\Columns;

class BooleanColumn extends Column
{
    protected ?string $trueIcon = null;

    protected ?string $falseIcon = null;

    protected ?string $trueColor = null;

    protected ?string $falseColor = null;

    protected string $size = 'md';

    public function __construct(string $label, string $field)
    {
        parent::__construct($label, $field);
        $this->align = 'center';
    }

    public function trueIcon(string $icon): static
    {
        $this->trueIcon = $icon;

        return $this;
    }

    public function falseIcon(string $icon): static
    {
        $this->falseIcon = $icon;

        return $this;
    }

    public function trueColor(string $color): static
    {
        $this->trueColor = $color;

        return $this;
    }

    public function falseColor(string $color): static
    {
        $this->falseColor = $color;

        return $this;
    }

    public function size(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getType(): string
    {
        return 'boolean';
    }

    public function editable(bool $editable = true): static
    {
        parent::editable($editable);

        if ($editable) {
            $this->editableComponent = 'toggle';
        }

        return $this;
    }

    public function getComponentProps(): array
    {
        return array_filter([
            'trueIcon'  => $this->trueIcon,
            'falseIcon' => $this->falseIcon,
            'trueColor' => $this->trueColor,
            'falseColor' => $this->falseColor,
            'size'      => $this->size,
        ], fn ($v) => $v !== null);
    }
}

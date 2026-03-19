<?php

namespace KoreUi\DataTable\Columns;

class ImageColumn extends Column
{
    protected string $size = 'sm';

    protected string $shape = 'circle';

    protected bool $isAvatar = true;

    protected ?string $nameField = null;

    public function __construct(string $label, string $field)
    {
        parent::__construct($label, $field);
        $this->align = 'center';
    }

    public function size(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function shape(string $shape): static
    {
        $this->shape = $shape;

        return $this;
    }

    public function isAvatar(bool $condition = true): static
    {
        $this->isAvatar = $condition;

        return $this;
    }

    public function nameField(string $field): static
    {
        $this->nameField = $field;

        return $this;
    }

    public function getNameValue(mixed $row): ?string
    {
        if (! $this->nameField) {
            return null;
        }

        return data_get($row, $this->nameField);
    }

    public function getType(): string
    {
        return 'image';
    }

    public function getComponentProps(): array
    {
        return [
            'size'     => $this->size,
            'shape'    => $this->shape,
            'isAvatar' => $this->isAvatar,
        ];
    }
}

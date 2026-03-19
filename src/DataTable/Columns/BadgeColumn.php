<?php

namespace KoreUi\DataTable\Columns;

class BadgeColumn extends Column
{
    protected array $colors = [];

    protected array $icons = [];

    protected ?string $variant = null;

    protected string $size = 'md';

    public function colors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    public function icons(array $icons): static
    {
        $this->icons = $icons;

        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function size(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function resolveColor(mixed $value): string
    {
        $stringValue = (string) $value;

        // Direct mapping: ['admin' => 'primary', 'user' => 'info']
        if (isset($this->colors[$stringValue])) {
            return $this->colors[$stringValue];
        }

        return 'muted';
    }

    public function resolveIcon(mixed $value): ?string
    {
        $stringValue = (string) $value;

        return $this->icons[$stringValue] ?? null;
    }

    public function getType(): string
    {
        return 'badge';
    }

    public function getComponentProps(): array
    {
        return array_filter([
            'variant' => $this->variant,
            'size'    => $this->size,
        ], fn ($v) => $v !== null);
    }
}

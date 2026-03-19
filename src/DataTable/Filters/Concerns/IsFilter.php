<?php

namespace KoreUi\DataTable\Filters\Concerns;

use Closure;

trait IsFilter
{
    protected string $label;

    protected string $column;

    protected mixed $default = null;

    protected bool $hidden = false;

    protected ?string $placeholder = null;

    protected ?int $position = null;

    protected ?Closure $pillCallback = null;

    protected ?Closure $callback = null;

    protected ?Closure $hiddenCallback = null;

    protected ?string $key = null;

    public function getKey(): string
    {
        return $this->key ?? str_replace('.', '_', $this->column);
    }

    public function key(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function getCallback(): ?Closure
    {
        return $this->callback;
    }

    public function isHidden(): bool
    {
        if ($this->hiddenCallback !== null) {
            return (bool) ($this->hiddenCallback)();
        }

        return $this->hidden;
    }

    public function hasValue(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === []) {
            return false;
        }

        return true;
    }

    public function getPillText(mixed $value): ?string
    {
        if (! $this->hasValue($value)) {
            return null;
        }

        if ($this->pillCallback !== null) {
            return ($this->pillCallback)($value);
        }

        if (is_array($value)) {
            return $this->label.': '.implode(', ', $value);
        }

        return $this->label.': '.$value;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function hidden(bool $condition = true): static
    {
        $this->hidden = $condition;

        return $this;
    }

    public function hiddenIf(Closure $callback): static
    {
        $this->hiddenCallback = $callback;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function position(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function pill(Closure $callback): static
    {
        $this->pillCallback = $callback;

        return $this;
    }

    public function callback(Closure $callback): static
    {
        $this->callback = $callback;

        return $this;
    }
}

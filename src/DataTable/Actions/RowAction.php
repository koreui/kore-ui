<?php

namespace KoreUi\DataTable\Actions;

use Closure;

class RowAction
{
    protected string $identifier;

    protected string $label;

    protected ?string $icon = null;

    protected string $color = 'primary';

    protected ?string $urlPattern = null;

    protected ?Closure $urlCallback = null;

    protected ?string $wireMethod = null;

    protected ?string $confirmMessage = null;

    protected ?string $confirmDescription = null;

    protected bool $hidden = false;

    protected ?Closure $hiddenCallback = null;

    protected bool $separator = false;

    protected bool $openInNewTab = false;

    public function __construct(string $identifier, string $label)
    {
        $this->identifier = $identifier;
        $this->label = $label;
    }

    public static function make(string $identifier, string $label): static
    {
        return new static($identifier, $label);
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function urlPattern(string $pattern): static
    {
        $this->urlPattern = $pattern;

        return $this;
    }

    public function url(Closure $callback): static
    {
        $this->urlCallback = $callback;

        return $this;
    }

    public function wireMethod(string $method): static
    {
        $this->wireMethod = $method;

        return $this;
    }

    public function confirm(string $message, ?string $description = null): static
    {
        $this->confirmMessage = $message;
        $this->confirmDescription = $description;

        return $this;
    }

    public function hidden(bool|Closure $condition = true): static
    {
        if ($condition instanceof Closure) {
            $this->hiddenCallback = $condition;
        } else {
            $this->hidden = $condition;
        }

        return $this;
    }

    public function separator(bool $condition = true): static
    {
        $this->separator = $condition;

        return $this;
    }

    public function openInNewTab(bool $condition = true): static
    {
        $this->openInNewTab = $condition;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getWireMethod(): ?string
    {
        return $this->wireMethod;
    }

    public function hasConfirm(): bool
    {
        return $this->confirmMessage !== null;
    }

    public function getConfirmMessage(): ?string
    {
        return $this->confirmMessage;
    }

    public function getConfirmDescription(): ?string
    {
        return $this->confirmDescription;
    }

    public function hasSeparator(): bool
    {
        return $this->separator;
    }

    public function opensInNewTab(): bool
    {
        return $this->openInNewTab;
    }

    public function isHidden(mixed $row = null): bool
    {
        if ($this->hiddenCallback !== null) {
            return (bool) ($this->hiddenCallback)($row);
        }

        return $this->hidden;
    }

    public function hasUrl(): bool
    {
        return $this->urlPattern !== null || $this->urlCallback !== null;
    }

    public function getUrl(mixed $row): ?string
    {
        if ($this->urlCallback !== null) {
            return ($this->urlCallback)($row);
        }

        if ($this->urlPattern !== null) {
            return preg_replace_callback('/\{(\w+(?:\.\w+)*)\}/', function ($matches) use ($row) {
                return data_get($row, $matches[1], $matches[0]);
            }, $this->urlPattern);
        }

        return null;
    }
}

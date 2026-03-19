<?php

namespace KoreUi\DataTable\Columns;

use Closure;

class LinkColumn extends Column
{
    protected ?string $urlPattern = null;

    protected ?Closure $urlCallback = null;

    protected bool $openInNewTab = false;

    protected ?string $icon = null;

    protected string $color = 'primary';

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

    public function openInNewTab(bool $condition = true): static
    {
        $this->openInNewTab = $condition;

        return $this;
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

    public function getType(): string
    {
        return 'link';
    }

    public function getComponentProps(): array
    {
        return [
            'openInNewTab' => $this->openInNewTab,
            'icon'         => $this->icon,
            'color'        => $this->color,
        ];
    }
}

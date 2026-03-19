<?php

namespace KoreUi\DataTable\Presets;

use Closure;

class FilterPreset
{
    protected string $identifier;

    protected string $label;

    protected ?string $icon = null;

    protected array $filters = [];

    protected array $sorts = [];

    protected ?string $search = null;

    protected ?int $perPage = null;

    protected ?Closure $countCallback = null;

    protected bool $isDefault = false;

    protected bool $hidden = false;

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

    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function sorts(array $sorts): static
    {
        $this->sorts = $sorts;

        return $this;
    }

    public function search(string $search): static
    {
        $this->search = $search;

        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function count(Closure $callback): static
    {
        $this->countCallback = $callback;

        return $this;
    }

    public function default(bool $isDefault = true): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function hidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;

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

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getSorts(): array
    {
        return $this->sorts;
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function getPerPage(): ?int
    {
        return $this->perPage;
    }

    public function getCountCallback(): ?Closure
    {
        return $this->countCallback;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function hasFilters(): bool
    {
        return ! empty($this->filters);
    }

    public function hasSorts(): bool
    {
        return ! empty($this->sorts);
    }

    public function hasSearch(): bool
    {
        return $this->search !== null;
    }

    public function hasPerPage(): bool
    {
        return $this->perPage !== null;
    }

    public function hasCount(): bool
    {
        return $this->countCallback !== null;
    }
}

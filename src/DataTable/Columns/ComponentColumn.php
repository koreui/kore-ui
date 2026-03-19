<?php

namespace KoreUi\DataTable\Columns;

use Closure;

class ComponentColumn extends Column
{
    protected ?string $componentName = null;

    protected ?string $viewName = null;

    protected ?Closure $propsCallback = null;

    public function component(string $component): static
    {
        $this->componentName = $component;
        $this->viewName = null;

        return $this;
    }

    public function view(string $view): static
    {
        $this->viewName = $view;
        $this->componentName = null;

        return $this;
    }

    public function props(Closure $callback): static
    {
        $this->propsCallback = $callback;

        return $this;
    }

    public function getComponentName(): ?string
    {
        return $this->componentName ?? $this->viewName;
    }

    public function isBladeComponent(): bool
    {
        return $this->componentName !== null;
    }

    public function resolveProps(mixed $value, mixed $row): array
    {
        if ($this->propsCallback !== null) {
            return ($this->propsCallback)($value, $row);
        }

        return ['value' => $value, 'row' => $row];
    }

    public function getType(): string
    {
        return 'component';
    }

    public function getComponentProps(): array
    {
        return [];
    }
}

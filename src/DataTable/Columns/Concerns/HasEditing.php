<?php

namespace KoreUi\DataTable\Columns\Concerns;

use Closure;

trait HasEditing
{
    protected bool $editable = false;

    protected string $editableComponent = 'input';

    protected array $editableRules = [];

    protected ?Closure $editableCallback = null;

    protected array $editableOptions = [];

    protected string $editableInputType = 'text';

    public function editable(bool $editable = true): static
    {
        $this->editable = $editable;

        return $this;
    }

    public function editableComponent(string $component): static
    {
        $this->editableComponent = $component;

        return $this;
    }

    public function editableRules(array $rules): static
    {
        $this->editableRules = $rules;

        return $this;
    }

    public function editableCallback(Closure $callback): static
    {
        $this->editableCallback = $callback;

        return $this;
    }

    /**
     * Set options for select editable component.
     * Accepts: ['value' => 'label'] or [['label' => 'X', 'value' => 'Y']]
     */
    public function editableOptions(array $options): static
    {
        $this->editableComponent = 'select';
        $this->editableOptions = $options;

        return $this;
    }

    public function isEditable(): bool
    {
        return $this->editable;
    }

    public function getEditableComponent(): string
    {
        return $this->editableComponent;
    }

    public function getEditableRules(): array
    {
        return $this->editableRules;
    }

    public function getEditableCallback(): ?Closure
    {
        return $this->editableCallback;
    }

    public function getEditableOptions(): array
    {
        return $this->editableOptions;
    }

    public function editableInputType(string $type): static
    {
        $this->editableInputType = $type;

        return $this;
    }

    public function getEditableInputType(): string
    {
        return $this->editableInputType;
    }
}

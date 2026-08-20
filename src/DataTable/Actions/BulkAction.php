<?php

namespace KoreUi\DataTable\Actions;

use Closure;

class BulkAction
{
    protected string $identifier;

    protected string $label;

    protected ?string $icon = null;

    protected string $color = 'primary';

    protected ?string $confirmMessage = null;

    protected ?string $confirmDescription = null;

    protected bool $hidden = false;

    protected ?Closure $hiddenCallback = null;


    protected ?Closure $authorizeCallback = null;

    protected bool $separator = false;

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

    /**
     * Comprobación de permisos que se evalúa EN EL SERVIDOR justo antes de
     * ejecutar la acción.
     *
     * `hidden()` decide qué se pinta; esto decide qué se puede ejecutar. No son
     * lo mismo: todo método público de un componente Livewire es invocable desde
     * el navegador, así que una acción que solo se esconde de la interfaz sigue
     * estando al alcance de quien abra la consola.
     */
    public function authorize(Closure $callback): static
    {
        $this->authorizeCallback = $callback;

        return $this;
    }

    public function isAuthorized(): bool
    {
        if ($this->authorizeCallback === null) {
            return true;
        }

        return (bool) ($this->authorizeCallback)();
    }


    public function separator(bool $condition = true): static
    {
        $this->separator = $condition;

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

    public function getConfirmMessage(): ?string
    {
        return $this->confirmMessage;
    }

    public function getConfirmDescription(): ?string
    {
        return $this->confirmDescription;
    }

    public function hasConfirm(): bool
    {
        return $this->confirmMessage !== null;
    }

    public function isHidden(): bool
    {
        if ($this->hiddenCallback !== null) {
            return (bool) ($this->hiddenCallback)();
        }

        return $this->hidden;
    }


    public function hasSeparator(): bool
    {
        return $this->separator;
    }
}

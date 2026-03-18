<?php

namespace KoreUi\DataTable\Columns\Concerns;

use Closure;

trait HasVisibility
{
    protected bool $hidden = false;

    protected ?Closure $hiddenCallback = null;

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

    public function isHidden(): bool
    {
        if ($this->hiddenCallback !== null) {
            return (bool) ($this->hiddenCallback)();
        }

        return $this->hidden;
    }
}

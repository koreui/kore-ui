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

    protected bool $collapseOnMobile = false;

    protected bool $collapseOnTablet = false;

    public function collapseOnMobile(bool $condition = true): static
    {
        $this->collapseOnMobile = $condition;

        return $this;
    }

    public function collapseOnTablet(bool $condition = true): static
    {
        $this->collapseOnTablet = $condition;

        return $this;
    }

    public function isCollapsedOnMobile(): bool
    {
        return $this->collapseOnMobile;
    }

    public function isCollapsedOnTablet(): bool
    {
        return $this->collapseOnTablet;
    }

    public function isHidden(): bool
    {
        if ($this->hiddenCallback !== null) {
            return (bool) ($this->hiddenCallback)();
        }

        return $this->hidden;
    }

    // --- Column Pinning ---

    protected ?string $pinned = null;

    public function pinned(?string $side = 'left'): static
    {
        $this->pinned = $side;

        return $this;
    }

    public function isPinned(): bool
    {
        return $this->pinned !== null;
    }

    public function getPinnedSide(): ?string
    {
        return $this->pinned;
    }
}

<?php

namespace KoreUi\DataTable\Concerns;

use KoreUi\DataTable\Columns\Column;

trait WithResponsive
{
    protected string $responsiveMode = 'scroll';

    protected int $responsiveBreakpoint = 768;

    public function mountWithResponsive(): void
    {
        $this->responsiveMode = config('kore-ui.datatable.responsive_mode', 'scroll');
        $this->responsiveBreakpoint = (int) config('kore-ui.datatable.responsive_breakpoint', 768);
    }

    public function setResponsiveMode(string $mode): static
    {
        $this->responsiveMode = $mode;

        return $this;
    }

    public function setResponsiveBreakpoint(int $breakpoint): static
    {
        $this->responsiveBreakpoint = $breakpoint;

        return $this;
    }

    public function getResponsiveMode(): string
    {
        return $this->responsiveMode;
    }

    public function getResponsiveBreakpoint(): int
    {
        return $this->responsiveBreakpoint;
    }

    /**
     * Get columns that should be collapsed on mobile.
     *
     * @return Column[]
     */
    public function getCollapsedColumns(): array
    {
        return collect($this->columns())
            ->filter(fn (Column $col) => $col->isCollapsedOnMobile() || $col->isCollapsedOnTablet())
            ->values()
            ->all();
    }

    /**
     * Get columns that remain visible in collapse mode.
     *
     * @return Column[]
     */
    public function getVisibleColumnsForCollapse(): array
    {
        return collect($this->columns())
            ->reject(fn (Column $col) => $col->isHidden())
            ->reject(fn (Column $col) => $col->isCollapsedOnMobile())
            ->values()
            ->all();
    }
}

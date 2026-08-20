<?php

namespace KoreUi\DataTable\Concerns;

use KoreUi\DataTable\Columns\Column;
use Livewire\Attributes\Locked;

trait WithResponsive
{
    protected string $responsiveMode = 'scroll';

    protected int $responsiveBreakpoint = 768;

    /**
     * Ancho conocido del contenedor, que Alpine informa una sola vez tras el
     * primer pintado.
     *
     * En los modos `card` y `collapse` el servidor no sabe qué variante hace
     * falta, así que la primera carga emite las dos y el cliente esconde la que
     * sobra — es el único momento en que se paga el HTML duplicado. A partir de
     * ahí `$mobileView` está establecido y cada render manda solo la variante
     * que toca, que es donde de verdad se nota: paginar, filtrar y buscar.
     */
    #[Locked]
    public bool $viewportKnown = false;

    #[Locked]
    public bool $mobileView = false;

    /**
     * Punto de entrada desde Alpine. Solo se llama cuando el valor cambia.
     */
    public function setViewport(bool $isMobile): void
    {
        $this->viewportKnown = true;
        $this->mobileView    = $isMobile;
    }

    /**
     * Si esta petición debe emitir la tabla completa.
     */
    public function shouldRenderTable(): bool
    {
        return $this->responsiveMode === 'scroll'
            || ! $this->viewportKnown
            || ! $this->mobileView;
    }

    /**
     * Si esta petición debe emitir la variante para pantallas estrechas.
     */
    public function shouldRenderMobile(): bool
    {
        return $this->responsiveMode !== 'scroll'
            && (! $this->viewportKnown || $this->mobileView);
    }

    protected function applyResponsiveConfig(): void
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
        return collect($this->cachedColumns())
            ->filter(fn (Column $col) => $col->isCollapsedOnMobile() || $col->isCollapsedOnTablet())
            ->values()
            ->all();
    }

}

<?php

namespace KoreUi\DataTable\Concerns;

use Livewire\Attributes\Locked;

trait WithDeferredLoading
{
    /**
     * #[Locked]: son estado de carga que fija el servidor. Si el cliente pudiera
     * escribirlos, apagaría la carga diferida sin más.
     */
    #[Locked]
    public bool $deferredLoading = false;

    #[Locked]
    public bool $dataLoaded = false;

    public function setDeferredLoading(bool $enabled = true): static
    {
        $this->deferredLoading = $enabled;

        return $this;
    }

    public function isDeferredLoading(): bool
    {
        return $this->deferredLoading;
    }

    public function isDataLoaded(): bool
    {
        return $this->dataLoaded;
    }

    public function loadData(): void
    {
        $this->dataLoaded = true;
    }
}

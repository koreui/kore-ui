<?php

namespace KoreUi\DataTable\Concerns;

trait WithDeferredLoading
{
    public bool $deferredLoading = false;

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

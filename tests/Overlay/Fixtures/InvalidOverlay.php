<?php

namespace KoreUi\Tests\Overlay\Fixtures;

use Livewire\Component;

/**
 * A component that does NOT implement Overlayable.
 * Used to test that the resolver rejects it.
 */
class InvalidOverlay extends Component
{
    public function render()
    {
        return <<<'HTML'
        <div>Invalid</div>
        HTML;
    }
}

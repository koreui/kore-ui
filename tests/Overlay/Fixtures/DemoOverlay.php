<?php

namespace KoreUi\Tests\Overlay\Fixtures;

use KoreUi\Overlay\OverlayComponent;

class DemoOverlay extends OverlayComponent
{
    public string $title = 'Demo Overlay';

    public function render()
    {
        return <<<'HTML'
        <div>
            <h2>{{ $title }}</h2>
            <p>This is a demo overlay.</p>
        </div>
        HTML;
    }
}

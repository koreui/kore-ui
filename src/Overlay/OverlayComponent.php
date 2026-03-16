<?php

namespace KoreUi\Overlay;

use KoreUi\Core\Concerns\HasOverlayBehavior;
use KoreUi\Core\Contracts\Overlayable;
use Livewire\Component;

abstract class OverlayComponent extends Component implements Overlayable
{
    use HasOverlayBehavior;
}

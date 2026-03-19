<?php

namespace KoreUi\Tests\Spotlight\Fixtures;

use Livewire\Component;

class DemoSpotlightComponent extends Component
{
    public string $result = '';

    public function handleAction(string $value = ''): void
    {
        $this->result = "action:{$value}";
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <p>{{ $result }}</p>
        </div>
        HTML;
    }
}

<?php

namespace KoreUi\Tests\Feedback\Fixtures;

use KoreUi\Core\Concerns\InteractsWithFeedback;
use Livewire\Component;

class DemoComponent extends Component
{
    use InteractsWithFeedback;

    public string $message = '';

    public function sendToast(): void
    {
        $this->toast()->success('Test toast')->send();
    }

    public function sendConfirm(): void
    {
        $this->confirm('Are you sure?')
            ->onConfirm('handleConfirm', [1])
            ->send();
    }

    public function handleConfirm(int $id): void
    {
        $this->message = "Confirmed: {$id}";
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <p>{{ $message }}</p>
        </div>
        HTML;
    }
}

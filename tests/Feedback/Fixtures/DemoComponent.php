<?php

namespace KoreUi\Tests\Feedback\Fixtures;

use KoreUi\Core\Concerns\InteractsWithFeedback;
use Livewire\Component;

class DemoComponent extends Component
{
    use InteractsWithFeedback;

    public string $message = '';

    public bool $secretRun = false;

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

    /**
     * Protected method that a forged kore:confirm-callback event must NOT be
     * able to reach (simulates runBulkAction/getAllMatchingIds in a DataTable).
     */
    protected function secretAction(): void
    {
        $this->secretRun = true;
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

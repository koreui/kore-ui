<?php

namespace KoreUi\Feedback;

use Livewire\Component;

class FeedbackManager extends Component
{
    public ?array $flash = null;

    public function mount(): void
    {
        $this->flash = session('kore:toast');
    }

    public function render()
    {
        return view('kore::feedback.manager', [
            'config' => [
                'position'       => config('kore-ui.feedback.toast.position', 'top-right'),
                'maxVisible'     => config('kore-ui.feedback.toast.max_visible', 5),
                'spacing'        => config('kore-ui.feedback.toast.spacing', 'gap-3'),
                'zIndex'         => config('kore-ui.feedback.toast.z_index', 'z-[60]'),
                'expandDelay'    => config('kore-ui.feedback.toast.expand_delay', 150),
                'collapseDelay'  => config('kore-ui.feedback.toast.collapse_delay', 300),
                'swipeToDismiss' => config('kore-ui.feedback.toast.swipe_to_dismiss', true),
            ],
        ]);
    }
}

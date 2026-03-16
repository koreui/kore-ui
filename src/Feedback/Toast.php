<?php

namespace KoreUi\Feedback;

use Illuminate\Support\Str;
use InvalidArgumentException;
use KoreUi\Feedback\Config\FeedbackDefaults;
use Livewire\Component;

class Toast
{
    protected string $id;

    protected string $type = 'info';

    protected string $title = '';

    protected ?string $description = null;

    protected ?int $timeout = null;

    protected ?string $position = null;

    protected ?bool $dismissible = null;

    protected bool $sole = false;

    protected bool $noGroup = false;

    protected ?bool $autoExpand = null;

    protected array $actions = [];

    protected array $options = [];

    protected array $hooks = [];

    protected ?string $resolveId = null;

    protected bool $viaSession = false;

    protected ?Component $component = null;

    public function __construct(?Component $component = null)
    {
        $this->id = Str::uuid()->toString();
        $this->component = $component;
    }

    // --- Type setters ---

    public function success(string $title, ?string $description = null): static
    {
        $this->type = 'success';
        $this->title = $title;
        $this->description = $description;

        return $this;
    }

    public function error(string $title, ?string $description = null): static
    {
        $this->type = 'error';
        $this->title = $title;
        $this->description = $description;

        return $this;
    }

    public function warning(string $title, ?string $description = null): static
    {
        $this->type = 'warning';
        $this->title = $title;
        $this->description = $description;

        return $this;
    }

    public function info(string $title, ?string $description = null): static
    {
        $this->type = 'info';
        $this->title = $title;
        $this->description = $description;

        return $this;
    }

    public function question(string $title, ?string $description = null): static
    {
        $this->type = 'question';
        $this->title = $title;
        $this->description = $description;

        return $this;
    }

    public function loading(string $title, ?string $description = null): static
    {
        $this->type = 'loading';
        $this->title = $title;
        $this->description = $description;
        $this->dismissible = false;
        $this->noGroup = true;

        return $this;
    }

    // --- Resolve (loading → result) ---

    public function resolve(string $id): static
    {
        $this->resolveId = $id;

        return $this;
    }

    // --- Configuration ---

    public function timeout(int $seconds): static
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function persistent(): static
    {
        $this->timeout = 0;

        return $this;
    }

    public function position(string $position): static
    {
        if (! FeedbackDefaults::isValidPosition($position)) {
            throw new InvalidArgumentException(
                "Invalid toast position [{$position}]. Valid positions: ".implode(', ', FeedbackDefaults::positions())
            );
        }

        $this->position = $position;

        return $this;
    }

    public function sole(): static
    {
        $this->sole = true;

        return $this;
    }

    public function noGroup(): static
    {
        $this->noGroup = true;

        return $this;
    }

    public function expanded(bool $autoExpand = true): static
    {
        $this->autoExpand = $autoExpand;

        return $this;
    }

    // --- Actions ---

    public function action(string $label, string $method, array $params = []): static
    {
        $this->actions[] = [
            'label'  => $label,
            'method' => $method,
            'params' => $params,
        ];

        return $this;
    }

    // --- Confirm/Cancel (mutually exclusive with actions) ---

    public function confirm(string $text, string $method, array $params = []): static
    {
        $this->options['confirm'] = [
            'text'   => $text,
            'method' => $method,
            'params' => $params,
        ];

        return $this;
    }

    public function cancel(string $text, ?string $method = null, array $params = []): static
    {
        $this->options['cancel'] = [
            'text'   => $text,
            'method' => $method,
            'params' => $params,
        ];

        return $this;
    }

    // --- Hooks ---

    public function hook(string $event, string $method, array $params = []): static
    {
        $this->hooks[$event] = [
            'method' => $method,
            'params' => $params,
        ];

        return $this;
    }

    // --- Delivery ---

    public function viaSession(): static
    {
        $this->viaSession = true;

        return $this;
    }

    /**
     * Send the toast. Returns the toast ID (useful for loading → resolve pattern).
     */
    public function send(): string
    {
        if ($this->resolveId) {
            return $this->sendResolve();
        }

        $payload = $this->toArray();

        if ($this->shouldUseSession()) {
            session()->flash('kore:toast', $payload);
        } else {
            $this->component->dispatch('kore:toast', toast: $payload);
        }

        return $this->id;
    }

    /**
     * Send a resolve event for a loading toast.
     */
    protected function sendResolve(): string
    {
        $payload = [
            'id'          => $this->resolveId,
            'type'        => $this->type,
            'title'       => $this->title,
            'description' => $this->description,
            'timeout'     => $this->getEffectiveTimeout(),
        ];

        if ($this->shouldUseSession()) {
            session()->flash('kore:toast-resolve', $payload);
        } else {
            $this->component->dispatch('kore:toast-resolve', toast: $payload);
        }

        return $this->resolveId;
    }

    protected function shouldUseSession(): bool
    {
        return $this->viaSession || $this->component === null;
    }

    /**
     * Get the effective timeout, applying type defaults.
     */
    protected function getEffectiveTimeout(): int
    {
        if ($this->timeout !== null) {
            return $this->timeout;
        }

        return FeedbackDefaults::timeout($this->type);
    }

    /**
     * Determine if the toast has expandable content.
     */
    protected function hasContent(): bool
    {
        return $this->description !== null
            || count($this->actions) > 0
            || ! empty($this->options);
    }

    /**
     * Build the payload array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $timeout = $this->getEffectiveTimeout();
        $hasContent = $this->hasContent();

        return [
            'id'          => $this->id,
            'type'        => $this->type,
            'title'       => $this->title,
            'description' => $this->description,
            'timeout'     => $timeout,
            'position'    => $this->position ?? config('kore-ui.feedback.toast.position', 'top-right'),
            'dismissible' => $this->dismissible ?? config('kore-ui.feedback.toast.dismissible', true),
            'sole'        => $this->sole,
            'noGroup'     => $this->noGroup,
            'expandable'  => $hasContent,
            'autoExpand'  => $this->autoExpand ?? $hasContent,
            'actions'     => $this->actions,
            'options'     => $this->options,
            'hooks'       => $this->hooks,
            'reference'   => $this->component?->getId(),
        ];
    }

    /**
     * Get the toast ID.
     */
    public function getId(): string
    {
        return $this->id;
    }
}

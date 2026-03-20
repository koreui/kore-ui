<?php

namespace KoreUi\DataTable\Actions;

use Closure;
use KoreUi\DataTable\Support\UrlSanitizer;

class RowAction
{
    protected string $identifier;

    protected string $label;

    protected ?string $icon = null;

    protected string $color = 'primary';

    protected ?string $urlPattern = null;

    protected ?Closure $urlCallback = null;

    protected ?string $wireMethod = null;

    protected ?string $dispatchEvent = null;

    protected array|Closure|null $dispatchParams = null;

    protected ?string $confirmMessage = null;

    protected ?string $confirmDescription = null;

    protected bool $hidden = false;

    protected ?Closure $hiddenCallback = null;

    protected bool $separator = false;

    protected bool $openInNewTab = false;

    public function __construct(string $identifier, string $label)
    {
        $this->identifier = $identifier;
        $this->label = $label;
    }

    public static function make(string $identifier, string $label): static
    {
        return new static($identifier, $label);
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function urlPattern(string $pattern): static
    {
        $this->urlPattern = $pattern;

        return $this;
    }

    public function url(Closure $callback): static
    {
        $this->urlCallback = $callback;

        return $this;
    }

    public function wireMethod(string $method): static
    {
        $this->wireMethod = $method;

        return $this;
    }

    /**
     * Dispatch a browser CustomEvent on click (no server round-trip).
     * $params can be a static array or a Closure receiving the row.
     */
    public function dispatch(string $event, array|Closure $params = []): static
    {
        $this->dispatchEvent = $event;
        $this->dispatchParams = $params;

        return $this;
    }

    /**
     * Shorthand for opening a kore-ui overlay (dispatches 'kore:open').
     * $arguments can be a static array or a Closure receiving the row.
     */
    public function openOverlay(string $name, array|Closure $arguments = []): static
    {
        if ($arguments instanceof Closure) {
            return $this->dispatch('kore:open', fn ($row) => [
                'name'      => $name,
                'arguments' => ($arguments)($row),
            ]);
        }

        return $this->dispatch('kore:open', [
            'name'      => $name,
            'arguments' => $arguments,
        ]);
    }

    public function confirm(string $message, ?string $description = null): static
    {
        $this->confirmMessage = $message;
        $this->confirmDescription = $description;

        return $this;
    }

    public function hidden(bool|Closure $condition = true): static
    {
        if ($condition instanceof Closure) {
            $this->hiddenCallback = $condition;
        } else {
            $this->hidden = $condition;
        }

        return $this;
    }

    public function separator(bool $condition = true): static
    {
        $this->separator = $condition;

        return $this;
    }

    public function openInNewTab(bool $condition = true): static
    {
        $this->openInNewTab = $condition;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getWireMethod(): ?string
    {
        return $this->wireMethod;
    }

    public function hasDispatch(): bool
    {
        return $this->dispatchEvent !== null;
    }

    public function getDispatchEvent(): ?string
    {
        return $this->dispatchEvent;
    }

    public function getDispatchParams(mixed $row): array
    {
        if ($this->dispatchParams instanceof Closure) {
            return ($this->dispatchParams)($row);
        }

        return $this->dispatchParams ?? [];
    }

    public function hasConfirm(): bool
    {
        return $this->confirmMessage !== null;
    }

    public function getConfirmMessage(): ?string
    {
        return $this->confirmMessage;
    }

    public function getConfirmDescription(): ?string
    {
        return $this->confirmDescription;
    }

    /**
     * Build the kore:open payload for the kore-confirm-dialog component.
     * This lets the Blade pre-render the full payload so the confirm dialog
     * opens client-side with no server round-trip.
     */
    public function buildKoreConfirmPayload(mixed $row, string $componentId, string $primaryKey): array
    {
        return [
            'name'      => 'kore-confirm-dialog',
            'arguments' => [
                'title'       => $this->confirmMessage,
                'description' => $this->confirmDescription,
                'type'        => 'question',
                'confirmText' => config('kore-ui.feedback.confirm.confirm_text', 'Confirmar'),
                'cancelText'  => config('kore-ui.feedback.confirm.cancel_text', 'Cancelar'),
                'onConfirm'   => [
                    'method' => $this->wireMethod,
                    'params' => [data_get($row, $primaryKey)],
                ],
                'onCancel'  => [],
                'callerRef' => $componentId,
            ],
            'overlayAttributes' => [
                'type'              => 'confirm',
                'size'              => config('kore-ui.feedback.confirm.size', 'md'),
                'closesOnClickAway' => config('kore-ui.feedback.confirm.closes_on_click_away', false),
                'closesOnEscape'    => config('kore-ui.feedback.confirm.closes_on_escape', true),
                'destroysOnClose'   => true,
            ],
        ];
    }

    public function hasSeparator(): bool
    {
        return $this->separator;
    }

    public function opensInNewTab(): bool
    {
        return $this->openInNewTab;
    }

    public function isHidden(mixed $row = null): bool
    {
        if ($this->hiddenCallback !== null) {
            return (bool) ($this->hiddenCallback)($row);
        }

        return $this->hidden;
    }

    public function hasUrl(): bool
    {
        return $this->urlPattern !== null || $this->urlCallback !== null;
    }

    public function getUrl(mixed $row): ?string
    {
        if ($this->urlCallback !== null) {
            return UrlSanitizer::sanitize(($this->urlCallback)($row));
        }

        if ($this->urlPattern !== null) {
            $url = preg_replace_callback('/\{(\w+(?:\.\w+)*)\}/', function ($matches) use ($row) {
                return data_get($row, $matches[1], $matches[0]);
            }, $this->urlPattern);

            return UrlSanitizer::sanitize($url);
        }

        return null;
    }
}

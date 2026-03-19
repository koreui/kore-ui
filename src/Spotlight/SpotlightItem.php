<?php

namespace KoreUi\Spotlight;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;
use KoreUi\Spotlight\Config\SpotlightDefaults;

class SpotlightItem
{
    protected string $id;

    protected string $name;

    protected ?string $description = null;

    protected ?string $icon = null;

    protected string $group = 'General';

    protected ?string $shortcut = null;

    // Action type: route | url | action | dispatch
    protected ?string $actionType = null;

    protected ?string $actionTarget = null;

    protected array $actionParams = [];

    protected array $synonyms = [];

    protected bool $hidden = false;

    protected bool $conditionResult = true;

    protected ?string $gateAbility = null;

    protected array $dependencies = [];

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->id = Str::slug($name);
    }

    /**
     * Create a new SpotlightItem instance.
     */
    public static function make(string $name): static
    {
        return new static($name);
    }

    // --- Content ---

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function group(string $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function shortcut(string $shortcut): static
    {
        $this->shortcut = $shortcut;

        return $this;
    }

    // --- Actions ---

    /**
     * Navigate to a named route.
     */
    public function route(string $routeName, array $params = []): static
    {
        $this->actionType = 'route';
        $this->actionTarget = $routeName;
        $this->actionParams = $params;

        return $this;
    }

    /**
     * Navigate to an external URL.
     */
    public function url(string $url, bool $newTab = false): static
    {
        $this->actionType = 'url';
        $this->actionTarget = $url;
        $this->actionParams = ['newTab' => $newTab];

        return $this;
    }

    /**
     * Call a Livewire method on the parent component.
     */
    public function action(string $method, array $params = []): static
    {
        $this->actionType = 'action';
        $this->actionTarget = $method;
        $this->actionParams = $params;

        return $this;
    }

    /**
     * Dispatch an Alpine/Livewire event.
     */
    public function dispatch(string $event, array $data = []): static
    {
        $this->actionType = 'dispatch';
        $this->actionTarget = $event;
        $this->actionParams = $data;

        return $this;
    }

    // --- Search helpers ---

    /**
     * Add synonyms for fuzzy search matching.
     */
    public function synonym(string ...$synonyms): static
    {
        $this->synonyms = array_merge($this->synonyms, $synonyms);

        return $this;
    }

    /**
     * Hide this item from the initial list (only visible when searching).
     */
    public function hidden(): static
    {
        $this->hidden = true;

        return $this;
    }

    // --- Conditions ---

    /**
     * Only include this item if the condition is true.
     */
    public function when(bool $condition): static
    {
        $this->conditionResult = $condition;

        return $this;
    }

    /**
     * Only include this item if the user passes the given gate ability.
     */
    public function gate(string $ability, mixed $arguments = []): static
    {
        $this->gateAbility = $ability;

        return $this;
    }

    // --- Dependencies ---

    /**
     * Add a multi-step dependency (input, search, or select step).
     */
    public function dependency(SpotlightDependency $dependency): static
    {
        $this->dependencies[] = $dependency;

        return $this;
    }

    // --- Visibility ---

    /**
     * Determine if this item should be included based on when() and gate().
     */
    public function isVisible(): bool
    {
        if (! $this->conditionResult) {
            return false;
        }

        if ($this->gateAbility !== null && ! Gate::check($this->gateAbility)) {
            return false;
        }

        return true;
    }

    // --- Serialization ---

    /**
     * Build the payload array for the frontend.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'description'  => $this->description,
            'icon'         => $this->icon,
            'iconHtml'     => $this->resolveIconHtml(),
            'group'        => $this->group,
            'shortcut'     => $this->shortcut,
            'actionType'   => $this->actionType,
            'actionTarget' => $this->actionTarget,
            'actionParams' => $this->actionParams,
            'synonyms'     => $this->synonyms,
            'hidden'       => $this->hidden,
            'dependencies' => array_map(
                fn (SpotlightDependency $dep) => $dep->toArray(),
                $this->dependencies
            ),
        ];
    }

    /**
     * Resolve the icon to an inline SVG string for Alpine x-for templates.
     */
    protected function resolveIconHtml(): ?string
    {
        if ($this->icon === null) {
            return null;
        }

        try {
            return svg('lucide-'.$this->icon, 'size-4')->toHtml();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get the item ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the item name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the group name.
     */
    public function getGroup(): string
    {
        return $this->group;
    }
}

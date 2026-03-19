<?php

namespace KoreUi\DataTable\Columns;

use KoreUi\DataTable\Actions\RowAction;

class ActionColumn extends Column
{
    /** @var RowAction[] */
    protected array $actions = [];

    protected string $triggerIcon = 'more-vertical';

    protected string $displayMode = 'dropdown';

    public function __construct(string $label = 'Acciones', string $field = '__actions')
    {
        parent::__construct($label, $field);
        $this->align = 'center';
    }

    public static function make(string $label = 'Acciones', ?string $field = null): static
    {
        return new static($label, $field ?? '__actions');
    }

    /**
     * @param  RowAction[]  $actions
     */
    public function actions(array $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    public function triggerIcon(string $icon): static
    {
        $this->triggerIcon = $icon;

        return $this;
    }

    public function inline(): static
    {
        $this->displayMode = 'inline';

        return $this;
    }

    public function dropdown(): static
    {
        $this->displayMode = 'dropdown';

        return $this;
    }

    /**
     * @return RowAction[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get visible actions for a specific row.
     *
     * @return RowAction[]
     */
    public function getVisibleActions(mixed $row): array
    {
        return array_values(
            array_filter($this->actions, fn (RowAction $action) => ! $action->isHidden($row))
        );
    }

    public function isSortable(): bool
    {
        return false;
    }

    public function isSearchable(): bool
    {
        return false;
    }

    public function getValue(mixed $row): mixed
    {
        return null;
    }

    public function getType(): string
    {
        return 'action';
    }

    public function getComponentProps(): array
    {
        return [
            'triggerIcon'  => $this->triggerIcon,
            'displayMode'  => $this->displayMode,
        ];
    }
}

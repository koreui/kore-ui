<?php

namespace KoreUi\DataTable\Columns;

use Closure;

class Column
{
    use Concerns\HasSorting;
    use Concerns\HasSearch;
    use Concerns\HasVisibility;
    use Concerns\HasAggregation;
    use Concerns\HasEditing;

    protected string $label;

    protected string $field;

    protected ?int $width = null;

    protected ?int $minWidth = null;

    protected string $align = 'left';

    protected bool $wrap = true;

    protected bool $html = false;

    protected mixed $default = null;

    protected ?Closure $formatCallback = null;

    protected bool $copyable = false;

    protected ?Closure $clickableCallback = null;

    protected ?string $clickableUrl = null;

    protected bool $clickableNewTab = false;

    public function __construct(string $label, string $field)
    {
        $this->label = $label;
        $this->field = $field;
    }

    public static function make(string $label, ?string $field = null): static
    {
        $field = $field ?? str($label)->snake()->toString();

        return new static($label, $field);
    }

    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function minWidth(int $minWidth): static
    {
        $this->minWidth = $minWidth;

        return $this;
    }

    public function align(string $align): static
    {
        $this->align = $align;

        return $this;
    }

    public function wrap(bool $wrap = true): static
    {
        $this->wrap = $wrap;

        return $this;
    }

    public function html(bool $html = true): static
    {
        $this->html = $html;

        return $this;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function format(Closure $callback): static
    {
        $this->formatCallback = $callback;

        return $this;
    }

    public function copyable(bool $copyable = true): static
    {
        $this->copyable = $copyable;

        return $this;
    }

    public function isCopyable(): bool
    {
        return $this->copyable;
    }

    public function clickable(Closure|string|null $urlOrCallback = null, bool $newTab = false): static
    {
        if ($urlOrCallback instanceof Closure) {
            $this->clickableCallback = $urlOrCallback;
        } elseif (is_string($urlOrCallback)) {
            $this->clickableUrl = $urlOrCallback;
        }

        $this->clickableNewTab = $newTab;

        return $this;
    }

    public function isClickable(): bool
    {
        return $this->clickableCallback !== null || $this->clickableUrl !== null;
    }

    public function getClickableUrl(mixed $row): ?string
    {
        if ($this->clickableCallback !== null) {
            return ($this->clickableCallback)($row);
        }

        return $this->clickableUrl;
    }

    public function isClickableNewTab(): bool
    {
        return $this->clickableNewTab;
    }

    public function getValue(mixed $row): mixed
    {
        $value = data_get($row, $this->field, $this->default);

        if ($this->formatCallback !== null) {
            return ($this->formatCallback)($value, $row);
        }

        return $value;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getMinWidth(): ?int
    {
        return $this->minWidth;
    }

    public function getAlign(): string
    {
        return $this->align;
    }

    public function isWrap(): bool
    {
        return $this->wrap;
    }

    public function isHtml(): bool
    {
        return $this->html;
    }

    public function getType(): string
    {
        return 'text';
    }

    public function getComponentProps(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return [
            'label'      => $this->label,
            'field'      => $this->field,
            'sortable'   => $this->isSortable(),
            'searchable' => $this->isSearchable(),
            'hidden'     => $this->isHidden(),
            'align'      => $this->align,
            'wrap'       => $this->wrap,
            'html'       => $this->html,
            'width'      => $this->width,
            'minWidth'   => $this->minWidth,
            'aggregation' => $this->aggregationType,
            'editable'   => $this->isEditable(),
            'copyable'   => $this->copyable,
            'clickable'  => $this->isClickable(),
            'pinned'     => $this->isPinned() ? $this->getPinnedSide() : null,
        ];
    }
}

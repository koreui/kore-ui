<?php

namespace KoreUi\DataTable\Columns;

use Closure;

class Column
{
    use Concerns\HasSorting;
    use Concerns\HasSearch;
    use Concerns\HasVisibility;

    protected string $label;

    protected string $field;

    protected ?int $width = null;

    protected ?int $minWidth = null;

    protected string $align = 'left';

    protected bool $wrap = true;

    protected bool $html = false;

    protected mixed $default = null;

    protected ?Closure $formatCallback = null;

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
        ];
    }
}

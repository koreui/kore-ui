<?php

namespace KoreUi\DataTable\Columns;

use Closure;
use KoreUi\DataTable\Support\UrlSanitizer;

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

    protected ?int $maxWidth = null;

    protected string $align = 'left';

    protected bool $wrap = true;

    protected bool $html = false;

    protected mixed $default = null;

    protected ?Closure $formatCallback = null;

    protected bool $copyable = false;

    protected Closure|string|null $description = null;

    protected string $descriptionPosition = 'below';

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

    /**
     * Cap the column width and truncate overflowing content with an ellipsis
     * (the full value is exposed via the cell's title attribute).
     */
    public function maxWidth(int $maxWidth): static
    {
        $this->maxWidth = $maxWidth;

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

    /**
     * Segunda línea de la celda, en tono secundario.
     *
     * Es el patrón «nombre arriba, email en gris debajo» que aparece en casi
     * toda tabla de administración y que hasta ahora obligaba a bajar a un
     * ComponentColumn.
     *
     *     Column::make('Usuario', 'name')
     *         ->description(fn ($row) => $row->email)
     *
     * $position acepta 'below' (por defecto) o 'above', para el caso contrario:
     * una etiqueta pequeña encima del valor.
     */
    public function description(Closure|string $description, string $position = 'below'): static
    {
        $this->description = $description;
        $this->descriptionPosition = $position === 'above' ? 'above' : 'below';

        return $this;
    }

    public function hasDescription(): bool
    {
        return $this->description !== null;
    }

    public function getDescriptionPosition(): string
    {
        return $this->descriptionPosition;
    }

    public function getDescription(mixed $row): ?string
    {
        if ($this->description === null) {
            return null;
        }

        $value = $this->description instanceof Closure
            ? ($this->description)($row)
            : data_get($row, $this->description);

        return $value === null || $value === '' ? null : (string) $value;
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
            return UrlSanitizer::sanitize(($this->clickableCallback)($row));
        }

        return UrlSanitizer::sanitize($this->clickableUrl);
    }

    public function isClickableNewTab(): bool
    {
        return $this->clickableNewTab;
    }

    public function getValue(mixed $row): mixed
    {
        $value = data_get($row, $this->field, $this->default);

        // El callback de formato manda y recibe el valor crudo: es el único que
        // puede querer distinguir un NULL de un valor por defecto.
        if ($this->formatCallback !== null) {
            return ($this->formatCallback)($value, $row);
        }

        // data_get() resuelve objetos con isset(), así que para un modelo con el
        // atributo a null ya devuelve el default. Con un array no: ahí la clave
        // existe y el null llega tal cual. Esta comprobación iguala los dos
        // casos, para que default() no dependa de si la fila es un modelo o un
        // array.
        if ($value === null) {
            return $this->default;
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

    public function getMaxWidth(): ?int
    {
        return $this->maxWidth;
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
            'maxWidth'   => $this->maxWidth,
            'aggregation' => $this->aggregationType,
            'editable'   => $this->isEditable(),
            'copyable'   => $this->copyable,
            'clickable'  => $this->isClickable(),
            'description' => $this->hasDescription(),
            'pinned'     => $this->isPinned() ? $this->getPinnedSide() : null,
        ];
    }
}

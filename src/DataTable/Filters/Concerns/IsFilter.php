<?php

namespace KoreUi\DataTable\Filters\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;

trait IsFilter
{
    protected string $label;

    protected string $column;

    protected mixed $default = null;

    protected bool $hidden = false;

    protected ?string $placeholder = null;

    protected ?int $position = null;

    protected ?Closure $pillCallback = null;

    protected ?Closure $callback = null;

    protected ?Closure $hiddenCallback = null;

    protected ?string $key = null;

    /**
     * Normaliza el valor que llega del cliente antes de tocar la consulta.
     *
     * `$filters` es una propiedad pública de Livewire: su contenido lo controla
     * quien esté en el navegador, tanto en forma como en tipo. Sin este paso, un
     * `?filter[estado][]=x` mete un array donde `where()` espera un escalar y
     * Eloquent lo pasa como binding — PDOException y error 500 con una URL que
     * cualquiera puede compartir.
     *
     * Devolver `null` significa "este valor no es utilizable": el filtro
     * simplemente no se aplica.
     *
     * Esta implementación por defecto es la red de seguridad para filtros
     * propios de terceros y solo comprueba la forma. Cada filtro de la librería
     * la sobreescribe con las reglas de su tipo; un filtro personalizado que
     * acepte estructuras distintas debería hacer lo mismo.
     */
    public function sanitize(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if (is_array($value)) {
            // Un nivel de profundidad como máximo: nada de arrays anidados ni
            // objetos, que es justo lo que rompe los bindings.
            return array_filter($value, fn ($item) => $item === null || is_scalar($item));
        }

        return null;
    }

    /**
     * Aplica una condición sobre la columna del filtro, resolviendo la
     * dot-notation como relación cuando el modelo la declara.
     *
     * `TextFilter::make('Autor', 'user.name')` generaba `where('user.name', …)`
     * y rompía el SQL. Ahora se traduce a `whereHas('user', …)`, igual que hace
     * la búsqueda global.
     *
     * El constraint recibe la consulta correcta (la de la relación o la base) y
     * el nombre de columna ya resuelto, así que un filtro que necesite DOS
     * condiciones (un rango) las añade dentro del mismo `whereHas` y conserva la
     * semántica: "tiene una fila entre X e Y", no "tiene alguna ≥ X y alguna ≤ Y".
     */
    protected function applyOnColumn(Builder $query, Closure $constraint): Builder
    {
        $target = $this->resolveColumnTarget($query);

        if ($target === null) {
            return $query;
        }

        [$relation, $column] = $target;

        if ($relation === null) {
            $constraint($query, $column);

            return $query;
        }

        $query->whereHas($relation, fn (Builder $related) => $constraint($related, $column));

        return $query;
    }

    /**
     * @return array{0: ?string, 1: string}|null  [relación|null, columna]
     */
    protected function resolveColumnTarget(Builder $query): ?array
    {
        $column = $this->column;

        if (! str_contains($column, '.')) {
            return preg_match('/^[a-zA-Z0-9_]+$/', $column) ? [null, $column] : null;
        }

        $parts    = explode('.', $column);
        $field    = array_pop($parts);
        $relation = implode('.', $parts);

        if (! preg_match('/^[a-zA-Z0-9_.]+$/', $relation) || ! preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
            return null;
        }

        // `posts.title` puede ser una relación o una columna cualificada por
        // tabla (con un join hecho en query()). Solo se trata como relación si
        // el modelo la declara; si no, se deja pasar tal cual, que es como se
        // comportaban los filtros antes de soportar dot-notation.
        if (! method_exists($query->getModel(), explode('.', $relation)[0])) {
            return [null, $column];
        }

        return [$relation, $field];
    }

    public function getKey(): string
    {
        return $this->key ?? str_replace('.', '_', $this->column);
    }

    public function key(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function getCallback(): ?Closure
    {
        return $this->callback;
    }

    public function isHidden(): bool
    {
        if ($this->hiddenCallback !== null) {
            return (bool) ($this->hiddenCallback)();
        }

        return $this->hidden;
    }

    public function hasValue(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === []) {
            return false;
        }

        return true;
    }

    public function getPillText(mixed $value): ?string
    {
        if (! $this->hasValue($value)) {
            return null;
        }

        if ($this->pillCallback !== null) {
            return ($this->pillCallback)($value);
        }

        if (is_array($value)) {
            return $this->label.': '.implode(', ', $value);
        }

        return $this->label.': '.$value;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function hidden(bool $condition = true): static
    {
        $this->hidden = $condition;

        return $this;
    }

    public function hiddenIf(Closure $callback): static
    {
        $this->hiddenCallback = $callback;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function position(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function pill(Closure $callback): static
    {
        $this->pillCallback = $callback;

        return $this;
    }

    public function callback(Closure $callback): static
    {
        $this->callback = $callback;

        return $this;
    }
}

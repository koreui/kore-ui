<?php

namespace KoreUi\DataTable\Filters;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Support\LikePattern;

class TextFilter extends Filter
{
    protected int $debounce = 300;

    public function sanitize(mixed $value): mixed
    {
        // Un array aquí no reventaba la consulta pero sí filtraba por el texto
        // literal "Array", con su Array-to-string-conversion de propina.
        return is_scalar($value) ? (string) $value : null;
    }

    public function apply(Builder $query, mixed $value): Builder
    {
        // Los comodines del término se escapan igual que en la búsqueda global:
        // un `%` escrito por el usuario es texto, no un comodín que fuerce un
        // escaneo completo de la tabla.
        $pattern = LikePattern::contains((string) $value);

        return $this->applyOnColumn(
            $query,
            fn (Builder $q, string $column) => LikePattern::where($q, $column, $pattern),
        );
    }

    public function getType(): string
    {
        return 'text';
    }

    public function getComponentProps(): array
    {
        return [
            'placeholder' => $this->placeholder ?? $this->label,
            'debounce'    => $this->debounce,
        ];
    }

    public function debounce(int $milliseconds): static
    {
        $this->debounce = $milliseconds;

        return $this;
    }

    public function getDebounce(): int
    {
        return $this->debounce;
    }
}

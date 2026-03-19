<?php

namespace KoreUi\Spotlight;

use InvalidArgumentException;

class SpotlightDependency
{
    protected string $type;

    protected string $placeholder;

    protected ?string $searchUrl = null;

    protected string $searchMethod = 'GET';

    protected ?string $validation = null;

    protected array $options = [];

    protected function __construct(string $type, string $placeholder)
    {
        $this->type = $type;
        $this->placeholder = $placeholder;
    }

    /**
     * Create a "search" dependency — remote search with selection.
     *
     * @param  string  $searchUrl  Route name or URL for remote search
     */
    public static function search(string $placeholder, string $searchUrl, string $method = 'GET'): static
    {
        $dep = new static('search', $placeholder);
        $dep->searchUrl = $searchUrl;
        $dep->searchMethod = strtoupper($method);

        return $dep;
    }

    /**
     * Create an "input" dependency — free text input.
     */
    public static function input(string $placeholder, ?string $validation = null): static
    {
        $dep = new static('input', $placeholder);
        $dep->validation = $validation;

        return $dep;
    }

    /**
     * Create a "select" dependency — fixed list of options.
     *
     * @param  array<string, string>  $options  key => label pairs
     */
    public static function select(string $placeholder, array $options): static
    {
        $dep = new static('select', $placeholder);
        $dep->options = $options;

        return $dep;
    }

    /**
     * Build the payload array for the frontend.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type'         => $this->type,
            'placeholder'  => $this->placeholder,
            'searchUrl'    => $this->searchUrl,
            'searchMethod' => $this->searchMethod,
            'validation'   => $this->validation,
            'options'      => $this->options,
        ];
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function getSearchUrl(): ?string
    {
        return $this->searchUrl;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}

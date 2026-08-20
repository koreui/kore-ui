<?php

namespace KoreUi\DataTable\Views;

/**
 * Una vista guardada: el estado completo de la tabla con un nombre.
 *
 * Es lo mismo que un FilterPreset pero al revés — el preset lo declara quien
 * escribe la tabla y es fijo; la vista la crea quien la usa, en caliente.
 */
class SavedView
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly array $filters = [],
        public readonly array $sorts = [],
        public readonly string $search = '',
        public readonly ?int $perPage = null,
        public readonly array $deselectedColumns = [],
        public readonly array $columnPins = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            filters: (array) ($data['filters'] ?? []),
            sorts: (array) ($data['sorts'] ?? []),
            search: (string) ($data['search'] ?? ''),
            perPage: isset($data['perPage']) ? (int) $data['perPage'] : null,
            deselectedColumns: (array) ($data['deselectedColumns'] ?? []),
            columnPins: (array) ($data['columnPins'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'filters'           => $this->filters,
            'sorts'             => $this->sorts,
            'search'            => $this->search,
            'perPage'           => $this->perPage,
            'deselectedColumns' => $this->deselectedColumns,
            'columnPins'        => $this->columnPins,
        ];
    }
}

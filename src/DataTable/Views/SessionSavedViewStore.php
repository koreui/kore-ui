<?php

namespace KoreUi\DataTable\Views;

use Illuminate\Contracts\Session\Session;
use KoreUi\DataTable\Views\Contracts\SavedViewStore;

/**
 * Driver por defecto: las vistas viven en la sesión.
 *
 * Cero instalación y ámbito por usuario de regalo (la sesión ya lo es), a cambio
 * de que las vistas se pierdan al cerrar sesión. Para algo permanente, implementa
 * SavedViewStore contra tu propia tabla y enlázalo en el contenedor.
 */
class SessionSavedViewStore implements SavedViewStore
{
    public function __construct(protected Session $session) {}

    public function all(string $tableKey): array
    {
        $raw = $this->session->get($this->key($tableKey), []);

        return collect($raw)
            ->map(fn (array $data) => SavedView::fromArray($data))
            ->all();
    }

    public function find(string $tableKey, string $id): ?SavedView
    {
        return $this->all($tableKey)[$id] ?? null;
    }

    public function save(string $tableKey, SavedView $view): SavedView
    {
        $stored = $this->session->get($this->key($tableKey), []);
        $stored[$view->id] = $view->toArray();

        $this->session->put($this->key($tableKey), $stored);

        return $view;
    }

    public function delete(string $tableKey, string $id): void
    {
        $stored = $this->session->get($this->key($tableKey), []);
        unset($stored[$id]);

        $this->session->put($this->key($tableKey), $stored);
    }

    protected function key(string $tableKey): string
    {
        return 'kore-datatable-views:' . $tableKey;
    }
}

<?php

namespace KoreUi\DataTable\Concerns;

use Illuminate\Database\Eloquent\Builder;
use KoreUi\Core\Concerns\InteractsWithFeedback;
use KoreUi\DataTable\Actions\BulkAction;
use KoreUi\DataTable\Events\BulkActionExecuted;
use Livewire\Attributes\Locked;

trait WithBulkActions
{
    use InteractsWithFeedback;

    private array $_selectedIds = [];

    /**
     * Acción a la espera de confirmación.
     *
     * #[Locked]: sin esto, el navegador puede fijarla y llamar directamente a
     * confirmBulkAction() saltándose el flujo entero. La comprobación de verdad
     * está en runBulkAction(), pero esta propiedad es estado del servidor y no
     * tiene por qué ser escribible desde fuera.
     */
    #[Locked]
    public string $pendingBulkIdentifier = '';

    /**
     * True cuando lo que espera confirmación es un "todo lo que coincide".
     *
     * En ese modo los IDs NO viajan al navegador dentro del payload del diálogo:
     * se vuelven a resolver en el servidor al confirmar. Mandar dos millones de
     * identificadores al cliente para que los devuelva no es solo caro, es que
     * el que vuelve ya no es el mismo conjunto que se prometió.
     */
    #[Locked]
    public bool $pendingBulkMatching = false;

    /**
     * Tope de IDs que se aceptan del cliente en una sola acción. Solo acota el
     * camino "selección explícita": "todo lo que coincide" se resuelve en el
     * servidor y no pasa por aquí. Súbelo en la tabla si de verdad hace falta.
     */
    protected int $bulkSelectionLimit = 5000;

    /**
     * Si la acción en ejecución vino de "todo lo que coincide". No es estado de
     * Livewire: solo vive durante la llamada, para que la acción pueda
     * preguntarlo con isActingOnAllMatching().
     */
    private bool $actingOnAllMatching = false;

    /**
     * Punto de entrada con una lista de IDs venida del cliente.
     */
    public function executeBulkAction(string $identifier, array $selectedIds): void
    {
        $this->startBulkAction($identifier, $this->resolveAuthorizedIds($selectedIds), matching: false);
    }

    /**
     * Ejecuta contra TODAS las filas que cumplen la búsqueda y los filtros
     * actuales, resolviendo los IDs en el servidor.
     */
    public function executeBulkActionMatching(string $identifier): void
    {
        $this->startBulkAction($identifier, [], matching: true);
    }

    /**
     * Entrada desde la interfaz. Decide de dónde salen los IDs a partir del
     * estado de selección del servidor.
     */
    public function runBulk(string $identifier): void
    {
        if (! $this->hasSelection()) {
            return;
        }

        if ($this->selectAllMatching) {
            $this->executeBulkActionMatching($identifier);
        } else {
            $this->executeBulkAction($identifier, $this->selected);
        }
    }

    /**
     * Tronco común: pide confirmación si la acción la lleva, o ejecuta.
     */
    protected function startBulkAction(string $identifier, array $selectedIds, bool $matching): void
    {
        $action = $this->findBulkAction($identifier);

        if (! $action) {
            return;
        }

        if (! $action->hasConfirm()) {
            $this->actingOnAllMatching = $matching;
            $this->runBulkAction($identifier, $matching ? $this->getAllMatchingIds() : $selectedIds);

            return;
        }

        $this->_selectedIds          = $selectedIds;
        $this->pendingBulkIdentifier = $identifier;
        $this->pendingBulkMatching   = $matching;

        $count   = $matching ? $this->countAllMatching() : count($selectedIds);
        $message = strtr($action->getConfirmMessage(), [':count' => $count]);

        // En modo "matching" el payload lleva una lista vacía a propósito: al
        // confirmar se resuelve de nuevo contra la consulta.
        $confirm = $this->confirm($message)
            ->onConfirm('confirmBulkAction', [$identifier, $matching ? [] : $selectedIds]);

        if ($action->getConfirmDescription()) {
            $confirm->description(strtr($action->getConfirmDescription(), [':count' => $count]));
        }

        $confirm->send();
    }

    /**
     * Llamado tras aceptar el diálogo.
     */
    public function confirmBulkAction(string $identifier, array $selectedIds): void
    {
        if ($this->pendingBulkIdentifier !== $identifier) {
            return;
        }

        $this->actingOnAllMatching = $this->pendingBulkMatching;

        $ids = $this->pendingBulkMatching
            ? $this->getAllMatchingIds()
            : $this->resolveAuthorizedIds($selectedIds);

        $this->_selectedIds = $ids;

        $this->runBulkAction($identifier, $ids);
    }

    /**
     * Recorta una lista de IDs del cliente a los que la `query()` de la tabla
     * autoriza a ver.
     *
     * Sin esto, cualquiera puede llamar a `$wire.executeBulkAction('delete',
     * [1,2,3])` con identificadores de registros que su scope nunca le habría
     * mostrado (IDOR). Es el mismo razonamiento que ya protegía `updateCell()`.
     */
    protected function resolveAuthorizedIds(array $ids): array
    {
        $ids = array_slice(
            array_values(array_unique(array_map(
                fn ($id) => (string) $id,
                array_filter($ids, 'is_scalar'),
            ))),
            0,
            $this->bulkSelectionLimit,
        );

        if ($ids === []) {
            return [];
        }

        $query  = $this->baseFilteredQuery();
        $column = $query->getModel()->qualifyColumn($this->getPrimaryKey());

        return $query->whereIn($column, $ids)
            ->pluck($column)
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /**
     * Claves primarias de todas las filas que cumplen búsqueda y filtros.
     *
     * @return array<int, string>
     */
    protected function getAllMatchingIds(): array
    {
        $query = $this->baseFilteredQuery();

        return $query->pluck($query->getModel()->qualifyColumn($this->getPrimaryKey()))
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    protected function countAllMatching(): int
    {
        return $this->baseFilteredQuery()->count();
    }

    /**
     * La consulta que describe "todo lo que coincide", sin ejecutar.
     *
     * Es la forma recomendada de escribir una acción masiva que pueda tocar
     * conjuntos grandes: `getAllMatchingIds()` materializa un array con todas
     * las claves primarias, y sobre dos millones de filas eso son dos millones
     * de strings en memoria antes de hacer nada.
     *
     *     public function archivar(array $ids): void
     *     {
     *         $this->matchingQuery()->update(['archived_at' => now()]);
     *     }
     *
     * Solo tiene sentido usarla cuando la acción se lanzó en modo "todo lo que
     * coincide"; `isActingOnAllMatching()` lo dice.
     */
    public function matchingQuery(): Builder
    {
        return $this->baseFilteredQuery();
    }

    /**
     * Recorre el conjunto por lotes, sin cargarlo entero.
     *
     *     public function notificar(array $ids): void
     *     {
     *         $this->eachMatching(fn ($usuarios) => Mail::queue(...));
     *     }
     *
     * Usa chunkById, así que el orden es por clave primaria y es seguro aunque
     * el callback modifique las filas que va recorriendo.
     */
    public function eachMatching(callable $callback, int $chunkSize = 500): void
    {
        $this->matchingQuery()->chunkById($chunkSize, function ($rows) use ($callback) {
            return $callback($rows);
        }, $this->getPrimaryKey());
    }

    /**
     * True cuando la acción en curso se lanzó sobre "todo lo que coincide" y no
     * sobre una selección concreta.
     */
    public function isActingOnAllMatching(): bool
    {
        return $this->actingOnAllMatching;
    }

    /**
     * Ejecuta el método de la acción sobre el componente.
     */
    protected function runBulkAction(string $identifier, array $selectedIds): void
    {
        // Última comprobación antes de tocar datos, y la única que cuenta:
        // executeBulkAction() y confirmBulkAction() son métodos públicos de
        // Livewire, así que se pueden llamar sin pasar por el menú. Se exige que
        // la acción exista, no esté oculta y su authorize() dé el visto bueno.
        $action = $this->findBulkAction($identifier);

        abort_unless($action !== null && $action->isAuthorized(), 403);

        $this->_selectedIds = $selectedIds;

        try {
            if (method_exists($this, $identifier)) {
                $this->{$identifier}($selectedIds);
            }

            event(new BulkActionExecuted(static::class, $identifier, $selectedIds, count($selectedIds)));
        } finally {
            $this->actingOnAllMatching = false;
            $this->clearSelected();

            // Una acción masiva cambia cuántas filas hay → los contadores de los
            // presets dejan de ser válidos.
            if (method_exists($this, 'invalidatePresetCounts')) {
                $this->invalidatePresetCounts();
            }
        }
    }

    public function getSelected(): array
    {
        return $this->_selectedIds;
    }

    public function clearSelected(): void
    {
        $this->_selectedIds          = [];
        $this->pendingBulkIdentifier = '';
        $this->pendingBulkMatching   = false;
        $this->clearSelection();
        $this->dispatch('kore:datatable-clear-selection');
    }

    /**
     * Acciones visibles, para pintar el menú.
     *
     * @return BulkAction[]
     */
    public function resolveBulkActions(): array
    {
        return collect($this->cachedBulkActions())
            ->reject(fn (BulkAction $action) => $action->isHidden())
            ->values()
            ->all();
    }

    public function hasBulkActions(): bool
    {
        return count($this->cachedBulkActions()) > 0;
    }

    /**
     * Busca una acción EJECUTABLE. Las ocultas quedan fuera a propósito: si una
     * acción no se le ofrece a este usuario, tampoco debe poder lanzarla.
     */
    protected function findBulkAction(string $identifier): ?BulkAction
    {
        return collect($this->cachedBulkActions())
            ->reject(fn (BulkAction $action) => $action->isHidden())
            ->first(fn (BulkAction $action) => $action->getIdentifier() === $identifier);
    }
}

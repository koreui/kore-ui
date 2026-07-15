<?php

namespace KoreUi\Kanban;

use Livewire\Component;

/**
 * Base Livewire component for a Kanban board.
 *
 * Extend it and implement columns(), cards() and persistMove(). The board renders
 * each column as a sortable group (Alpine's x-sort, bundled with Livewire 4) so cards
 * can be dragged within and between columns; on drop, moveCard() is invoked and delegates
 * persistence to persistMove().
 *
 * ```php
 * class TasksBoard extends KoreKanban
 * {
 *     public function columns(): array
 *     {
 *         return [
 *             ['id' => 'todo', 'label' => 'Por hacer'],
 *             ['id' => 'doing', 'label' => 'En curso', 'color' => 'warning'],
 *             ['id' => 'done', 'label' => 'Hecho', 'color' => 'success'],
 *         ];
 *     }
 *
 *     public function cards(): array
 *     {
 *         return Task::orderBy('position')->get()
 *             ->map(fn ($t) => ['id' => $t->id, 'column' => $t->status, 'title' => $t->title])
 *             ->all();
 *     }
 *
 *     protected function persistMove($cardId, $toColumn, int $position): void
 *     {
 *         Task::whereKey($cardId)->update(['status' => $toColumn, 'position' => $position]);
 *     }
 * }
 * ```
 */
abstract class KoreKanban extends Component
{
    /**
     * Column definitions: [['id' => string, 'label' => string, 'color' => ?string], ...].
     */
    abstract public function columns(): array;

    /**
     * Card definitions: [['id' => mixed, 'column' => string, 'title' => string, ...], ...].
     */
    abstract public function cards(): array;

    /**
     * Persist a card that was dropped: it now belongs to $toColumn at $position.
     * Called by moveCard() on every drop (both within and across columns).
     */
    abstract protected function persistMove(string|int $cardId, string|int $toColumn, int $position): void;

    /**
     * Invoked from the board via `$wire.moveCard($item, $position, '<columnId>')` when a
     * card is dropped. $toColumn is the destination column (known at render time).
     */
    public function moveCard(string|int $cardId, int $position, string|int $toColumn): void
    {
        $this->persistMove($cardId, $toColumn, $position);
    }

    public function render()
    {
        return view('kore::kanban.index', [
            'koreColumns' => $this->columns(),
            'koreCards' => collect($this->cards()),
        ]);
    }
}

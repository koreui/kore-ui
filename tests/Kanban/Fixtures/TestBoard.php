<?php

namespace KoreUi\Tests\Kanban\Fixtures;

use KoreUi\Kanban\KoreKanban;

class TestBoard extends KoreKanban
{
    public array $items = [
        ['id' => 1, 'column' => 'todo', 'title' => 'Tarea 1'],
        ['id' => 2, 'column' => 'todo', 'title' => 'Tarea 2'],
        ['id' => 3, 'column' => 'doing', 'title' => 'Tarea 3'],
    ];

    public function columns(): array
    {
        return [
            ['id' => 'todo', 'label' => 'Por hacer'],
            ['id' => 'doing', 'label' => 'En curso', 'color' => 'warning'],
            ['id' => 'done', 'label' => 'Hecho', 'color' => 'success'],
        ];
    }

    public function cards(): array
    {
        return $this->items;
    }

    protected function persistMove(string|int $cardId, string|int $toColumn, int $position): void
    {
        $this->items = collect($this->items)
            ->map(function ($card) use ($cardId, $toColumn) {
                if ($card['id'] == $cardId) {
                    $card['column'] = $toColumn;
                }

                return $card;
            })
            ->all();
    }
}

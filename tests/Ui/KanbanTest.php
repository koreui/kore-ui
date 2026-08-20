<?php

$columns = "[['id' => 'todo', 'label' => 'Por hacer'], ['id' => 'done', 'label' => 'Hecho', 'color' => 'success']]";
$cards = "[['id' => 1, 'column' => 'todo', 'title' => 'Tarea A'], ['id' => 2, 'column' => 'done', 'title' => 'Tarea B']]";

it('renders the columns and their labels', function () use ($columns, $cards) {
    $view = $this->blade("<x-kore::kanban :columns=\"{$columns}\" :cards=\"{$cards}\" />");
    $view->assertSee('Por hacer')
        ->assertSee('Hecho');
});

it('renders cards grouped by column', function () use ($columns, $cards) {
    $view = $this->blade("<x-kore::kanban :columns=\"{$columns}\" :cards=\"{$cards}\" />");
    $view->assertSee('Tarea A')
        ->assertSee('Tarea B');
});

it('emits x-sort groups and items for dragging', function () use ($columns, $cards) {
    $view = $this->blade("<x-kore::kanban :columns=\"{$columns}\" :cards=\"{$cards}\" />");
    $view->assertSee('x-sort:group="kanban"', false)
        // Entrecomillado: `x-sort:item` es una EXPRESIÓN de JavaScript para
        // Alpine, y un id sin comillas se lee como una resta de variables. Este
        // test asertaba el valor pelado —que es justo lo que fallaba— y por eso
        // pasaba en verde mientras un tablero con ids de texto soltaba un
        // ReferenceError por tarjeta.
        ->assertSee("x-sort:item=\"'1'\"", false);
});

it('quotes card ids so Alpine does not read them as variables', function () {
    // El control: con un id de TEXTO, sin comillas Alpine evaluaría `tarea - a`.
    $columns = "[['id' => 'todo', 'label' => 'Por hacer']]";
    $cards = "[['id' => 'tarea-a', 'column' => 'todo', 'title' => 'Tarea A']]";

    $view = $this->blade("<x-kore::kanban :columns=\"{$columns}\" :cards=\"{$cards}\" />");

    $view->assertSee("x-sort:item=\"'tarea-a'\"", false)
        ->assertDontSee('x-sort:item="tarea-a"', false);
});

it('calls the handler with the destination column id', function () use ($columns, $cards) {
    $view = $this->blade("<x-kore::kanban :columns=\"{$columns}\" :cards=\"{$cards}\" handler=\"moveCard\" />");
    $view->assertSee("moveCard(\$item, \$position, 'todo')", false);
});

it('shows a colored dot for columns with a color', function () use ($columns, $cards) {
    $view = $this->blade("<x-kore::kanban :columns=\"{$columns}\" :cards=\"{$cards}\" />");
    $view->assertSee('bg-kore-success', false);
});

it('supports a custom card slot', function () {
    $view = $this->blade(<<<'BLADE'
        <x-kore::kanban.card :card="['id' => 9]">
            <span>Contenido custom</span>
        </x-kore::kanban.card>
    BLADE);
    $view->assertSee('Contenido custom');
});

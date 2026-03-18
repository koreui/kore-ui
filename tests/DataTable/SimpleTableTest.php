<?php

it('renders a simple table with headers and rows', function () {
    $view = $this->blade('
        <x-kore::table
            :headers="$headers"
            :rows="$rows"
        />
    ', [
        'headers' => [
            ['key' => 'name', 'label' => 'Nombre'],
            ['key' => 'email', 'label' => 'Email'],
        ],
        'rows' => [
            ['name' => 'John', 'email' => 'john@test.com'],
            ['name' => 'Jane', 'email' => 'jane@test.com'],
        ],
    ]);

    $view->assertSee('Nombre')
        ->assertSee('Email')
        ->assertSee('John')
        ->assertSee('john@test.com')
        ->assertSee('Jane')
        ->assertSee('jane@test.com');
});

it('renders empty state when no rows', function () {
    $view = $this->blade('
        <x-kore::table
            :headers="$headers"
            :rows="[]"
        />
    ', [
        'headers' => [
            ['key' => 'name', 'label' => 'Nombre'],
        ],
    ]);

    $view->assertSee('No se encontraron resultados');
});

it('renders custom empty text', function () {
    $view = $this->blade('
        <x-kore::table
            :headers="$headers"
            :rows="[]"
            empty-text="Sin datos"
        />
    ', [
        'headers' => [
            ['key' => 'name', 'label' => 'Nombre'],
        ],
    ]);

    $view->assertSee('Sin datos');
});

it('renders striped rows', function () {
    $view = $this->blade('
        <x-kore::table
            :headers="$headers"
            :rows="$rows"
            :striped="true"
        />
    ', [
        'headers' => [
            ['key' => 'name', 'label' => 'Nombre'],
        ],
        'rows' => [
            ['name' => 'A'],
            ['name' => 'B'],
        ],
    ]);

    $view->assertSee('bg-kore-muted/30', false);
});

it('renders without headers when headerless', function () {
    $view = $this->blade('
        <x-kore::table
            :headers="$headers"
            :rows="$rows"
            :headerless="true"
        />
    ', [
        'headers' => [
            ['key' => 'name', 'label' => 'Nombre'],
        ],
        'rows' => [
            ['name' => 'John'],
        ],
    ]);

    $view->assertDontSee('<thead', false);
    $view->assertSee('John');
});

it('renders compact density', function () {
    $view = $this->blade('
        <x-kore::table
            :headers="$headers"
            :rows="$rows"
            :compact="true"
        />
    ', [
        'headers' => [
            ['key' => 'name', 'label' => 'Nombre'],
        ],
        'rows' => [
            ['name' => 'John'],
        ],
    ]);

    $view->assertSee('py-1 ', false);
});

it('renders bordered container', function () {
    $view = $this->blade('
        <x-kore::table
            :headers="$headers"
            :rows="$rows"
            :bordered="true"
        />
    ', [
        'headers' => [
            ['key' => 'name', 'label' => 'Nombre'],
        ],
        'rows' => [
            ['name' => 'John'],
        ],
    ]);

    $view->assertSee('border-kore-border', false);
});

it('supports Collection as rows', function () {
    $view = $this->blade('
        <x-kore::table
            :headers="$headers"
            :rows="$rows"
        />
    ', [
        'headers' => [
            ['key' => 'name', 'label' => 'Nombre'],
        ],
        'rows' => collect([
            ['name' => 'John'],
            ['name' => 'Jane'],
        ]),
    ]);

    $view->assertSee('John')
        ->assertSee('Jane');
});

it('renders with custom slot for cell', function () {
    $view = $this->blade('
        <x-kore::table
            :headers="$headers"
            :rows="$rows"
        >
            <x-slot:cell-name>
                <strong>Custom</strong>
            </x-slot:cell-name>
        </x-kore::table>
    ', [
        'headers' => [
            ['key' => 'name', 'label' => 'Nombre'],
        ],
        'rows' => [
            ['name' => 'John'],
        ],
    ]);

    $view->assertSee('<strong>Custom</strong>', false);
});

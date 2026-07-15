<?php

it('renders a title and description', function () {
    $view = $this->blade('<x-kore::descriptions title="Usuario" description="Detalle del registro" />');
    $view->assertSee('Usuario')
        ->assertSee('Detalle del registro');
});

it('renders items from the data-driven API', function () {
    $view = $this->blade(
        '<x-kore::descriptions :items="$items" />',
        ['items' => [
            ['label' => 'Nombre', 'value' => 'Oscar Villa'],
            ['label' => 'Email', 'value' => 'o@mail.com'],
        ]]
    );

    $view->assertSee('Nombre')
        ->assertSee('Oscar Villa')
        ->assertSee('Email')
        ->assertSee('o@mail.com');
});

it('renders subcomponent items with rich slot content', function () {
    $view = $this->blade(<<<'BLADE'
        <x-kore::descriptions>
            <x-kore::descriptions.item label="Estado">
                <x-kore::badge label="Activo" color="success" />
            </x-kore::descriptions.item>
        </x-kore::descriptions>
    BLADE);

    $view->assertSee('Estado')
        ->assertSee('Activo')
        ->assertSee('bg-kore-success/10', false);
});

it('renders as a description list', function () {
    $view = $this->blade('<x-kore::descriptions :items="[[\'label\' => \'A\', \'value\' => \'1\']]" />');
    $view->assertSee('<dl', false)
        ->assertSee('<dt', false)
        ->assertSee('<dd', false);
});

it('applies the columns grid class', function () {
    $view = $this->blade('<x-kore::descriptions :columns="2" :items="[[\'label\' => \'A\', \'value\' => \'1\']]" />');
    $view->assertSee('sm:grid-cols-2', false);
});

it('renders the bordered variant', function () {
    $view = $this->blade('<x-kore::descriptions :bordered="true" :items="[[\'label\' => \'A\', \'value\' => \'1\']]" />');
    $view->assertSee('border-kore-border', false)
        ->assertSee('bg-kore-surface', false);
});

it('inherits vertical layout on items via @aware', function () {
    $view = $this->blade(<<<'BLADE'
        <x-kore::descriptions layout="vertical">
            <x-kore::descriptions.item label="Nombre" value="Oscar" />
        </x-kore::descriptions>
    BLADE);

    // Vertical layout stacks label above value: label gets a bottom margin
    $view->assertSee('mb-1', false);
});

it('renders an icon in the item label', function () {
    $view = $this->blade('<x-kore::descriptions :items="[[\'label\' => \'Ubicación\', \'value\' => \'MX\', \'icon\' => \'map-pin\']]" />');
    $view->assertSee('<svg', false);
});

it('applies span classes on items', function () {
    $view = $this->blade('<x-kore::descriptions :columns="2" :items="[[\'label\' => \'Bio\', \'value\' => \'...\', \'span\' => 2]]" />');
    $view->assertSee('sm:col-span-2', false);
});

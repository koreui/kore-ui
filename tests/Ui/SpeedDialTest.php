<?php

it('renders the container', function () {
    $view = $this->blade('<x-kore::speed-dial />');
    $view->assertSee('KoreSpeedDial', false);
});

it('renders the FAB button', function () {
    $view = $this->blade('<x-kore::speed-dial />');
    $view->assertSee('aria-haspopup="true"', false)
        ->assertSee('Acciones rápidas', false);
});

it('renders default plus icon', function () {
    $view = $this->blade('<x-kore::speed-dial />');
    $view->assertSee('<svg', false);
});

it('renders custom icon', function () {
    $view = $this->blade('<x-kore::speed-dial icon="settings" />');
    $view->assertSee('<svg', false);
});

it('renders items with icons', function () {
    $view = $this->blade('<x-kore::speed-dial :items="[
        [\'icon\' => \'edit\', \'label\' => \'Edit\'],
        [\'icon\' => \'trash\', \'label\' => \'Delete\'],
    ]" />');
    $view->assertSee('Edit')
        ->assertSee('Delete');
});

it('renders tooltips for items', function () {
    $view = $this->blade('<x-kore::speed-dial :items="[
        [\'icon\' => \'edit\', \'label\' => \'Edit item\'],
    ]" />');
    $view->assertSee('Edit item');
});

it('renders up direction by default', function () {
    $view = $this->blade('<x-kore::speed-dial />');
    $view->assertSee('flex-col-reverse', false);
});

it('renders down direction', function () {
    $view = $this->blade('<x-kore::speed-dial direction="down" />');
    $view->assertSee("direction: 'down'", false);
});

it('renders left direction', function () {
    $view = $this->blade('<x-kore::speed-dial direction="left" />');
    $view->assertSee('flex-row-reverse', false);
});

it('renders right direction', function () {
    $view = $this->blade('<x-kore::speed-dial direction="right" />');
    $view->assertSee('flex-row', false);
});

it('renders fixed position', function () {
    $view = $this->blade('<x-kore::speed-dial position="bottom-right" />');
    $view->assertSee('fixed', false)
        ->assertSee('bottom-6', false)
        ->assertSee('right-6', false);
});

it('renders primary color by default', function () {
    $view = $this->blade('<x-kore::speed-dial />');
    $view->assertSee('bg-kore-primary', false);
});

it('renders sm size by default', function () {
    $view = $this->blade('<x-kore::speed-dial />');
    $view->assertSee('size-10', false);
});


/**
 * El `role="menuitem"` estaba en el `<div>` envoltorio, con el botón DENTRO.
 * Un menuitem no puede contener un control: la relación se rompe y la acción no
 * se anuncia como activable. Medido: tres `menuitem`, los tres DIV.
 */
it('pone el rol de item en el control, no en el envoltorio', function () {
    $items = "[['icon' => 'plus', 'label' => 'Nuevo'], ['icon' => 'link', 'label' => 'Ir', 'href' => '#x']]";
    $view = $this->blade("<x-kore::speed-dial :items=\"{$items}\" />");

    $view->assertSee('<div class="relative group" role="none">', false)
        ->assertSee('role="menuitem"', false);
});

/** Un `role="menu"` sin nombre se anuncia como «menú» y nada más. */
it('nombra el menú', function () {
    $view = $this->blade('<x-kore::speed-dial />');
    $view->assertSee('role="menu"', false)
        ->assertSee('aria-label="Acciones rápidas"', false);
});

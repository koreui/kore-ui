<?php

it('renders the container', function () {
    $view = $this->blade('<x-kore::speed-dial />');
    $view->assertSee('KoreSpeedDial', false);
});

it('renders the FAB button', function () {
    $view = $this->blade('<x-kore::speed-dial />');
    $view->assertSee('aria-haspopup="true"', false)
        ->assertSee('Speed dial menu', false);
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

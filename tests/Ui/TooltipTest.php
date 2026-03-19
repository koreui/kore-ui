<?php

it('renders with text', function () {
    $view = $this->blade('<x-kore::tooltip text="Help text"><button>Hover</button></x-kore::tooltip>');
    $view->assertSee('Help text')
        ->assertSee('Hover');
});

it('has role tooltip', function () {
    $view = $this->blade('<x-kore::tooltip text="Info"><span>?</span></x-kore::tooltip>');
    $view->assertSee('role="tooltip"', false);
});

it('renders with alpine data', function () {
    $view = $this->blade('<x-kore::tooltip text="Info"><span>?</span></x-kore::tooltip>');
    $view->assertSee('x-data=', false)
        ->assertSee('x-show="show"', false);
});

it('renders top position by default', function () {
    $view = $this->blade('<x-kore::tooltip text="Info"><span>?</span></x-kore::tooltip>');
    $view->assertSee("placement: 'top'", false);
});

it('renders bottom position', function () {
    $view = $this->blade('<x-kore::tooltip text="Info" position="bottom"><span>?</span></x-kore::tooltip>');
    $view->assertSee("placement: 'bottom'", false);
});

it('renders left position', function () {
    $view = $this->blade('<x-kore::tooltip text="Info" position="left"><span>?</span></x-kore::tooltip>');
    $view->assertSee("placement: 'left'", false);
});

it('renders right position', function () {
    $view = $this->blade('<x-kore::tooltip text="Info" position="right"><span>?</span></x-kore::tooltip>');
    $view->assertSee("placement: 'right'", false);
});

it('renders with custom delay', function () {
    $view = $this->blade('<x-kore::tooltip text="Info" :delay="500"><span>?</span></x-kore::tooltip>');
    $view->assertSee('500', false);
});

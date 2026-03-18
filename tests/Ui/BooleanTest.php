<?php

it('renders true value with success color', function () {
    $view = $this->blade('<x-kore::boolean :value="true" />');
    $view->assertSee('text-kore-success', false);
});

it('renders false value with destructive color', function () {
    $view = $this->blade('<x-kore::boolean :value="false" />');
    $view->assertSee('text-kore-destructive', false);
});

it('renders check icon for true', function () {
    $view = $this->blade('<x-kore::boolean :value="true" />');
    $view->assertSee('<svg', false);
});

it('renders x icon for false', function () {
    $view = $this->blade('<x-kore::boolean :value="false" />');
    $view->assertSee('<svg', false);
});

it('renders custom true icon', function () {
    $view = $this->blade('<x-kore::boolean :value="true" trueIcon="check-circle" />');
    $view->assertSee('<svg', false);
});

it('renders custom colors', function () {
    $view = $this->blade('<x-kore::boolean :value="true" trueColor="primary" />');
    $view->assertSee('text-kore-primary', false);
});

it('renders sm size', function () {
    $view = $this->blade('<x-kore::boolean :value="true" size="sm" />');
    $view->assertSee('size-4', false);
});

it('renders lg size', function () {
    $view = $this->blade('<x-kore::boolean :value="true" size="lg" />');
    $view->assertSee('size-6', false);
});

it('has role img', function () {
    $view = $this->blade('<x-kore::boolean :value="true" />');
    $view->assertSee('role="img"', false);
});

it('has aria-label true', function () {
    $view = $this->blade('<x-kore::boolean :value="true" />');
    $view->assertSee('aria-label="true"', false);
});

it('has aria-label false', function () {
    $view = $this->blade('<x-kore::boolean :value="false" />');
    $view->assertSee('aria-label="false"', false);
});

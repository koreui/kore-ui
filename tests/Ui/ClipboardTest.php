<?php

it('renders input variant by default', function () {
    $view = $this->blade('<x-kore::clipboard text="hello" />');
    $view->assertSee('KoreClipboard', false)
        ->assertSee('value="hello"', false);
});

it('renders with label', function () {
    $view = $this->blade('<x-kore::clipboard text="abc" label="API Key" />');
    $view->assertSee('API Key');
});

it('renders inline variant', function () {
    $view = $this->blade('<x-kore::clipboard text="code123" variant="inline" />');
    $view->assertSee('code123');
});

it('renders icon variant', function () {
    $view = $this->blade('<x-kore::clipboard text="secret" variant="icon" />');
    $view->assertSee('Copy to clipboard', false);
});

it('renders secret mode with password input', function () {
    $view = $this->blade('<x-kore::clipboard text="s3cret" :secret="true" />');
    $view->assertSee('type="password"', false);
});

it('renders secret inline with masked text', function () {
    $view = $this->blade('<x-kore::clipboard text="s3cret" variant="inline" :secret="true" />');
    $view->assertSee('••••••••');
});

it('renders copy button', function () {
    $view = $this->blade('<x-kore::clipboard text="copy me" />');
    $view->assertSee('copy()', false);
});

it('renders Alpine clipboard component', function () {
    $view = $this->blade('<x-kore::clipboard text="test" />');
    $view->assertSee('x-data="KoreClipboard', false);
});

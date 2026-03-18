<?php

it('renders native select with options', function () {
    $view = $this->blade('
        <x-kore::select
            label="Country"
            name="country"
            :options="[\'mx\' => \'Mexico\', \'us\' => \'USA\']"
            native
        />
    ');

    $view->assertSee('Country')
        ->assertSee('<select', false)
        ->assertSee('Mexico')
        ->assertSee('USA');
});

it('renders native select with placeholder', function () {
    $view = $this->blade('
        <x-kore::select
            label="Country"
            name="country"
            :options="[\'mx\' => \'Mexico\']"
            placeholder="Select a country"
            native
        />
    ');

    $view->assertSee('Select a country');
});

it('renders custom select trigger', function () {
    $view = $this->blade('
        <x-kore::select
            label="Status"
            name="status"
            :options="[\'active\' => \'Active\', \'inactive\' => \'Inactive\']"
            placeholder="Choose..."
        />
    ');

    $view->assertSee('Status')
        ->assertSee('KoreSelect', false)
        ->assertSee('Choose...');
});

it('renders searchable input', function () {
    $view = $this->blade('
        <x-kore::select
            label="User"
            name="user"
            :options="[]"
            searchable
        />
    ');

    $view->assertSee('x-ref="search"', false)
        ->assertSee('placeholder="Search..."', false);
});

it('renders clearable button', function () {
    $view = $this->blade('
        <x-kore::select
            label="Status"
            name="status"
            :options="[\'a\' => \'A\']"
            clearable
        />
    ');

    $view->assertSee('clear()', false);
});

it('renders disabled state', function () {
    $view = $this->blade('
        <x-kore::select
            label="Status"
            name="status"
            :options="[\'a\' => \'A\']"
            disabled
        />
    ');

    $view->assertSee('disabled', false)
        ->assertSee('opacity-50', false);
});

it('renders native grouped options', function () {
    $view = $this->blade('
        <x-kore::select
            label="City"
            name="city"
            :options="[\'North\' => [\'mty\' => \'Monterrey\'], \'South\' => [\'oax\' => \'Oaxaca\']]"
            grouped
            native
        />
    ');

    $view->assertSee('<optgroup', false)
        ->assertSee('Monterrey')
        ->assertSee('Oaxaca');
});

it('forwards wire:model on hidden input', function () {
    $view = $this->blade('
        <x-kore::select
            label="Status"
            :options="[\'a\' => \'A\']"
            wire:model="status"
        />
    ');

    $view->assertSee('wire:model="status"', false);
});

it('renders creatable config in KoreSelect', function () {
    $view = $this->blade('
        <x-kore::select
            label="Tags"
            :options="[\'a\' => \'A\']"
            creatable
        />
    ');

    $view->assertSee('creatable: true', false);
});

it('auto-enables search input when creatable', function () {
    $view = $this->blade('
        <x-kore::select
            label="Tags"
            :options="[\'a\' => \'A\']"
            creatable
        />
    ');

    $view->assertSee('x-ref="search"', false);
});

it('renders create option template when creatable', function () {
    $view = $this->blade('
        <x-kore::select
            label="Tags"
            :options="[\'a\' => \'A\']"
            creatable
        />
    ');

    $view->assertSee('createFromSearch()', false);
});

it('does not render create option when not creatable', function () {
    $view = $this->blade('
        <x-kore::select
            label="Tags"
            :options="[\'a\' => \'A\']"
        />
    ');

    $view->assertDontSee('createFromSearch()', false);
});

it('ignores creatable in native mode', function () {
    $view = $this->blade('
        <x-kore::select
            label="Tags"
            :options="[\'a\' => \'A\']"
            creatable
            native
        />
    ');

    $view->assertDontSee('createFromSearch()', false);
});

it('renders with multiple and creatable', function () {
    $view = $this->blade('
        <x-kore::select
            label="Tags"
            :options="[\'a\' => \'A\']"
            creatable
            multiple
        />
    ');

    $view->assertSee('creatable: true', false)
        ->assertSee('multiple: true', false);
});

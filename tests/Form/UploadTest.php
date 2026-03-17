<?php

// --- Rendering ---

it('renders with label and name', function () {
    $view = $this->blade('<x-kore::upload label="Avatar" name="avatar" />');

    $view->assertSee('Avatar')
        ->assertSee('name="avatar"', false)
        ->assertSee('KoreUpload', false);
});

it('renders dropzone by default', function () {
    $view = $this->blade('<x-kore::upload label="File" name="file" />');

    $view->assertSee('Drag & drop', false)
        ->assertSee('browse')
        ->assertSee('role="button"', false);
});

it('renders upload icon area in dropzone', function () {
    $view = $this->blade('<x-kore::upload name="file" />');

    $view->assertSee('size-8 mx-auto text-kore-muted-fg mb-2', false);
});

it('forwards wire:model on file input', function () {
    $view = $this->blade('<x-kore::upload label="Avatar" wire:model="avatar" />');

    $view->assertSee('wire:model="avatar"', false);
});

// --- Variants ---

it('renders button variant', function () {
    $view = $this->blade('<x-kore::upload label="File" name="file" variant="button" />');

    $view->assertSee('Choose file')
        ->assertDontSee('Drag & drop');
});

it('renders dropzone variant as default', function () {
    $view = $this->blade('<x-kore::upload label="File" name="file" />');

    $view->assertSee('Drag & drop', false);
});

// --- Error handling ---

it('shows error from errors bag', function () {
    $this->withViewErrors(['avatar' => 'The avatar is required.']);

    $view = $this->blade('<x-kore::upload label="Avatar" name="avatar" />');

    $view->assertSee('The avatar is required.')
        ->assertSee('role="alert"', false);
});

it('shows manual error prop', function () {
    $view = $this->blade('<x-kore::upload label="Avatar" name="avatar" error="Invalid file" />');

    $view->assertSee('Invalid file')
        ->assertSee('role="alert"', false);
});

it('renders destructive border on dropzone when error', function () {
    $this->withViewErrors(['avatar' => 'Required']);

    $view = $this->blade('<x-kore::upload label="Avatar" name="avatar" />');

    $view->assertSee('border-kore-destructive', false);
});

it('hides error when show-error is false', function () {
    $this->withViewErrors(['avatar' => 'The avatar is required.']);

    $view = $this->blade('<x-kore::upload label="Avatar" name="avatar" :show-error="false" />');

    $view->assertDontSee('The avatar is required.');
});

// --- File input attributes ---

it('passes accept attribute to file input', function () {
    $view = $this->blade('<x-kore::upload name="file" accept="image/*,.pdf" />');

    $view->assertSee('accept="image/*,.pdf"', false);
});

it('passes multiple attribute to file input', function () {
    $view = $this->blade('<x-kore::upload name="files" multiple />');

    $view->assertSee('multiple', false);
});

it('applies disabled state', function () {
    $view = $this->blade('<x-kore::upload label="File" name="file" disabled />');

    $view->assertSee('disabled', false)
        ->assertSee('opacity-50', false);
});

it('passes name to file input', function () {
    $view = $this->blade('<x-kore::upload name="document" />');

    $view->assertSee('name="document"', false);
});

// --- JS config props ---

it('passes maxSize to JS config', function () {
    $view = $this->blade('<x-kore::upload name="file" :max-size="5" />');

    $view->assertSee('&quot;maxSize&quot;:5', false);
});

it('passes maxFiles to JS config', function () {
    $view = $this->blade('<x-kore::upload name="files" :max-files="3" />');

    $view->assertSee('&quot;maxFiles&quot;:3', false);
});

it('passes deletable and deleteMethod to JS config', function () {
    $view = $this->blade('<x-kore::upload name="file" deletable delete-method="removeFile" />');

    $view->assertSee('&quot;deletable&quot;:true', false)
        ->assertSee('&quot;deleteMethod&quot;:&quot;removeFile&quot;', false);
});

it('renders delete button when deletable', function () {
    $view = $this->blade('<x-kore::upload name="file" deletable />');

    $view->assertSee('removeFile(file.id)', false);
});

it('does not render delete button when not deletable', function () {
    $view = $this->blade('<x-kore::upload name="file" />');

    $view->assertDontSee('removeFile(file.id)', false);
});

// --- Accessibility ---

it('has role=button on dropzone', function () {
    $view = $this->blade('<x-kore::upload name="file" />');

    $view->assertSee('role="button"', false);
});

it('has role=list on file list', function () {
    $view = $this->blade('<x-kore::upload name="file" />');

    $view->assertSee('role="list"', false);
});

it('has tabindex on dropzone', function () {
    $view = $this->blade('<x-kore::upload name="file" />');

    $view->assertSee('tabindex="0"', false);
});

// --- Required ---

it('shows required indicator in label', function () {
    $view = $this->blade('<x-kore::upload label="Avatar" name="avatar" required />');

    $view->assertSee('*');
});

// --- Hint ---

it('shows hint when no error', function () {
    $view = $this->blade('<x-kore::upload label="Avatar" name="avatar" hint="Max 5 MB" />');

    $view->assertSee('Max 5 MB');
});

it('hides hint when error present', function () {
    $this->withViewErrors(['avatar' => 'Required']);

    $view = $this->blade('<x-kore::upload label="Avatar" name="avatar" hint="Max 5 MB" />');

    $view->assertSee('Required')
        ->assertDontSee('Max 5 MB');
});

// --- Static mode ---

it('does not render dropzone in static mode', function () {
    $view = $this->blade('<x-kore::upload name="file" static />');

    $view->assertDontSee('Drag & drop')
        ->assertDontSee('Choose file');
});

// --- Clearable ---

it('renders clear all button when clearable', function () {
    $view = $this->blade('<x-kore::upload name="file" clearable />');

    $view->assertSee('Clear all');
});

it('does not render clear all button when not clearable', function () {
    $view = $this->blade('<x-kore::upload name="file" />');

    $view->assertDontSee('Clear all');
});

// --- Paste ---

it('binds paste event on root div', function () {
    $view = $this->blade('<x-kore::upload name="file" />');

    $view->assertSee('onPaste($event)', false);
});

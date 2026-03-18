<?php

it('renders accordion container', function () {
    $view = $this->blade('<x-kore::accordion>Content</x-kore::accordion>');
    $view->assertSee('Content');
});

it('renders with x-data KoreAccordion', function () {
    $view = $this->blade('<x-kore::accordion>Content</x-kore::accordion>');
    $view->assertSee('KoreAccordion', false);
});

it('renders bordered variant', function () {
    $view = $this->blade('<x-kore::accordion variant="bordered">Content</x-kore::accordion>');
    $view->assertSee('border-kore-border', false);
});

it('renders flush variant', function () {
    $view = $this->blade('<x-kore::accordion variant="flush">Content</x-kore::accordion>');
    $view->assertSee('divide-y', false);
});

it('renders separated variant', function () {
    $view = $this->blade('<x-kore::accordion variant="separated">Content</x-kore::accordion>');
    $view->assertSee('space-y-3', false);
});

it('renders item with title', function () {
    $view = $this->blade('<x-kore::accordion.item title="Section 1">Content</x-kore::accordion.item>');
    $view->assertSee('Section 1');
});

it('renders item header button', function () {
    $view = $this->blade('<x-kore::accordion.item title="Section">Content</x-kore::accordion.item>');
    $view->assertSee('<button', false);
});

it('renders item with icon', function () {
    $view = $this->blade('<x-kore::accordion.item title="Section" icon="star">Content</x-kore::accordion.item>');
    $view->assertSee('<svg', false);
});

it('renders disabled item', function () {
    $view = $this->blade('<x-kore::accordion.item title="Section" :disabled="true">Content</x-kore::accordion.item>');
    $view->assertSee('disabled', false);
});

it('renders multiple mode', function () {
    $view = $this->blade('<x-kore::accordion :multiple="true">Content</x-kore::accordion>');
    $view->assertSee('multiple: true', false);
});

it('renders aria-controls on item button', function () {
    $view = $this->blade('<x-kore::accordion.item id="test" title="Section">Content</x-kore::accordion.item>');
    $view->assertSee('aria-controls="panel-test"', false);
});

it('renders region role on panel', function () {
    $view = $this->blade('<x-kore::accordion.item title="Section">Content</x-kore::accordion.item>');
    $view->assertSee('role="region"', false);
});

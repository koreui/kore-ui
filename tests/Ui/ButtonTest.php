<?php

it('renders with label', function () {
    $view = $this->blade('<x-kore::button label="Click me" />');
    $view->assertSee('Click me');
});

it('renders as button by default', function () {
    $view = $this->blade('<x-kore::button label="Save" />');
    $view->assertSee('<button', false)
        ->assertSee('type="button"', false);
});

it('renders as anchor when href provided', function () {
    $view = $this->blade('<x-kore::button label="Go" href="https://example.com" />');
    $view->assertSee('<a', false)
        ->assertSee('href="https://example.com"', false);
});

it('renders with submit type', function () {
    $view = $this->blade('<x-kore::button label="Submit" type="submit" />');
    $view->assertSee('type="submit"', false);
});

it('renders solid variant by default', function () {
    $view = $this->blade('<x-kore::button label="Test" />');
    $view->assertSee('bg-kore-primary', false);
});

it('renders outline variant', function () {
    $view = $this->blade('<x-kore::button label="Test" variant="outline" />');
    $view->assertSee('border-kore-primary', false);
});

it('renders ghost variant', function () {
    $view = $this->blade('<x-kore::button label="Test" variant="ghost" />');
    $view->assertSee('hover:bg-kore-primary/10', false);
});

it('renders soft variant', function () {
    $view = $this->blade('<x-kore::button label="Test" variant="soft" />');
    $view->assertSee('bg-kore-primary/10', false);
});

it('renders link variant', function () {
    $view = $this->blade('<x-kore::button label="Test" variant="link" />');
    $view->assertSee('underline-offset-4', false);
});

it('renders destructive color', function () {
    $view = $this->blade('<x-kore::button label="Delete" color="destructive" />');
    $view->assertSee('bg-kore-destructive', false);
});

it('renders small size', function () {
    $view = $this->blade('<x-kore::button label="Small" size="sm" />');
    $view->assertSee('text-xs', false);
});

it('renders medium size by default', function () {
    $view = $this->blade('<x-kore::button label="Medium" />');
    $view->assertSee('text-sm', false);
});

it('renders large size', function () {
    $view = $this->blade('<x-kore::button label="Large" size="lg" />');
    $view->assertSee('text-base', false);
});

it('renders icon', function () {
    $view = $this->blade('<x-kore::button label="Save" icon="save" />');
    $view->assertSee('<svg', false)
        ->assertSee('size-4', false);
});

it('renders right icon', function () {
    $view = $this->blade('<x-kore::button label="Next" icon-right="arrow-right" />');
    $view->assertSee('<svg', false);
});

it('renders loading state', function () {
    $view = $this->blade('<x-kore::button label="Save" :loading="true" />');
    $view->assertSee('animate-spin', false)
        ->assertSee('aria-busy="true"', false);
});

it('renders disabled state', function () {
    $view = $this->blade('<x-kore::button label="Save" :disabled="true" />');
    $view->assertSee('disabled', false);
});

it('renders block width', function () {
    $view = $this->blade('<x-kore::button label="Full" :block="true" />');
    $view->assertSee('w-full', false);
});

it('renders slot content', function () {
    $view = $this->blade('<x-kore::button>Custom Content</x-kore::button>');
    $view->assertSee('Custom Content');
});

it('renders button group', function () {
    $view = $this->blade('
        <x-kore::button-group>
            <x-kore::button label="A" />
            <x-kore::button label="B" />
        </x-kore::button-group>
    ');
    $view->assertSee('A')
        ->assertSee('B');
});

/**
 * Las variantes soft, outline, ghost y link pintan el color COMO TEXTO, así que
 * usan el token `-text`. El botón se había quedado fuera del arreglo que ya
 * tenían el badge y el chip para `warning`: medido, `soft warning` daba 2,06.
 */
it('usa los tokens de texto donde el color es el texto', function () {
    foreach (['soft', 'outline', 'ghost'] as $variante) {
        foreach (['primary', 'success', 'info', 'destructive', 'warning'] as $color) {
            $this->blade('<x-kore::button variant="'.$variante.'" color="'.$color.'" label="X" />')
                ->assertSee('text-kore-'.$color.'-text', false);
        }
    }
});

/** El spinner es decorativo y se atenúa con `prefers-reduced-motion`. */
it('marca el spinner para reduced-motion y lo oculta al lector', function () {
    $view = $this->blade('<x-kore::button label="X" :loading="true" />');
    $view->assertSee('kore-anim-spinner', false)
        ->assertSee('aria-hidden="true"', false)
        ->assertSee('aria-busy="true"', false);
});

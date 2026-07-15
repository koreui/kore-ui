<?php

it('renders title and description', function () {
    $view = $this->blade('<x-kore::result status="success" title="¡Listo!" description="Operación completada." />');
    $view->assertSee('¡Listo!')
        ->assertSee('Operación completada.');
});

it('renders the success status with its color', function () {
    $view = $this->blade('<x-kore::result status="success" title="OK" />');
    $view->assertSee('text-kore-success', false)
        ->assertSee('bg-kore-success/10', false)
        ->assertSee('<svg', false);
});

it('renders the error status with destructive color', function () {
    $view = $this->blade('<x-kore::result status="error" title="Error" />');
    $view->assertSee('text-kore-destructive', false)
        ->assertSee('bg-kore-destructive/10', false);
});

it('renders the warning status with warning-text color', function () {
    $view = $this->blade('<x-kore::result status="warning" title="Cuidado" />');
    $view->assertSee('text-kore-warning-text', false)
        ->assertSee('bg-kore-warning/10', false);
});

it('renders the 404 status with muted color', function () {
    $view = $this->blade('<x-kore::result status="404" title="No encontrado" />');
    $view->assertSee('text-kore-muted-fg', false)
        ->assertSee('bg-kore-muted', false);
});

it('renders the info status by default', function () {
    $view = $this->blade('<x-kore::result title="Info" />');
    $view->assertSee('text-kore-info', false);
});

it('allows overriding the icon', function () {
    // rocket icon should resolve without error and produce an svg
    $view = $this->blade('<x-kore::result status="success" icon="rocket" title="Deploy" />');
    $view->assertSee('<svg', false);
});

it('renders the action slot', function () {
    $view = $this->blade(<<<'BLADE'
        <x-kore::result status="success" title="Pago recibido">
            <x-slot:action>
                <x-kore::button label="Volver al inicio" />
            </x-slot:action>
        </x-kore::result>
    BLADE);

    $view->assertSee('Volver al inicio');
});

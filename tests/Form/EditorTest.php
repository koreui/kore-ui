<?php

/**
 * El editor de texto enriquecido: lo que se renderiza desde el servidor.
 *
 * El comportamiento —que la negrita ponga negrita, que el pegado se limpie— vive
 * en el navegador y se prueba en la suite E2E. Aquí se comprueba el andamiaje:
 * la etiqueta, el nombre accesible, los estados y que la barra sea navegable.
 */

it('se monta sobre el campo estándar de la librería', function () {
    $view = $this->blade('<x-kore::editor label="Descripción" name="descripcion" hint="Admite formato" />');

    $view->assertSee('Descripción')
        ->assertSee('Admite formato')
        ->assertSee('KoreEditor(', false);
});

it('el área de escritura se anuncia como un campo de texto de varias líneas', function () {
    $this->blade('<x-kore::editor label="Notas" name="notas" required />')
        ->assertSee('role="textbox"', false)
        ->assertSee('aria-multiline="true"', false)
        ->assertSee('aria-required="true"', false);
});

it('la barra es una sola parada del tabulador', function () {
    // Con doce botones, lo contrario son doce pulsaciones para llegar al texto.
    $html = $this->blade('<x-kore::editor name="x" />')->__toString();

    expect($html)->toContain('role="toolbar"')
        ->and($html)->toContain('keydown.arrow-right')
        // Todos arrancan fuera del recorrido; el primero lo recupera desde JS.
        ->and(substr_count($html, 'tabindex="-1"'))->toBeGreaterThan(5);
});

it('cada botón dice lo que hace y si está activo', function () {
    $html = $this->blade('<x-kore::editor name="x" />')->__toString();

    expect($html)->toContain('aria-label="Negrita (Ctrl+B)"')
        ->and($html)->toContain('aria-pressed')
        ->and($html)->toContain('aria-label="Lista con viñetas"');
});

it('el valor viaja por un input oculto, no por el contenteditable', function () {
    // Un `contenteditable` no es un control de formulario: no tiene `value` que
    // Livewire pueda leer.
    $html = $this->blade('<x-kore::editor name="cuerpo" wire:model="cuerpo" />')->__toString();

    expect($html)->toContain('type="hidden"')
        ->and($html)->toContain('wire:model="cuerpo"')
        ->and($html)->toContain('name="cuerpo"');
});

it('protege el contenido del morph de Livewire', function () {
    // Sin `wire:ignore`, cualquier repintado del servidor reemplaza el árbol que
    // el usuario está editando y se lleva el cursor y el historial de deshacer.
    $this->blade('<x-kore::editor name="x" wire:model="x" />')->assertSee('wire:ignore', false);
});

it('en solo lectura no hay barra ni edición', function () {
    $html = $this->blade('<x-kore::editor name="x" readonly />')->__toString();

    expect($html)->toContain('contenteditable="false"')
        ->and($html)->toContain('aria-readonly="true"')
        ->and($html)->not->toContain('role="toolbar"');
});

it('deshabilitado tampoco deja escribir', function () {
    $this->blade('<x-kore::editor name="x" disabled />')
        ->assertSee('contenteditable="false"', false)
        ->assertSee('opacity-50', false);
});

it('marca el error en el área de escritura', function () {
    $this->withViewErrors(['cuerpo' => 'Escribe algo']);

    $this->blade('<x-kore::editor label="Cuerpo" name="cuerpo" />')
        ->assertSee('Escribe algo')
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('border-kore-destructive', false);
});

it('permite elegir qué botones aparecen', function () {
    $html = $this->blade('<x-kore::editor name="x" :toolbar="[\'bold\', \'italic\']" />')->__toString();

    expect($html)->toContain('aria-label="Negrita (Ctrl+B)"')
        ->and($html)->not->toContain('aria-label="Cita"');
});

it('el contador solo aparece si se pide', function () {
    $this->blade('<x-kore::editor name="x" />')->assertDontSee('x-text="caracteres"', false);

    $this->blade('<x-kore::editor name="x" counter />')->assertSee('x-text="caracteres"', false);

    // Un límite implica contador: si no, el campo deja de aceptar teclas sin
    // decir por qué.
    $this->blade('<x-kore::editor name="x" :maxlength="200" />')
        ->assertSee('x-text="caracteres"', false)
        ->assertSee('/ 200', false);
});

it('el marcador de posición no se come el clic', function () {
    // Un `contenteditable` no tiene `placeholder`: se pinta encima, y sin
    // `pointer-events-none` pinchar en él no llevaría el cursor al texto.
    $this->blade('<x-kore::editor name="x" placeholder="Escribe aquí" />')
        ->assertSee('Escribe aquí')
        ->assertSee('pointer-events-none', false);
});

it('el diálogo del enlace tiene su campo etiquetado', function () {
    $html = $this->blade('<x-kore::editor name="x" id="notas" />')->__toString();

    expect($html)->toContain('for="notas-url"')
        ->and($html)->toContain('id="notas-url"');
});

it('la etiqueta apunta al área de escritura', function () {
    // El `for` de la etiqueta tiene que caer sobre el elemento que recibe el
    // foco, que aquí no es un <input> sino el contenteditable.
    $html = $this->blade('<x-kore::editor label="Cuerpo" name="cuerpo" id="ed" />')->__toString();

    expect($html)->toContain('for="ed"')
        ->and($html)->toMatch('/role="textbox"[^>]*/');
});

it('declara su bundle por el mecanismo de Livewire, no a pelo', function () {
    // El script va en `@assets`: Livewire lo recoge y lo inyecta en la respuesta
    // completa, así que NO aparece en el HTML de un componente aislado. Es a
    // propósito: un `<script>` que llega dentro de una respuesta de Livewire
    // —un editor dentro de un modal— no lo ejecutaría el navegador.
    //
    // Que llegue de verdad se comprueba en el navegador: `55-form-editor` cuenta
    // las peticiones del bundle, y `33-overlay-formulario` lo abre dentro de un
    // modal.
    $html = $this->blade('<x-kore::editor name="a" />')->__toString();

    expect($html)->not->toContain('<script src=')
        ->and(KoreUi\KoreUiServiceProvider::editorScriptUrl())->toContain('kore-ui-editor.js');
});

it('el bundle del editor no viaja en el principal', function () {
    // Si `@koreScripts` lo trajera, sacarlo aparte no habría servido de nada.
    $principal = file_get_contents(__DIR__ . '/../../dist/kore-ui.js');

    expect($principal)->not->toContain('KoreEditor');
});

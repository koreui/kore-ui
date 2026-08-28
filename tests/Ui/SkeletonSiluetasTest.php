<?php

/**
 * `skeleton` como prop del propio componente.
 *
 * No es lo mismo que `loading`: ese echa un velo por encima de un contenido ya
 * pintado. Esto dibuja la silueta de lo que todavía no ha llegado, con el mismo
 * marco que tendrá después, para que nada salte cuando lleguen los datos.
 */

it('sustituye el contenido por la silueta', function (string $etiqueta, string $contenido) {
    $this->blade($etiqueta)->assertDontSee($contenido, false);
})->with([
    'card'    => ['<x-kore::card title="Ventas" skeleton>Contenido real</x-kore::card>', 'Contenido real'],
    'stats'   => ['<x-kore::stats label="Ingresos" value="1234" skeleton />', '1234'],
    'table'   => ['<x-kore::table :headers="[\'Nombre\']" :rows="[[\'Nombre\' => \'Ada\']]" skeleton />', 'Ada'],
]);

it('anuncia que está cargando', function (string $etiqueta) {
    $this->blade($etiqueta)
        ->assertSee('aria-busy="true"', false)
        ->assertSee('role="status"', false)
        ->assertSee('Cargando', false);
})->with([
    ['<x-kore::card skeleton />'],
    ['<x-kore::stats skeleton />'],
    ['<x-kore::table skeleton />'],
    ['<x-kore::stepper skeleton />'],
    ['<x-kore::skeleton.chart />'],
]);

it('deja el componente intacto cuando no se pide', function () {
    $this->blade('<x-kore::card title="Ventas">Contenido real</x-kore::card>')
        ->assertSee('Contenido real', false)
        ->assertDontSee('aria-busy', false);
});

it('el entero elige cuántas filas o líneas dibuja', function () {
    // 5 filas por defecto; el entero manda.
    $pocas = $this->blade('<x-kore::table :headers="[\'A\', \'B\']" :skeleton="2" />')->__toString();
    $muchas = $this->blade('<x-kore::table :headers="[\'A\', \'B\']" :skeleton="9" />')->__toString();

    expect(substr_count($pocas, '<tr>'))->toBe(3)      // cabecera + 2
        ->and(substr_count($muchas, '<tr>'))->toBe(10); // cabecera + 9
});

it('la silueta de la tabla usa las columnas que ya se conocen', function () {
    // Las cabeceras llegan antes que las filas: no hay que adivinarlas.
    $html = $this->blade('<x-kore::table :headers="[\'A\', \'B\', \'C\']" skeleton />')->__toString();

    // `<th ` con espacio: `<thead` también empieza por «th».
    expect(substr_count($html, '<th '))->toBe(3);
});

it('las siluetas se pueden usar sueltas, sin el componente', function () {
    // Útil para un listado que aún no sabe cuántas tarjetas va a tener.
    $this->blade('<x-kore::skeleton.card :lines="4" footer />')
        ->assertSee('aria-busy="true"', false);
});

it('la silueta del gráfico no cambia entre repintados', function () {
    // Alturas fijas y no aleatorias: con Livewire se repinta más de lo que uno
    // cree, y una silueta que baila en cada morph parpadea.
    $primera = $this->blade('<x-kore::skeleton.chart :bars="5" />')->__toString();
    $segunda = $this->blade('<x-kore::skeleton.chart :bars="5" />')->__toString();

    expect($primera)->toBe($segunda);
});

it('el gráfico dibuja su silueta sin tocar el frame', function () {
    // El componente de clase abre un frame en el constructor y la vista lo cierra.
    // La silueta tiene que cerrarlo igual: si no, el siguiente gráfico de la
    // página hereda las marcas de este.
    $html = $this->blade('<x-kore::chart :data="[]" skeleton><x-kore::chart.bar y="ventas" /></x-kore::chart>');

    $html->assertSee('aria-busy="true"', false)
        ->assertDontSee('<svg', false);

    // Y el de después sale limpio, no con las marcas del anterior.
    $this->blade(<<<'BLADE'
        <x-kore::chart :data="[['mes' => 'Ene', 'v' => 1], ['mes' => 'Feb', 'v' => 2]]" x="mes">
            <x-kore::chart.line y="v" />
        </x-kore::chart>
    BLADE)->assertSee('<svg', false)
        ->assertSee('class="kore-chart-line"', false);
});

it('el entero del gráfico elige cuántas barras', function () {
    $pocas = $this->blade('<x-kore::chart :data="[]" :skeleton="3" />')->__toString();
    $muchas = $this->blade('<x-kore::chart :data="[]" :skeleton="10" />')->__toString();

    expect(substr_count($muchas, 'kore-skeleton-shimmer'))
        ->toBeGreaterThan(substr_count($pocas, 'kore-skeleton-shimmer'));
});

<?php

use KoreUi\Charts\Stream;

describe('el intervalo', function () {
    it('entiende segundos, milisegundos y números', function () {
        expect(Stream::interval('5s'))->toBe(5000);
        expect(Stream::interval('2s'))->toBe(2000);
        expect(Stream::interval('750ms'))->toBe(750);
        expect(Stream::interval(3000))->toBe(3000);
        expect(Stream::interval('1500'))->toBe(1500);
    });

    it('lanza si no es un intervalo', function () {
        $mensaje = null;

        try {
            Stream::interval('cada rato');
        } catch (InvalidArgumentException $e) {
            $mensaje = $e->getMessage();
        }

        expect($mensaje)->toContain('no es un intervalo');
    });

    /**
     * El suelo no es prudencia: es aritmética.
     *
     * Un refresco es un round-trip COMPLETO de Livewire —consulta, Blade, morph— y cuesta entre 30
     * y 80 ms de servidor más la red. Por debajo de medio segundo los refrescos se solapan, Livewire
     * los encola, y el gráfico va cada vez más por detrás mientras el servidor arde.
     */
    it('lanza por debajo de medio segundo, y explica por qué', function () {
        $mensaje = null;

        try {
            Stream::interval('100ms');
        } catch (InvalidArgumentException $e) {
            $mensaje = $e->getMessage();
        }

        expect($mensaje)->toContain('round-trip completo de Livewire');
        expect($mensaje)->toContain('El techo honesto es 1 Hz');
        expect($mensaje)->toContain('el formato de cable');
    });
});

describe('la marca <x-kore::chart.stream>', function () {
    $data = "[['m' => 'Ene', 'v' => 1], ['m' => 'Feb', 'v' => 2]]";

    it('enciende las transiciones y monta el temporizador', function () use ($data) {
        $html = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="m">
                <x-kore::chart.bar y="v" />
                <x-kore::chart.stream every="2s" call="tick" />
            </x-kore::chart>
        BLADE)->__toString();

        // Js::from() escapa las comillas como \u0022.
        expect($html)->toContain('data-kore-chart-stream="true"');
        expect($html)->toContain('\u0022every\u0022:2000');
        expect($html)->toContain('\u0022call\u0022:\u0022tick\u0022');
    });

    it('se pueden apagar las transiciones sin apagar el refresco', function () use ($data) {
        $html = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="m">
                <x-kore::chart.bar y="v" />
                <x-kore::chart.stream every="5s" :transition="false" />
            </x-kore::chart>
        BLADE)->__toString();

        expect($html)->not->toContain('data-kore-chart-stream');
        expect($html)->toContain('\u0022every\u0022:5000');
    });

    it('vive aunque el gráfico esté vacío', function () {
        // Es justamente lo que hace que un panel que arranca sin datos se rellene solo cuando
        // llegan. Si el stream se apagara con el estado vacío, no llegarían nunca.
        $html = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[]" x="m">
                <x-kore::chart.line y="v" />
                <x-kore::chart.stream every="2s" call="tick" />
            </x-kore::chart>
        BLADE)->__toString();

        expect($html)->toContain('data-kore-chart-empty="true"');
        expect($html)->toContain('KoreChart(');
        expect($html)->toContain('\u0022every\u0022:2000');
    });
});

describe('el eje Y fijo', function () {
    it('deja de reescalarse con cada dato nuevo', function () {
        // En un gráfico EN VIVO esto deja de ser un lujo: un eje que se reescala cada dos segundos
        // porque el dato subió un punto es ilegible — la línea se queda quieta y lo que se mueve es
        // el eje. `Domain::fromSeries()` aceptaba min/max desde el primer día, pero Plot no se los
        // pasaba nunca.
        $html = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'Ene', 'v' => 43], ['m' => 'Feb', 'v' => 51]]" x="m">
                <x-kore::chart.line y="v" />
                <x-kore::chart.axis-y :min="0" :max="100" :ticks="5" />
            </x-kore::chart>
        BLADE)->__toString();

        // El eje llega a 100 aunque el dato máximo sea 51.
        expect($html)->toContain('>100<');
        expect($html)->toContain('>0<');
    });

    it('sin min ni max, sigue deduciéndolo del dato', function () {
        $html = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'Ene', 'v' => 43], ['m' => 'Feb', 'v' => 51]]" x="m">
                <x-kore::chart.line y="v" />
                <x-kore::chart.axis-y :ticks="5" />
            </x-kore::chart>
        BLADE)->__toString();

        expect($html)->not->toContain('>100<');
    });
});

/**
 * La regla que se aprendió mirando: **si hay trazo, no se anima nada**.
 *
 * El <path> no se puede animar —medido en los tres motores: WebKit ni siquiera soporta
 * `transition: d`— así que salta de golpe. Y todo lo que se mueva despacio mientras el trazo salta
 * SE DESPEGA DE ÉL.
 *
 * Medido en el navegador, con los puntos animados: el peor se iba a **8,36 % del área** de la curva
 * sobre la que se supone que está — unos 24 px en un gráfico de 18rem. Con la regla puesta: 0,31 %.
 */
describe('o se anima todo, o no se anima nada', function () {
    it('con línea, las transiciones se apagan', function () {
        $html = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'Ene', 'v' => 1], ['m' => 'Feb', 'v' => 2]]" x="m">
                <x-kore::chart.line y="v" dots />
                <x-kore::chart.stream every="2s" call="tick" />
            </x-kore::chart>
        BLADE)->__toString();

        expect($html)->not->toContain('data-kore-chart-stream');

        // Pero el refresco sigue vivo: lo que se apaga es el viaje, no el dato.
        expect($html)->toContain('\u0022every\u0022:2000');
    });

    it('con área, también', function () {
        $html = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'Ene', 'v' => 1], ['m' => 'Feb', 'v' => 2]]" x="m">
                <x-kore::chart.area y="v" />
                <x-kore::chart.stream every="2s" />
            </x-kore::chart>
        BLADE)->__toString();

        expect($html)->not->toContain('data-kore-chart-stream');
    });

    it('sin trazo, las barras SÍ viajan — no hay nada de lo que despegarse', function () {
        $html = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'Ene', 'v' => 1], ['m' => 'Feb', 'v' => 2]]" x="m">
                <x-kore::chart.bar y="v" />
                <x-kore::chart.stream every="2s" call="tick" />
            </x-kore::chart>
        BLADE)->__toString();

        expect($html)->toContain('data-kore-chart-stream="true"');
    });

    it('una barra con línea encima tampoco se anima: la línea manda', function () {
        $html = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'Ene', 'v' => 1, 'w' => 3], ['m' => 'Feb', 'v' => 2, 'w' => 4]]" x="m">
                <x-kore::chart.bar y="v" />
                <x-kore::chart.line y="w" />
                <x-kore::chart.stream every="2s" />
            </x-kore::chart>
        BLADE)->__toString();

        expect($html)->not->toContain('data-kore-chart-stream');
    });

    it('las barras llevan wire:key cuando hay stream, y sigue al DATO', function () {
        // Sin ella, en una ventana deslizante el morph reutiliza la barra i para el dato i+1: la
        // barra CRECE en el sitio en vez de que la de al lado se desplace. Con la transición
        // puesta, eso se ve temblar.
        $html = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'Ene', 'v' => 1], ['m' => 'Feb', 'v' => 2]]" x="m">
                <x-kore::chart.bar y="v" />
                <x-kore::chart.stream every="2s" />
            </x-kore::chart>
        BLADE)->__toString();

        expect($html)->toContain('wire:key="kore-chart-1-kore-chart-1-s1-Ene"');
        expect($html)->toContain('wire:key="kore-chart-1-kore-chart-1-s1-Feb"');
    });

    it('sin stream, ni una clave: son bytes que nadie necesita', function () {
        $html = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'Ene', 'v' => 1]]" x="m">
                <x-kore::chart.bar y="v" />
            </x-kore::chart>
        BLADE)->__toString();

        expect($html)->not->toContain('wire:key');
    });
});

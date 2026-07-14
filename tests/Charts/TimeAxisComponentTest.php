<?php

/**
 * El eje temporal, de punta a punta: desde el Blade que escribe el usuario hasta el HTML.
 *
 * Los tests de `TimeScaleTest` prueban el motor. Éstos prueban que el motor está ENCHUFADO —que
 * es una cosa distinta, y la que se rompe en silencio.
 */

// Un sensor que estuvo caído del 3 al 5 de febrero. En un eje de categorías, esos tres días
// desaparecen y la línea los cierra como si no hubiera pasado nada.
$lecturas = <<<'PHP'
[
    ['medido_en' => new DateTimeImmutable('2026-02-01 00:00', new DateTimeZone('Europe/Madrid')), 'temp' => 21.4],
    ['medido_en' => new DateTimeImmutable('2026-02-02 00:00', new DateTimeZone('Europe/Madrid')), 'temp' => 22.1],
    ['medido_en' => new DateTimeImmutable('2026-02-06 00:00', new DateTimeZone('Europe/Madrid')), 'temp' => 19.8],
]
PHP;

it('detecta las fechas y monta un eje temporal sin que se lo pidas', function () use ($lecturas) {
    $view = $this->blade(<<<BLADE
        <x-kore::chart :data="{$lecturas}" x="medido_en">
            <x-kore::chart.line y="temp" />
            <x-kore::chart.axis-x />
        </x-kore::chart>
    BLADE);

    // Los ticks caen en fronteras del calendario, así que llevan su línea de contexto.
    $view->assertSee('kore-chart-tick-context', false);
});

it('el hueco de tres días se VE', function () use ($lecturas) {
    $html = $this->blade(<<<BLADE
        <x-kore::chart :data="{$lecturas}" x="medido_en">
            <x-kore::chart.line y="temp" />
        </x-kore::chart>
    BLADE)->__toString();

    preg_match('/class="kore-chart-line" d="([^"]+)"/', $html, $m);

    expect($m)->not->toBeEmpty();

    // Cinco días de rango. El 1 de febrero en el 0 %, el 2 en el 20 %, el 6 en el 100 %.
    // Si se colocaran por ordinal (que es lo que hacía hasta 1.6.0), el segundo punto estaría
    // en el 50 % y el hueco no existiría.
    expect($m[1])->toContain('M0 ')->toContain('L20 ')->toContain('L100 ');
});

it('el tooltip habla de una fecha entera, no de un número suelto', function () use ($lecturas) {
    app()->setLocale('es');

    $html = $this->blade(<<<BLADE
        <x-kore::chart :data="{$lecturas}" x="medido_en">
            <x-kore::chart.line y="temp" />
            <x-kore::chart.tooltip />
        </x-kore::chart>
    BLADE)->__toString();

    // El eje dice «1»; el tooltip dice «1 feb. 2026». Un tooltip habla de un punto concreto y
    // fuera de todo contexto: «1» no le dice nada a nadie.
    expect($html)->toContain('1 feb. 2026');
});

it('lee la fecha en la zona que le digas, no en la de la base de datos', function () {
    app()->setLocale('es');

    // Un pedido de las 23:30 en Madrid es de las 22:30 en UTC, o sea de OTRO DÍA. Un gráfico
    // diario que lea las fechas en UTC lo pone en la barra de ayer. No es cosmético.
    $data = <<<'PHP'
    [
        ['t' => new DateTimeImmutable('2026-02-14 23:30', new DateTimeZone('UTC')), 'v' => 1],
        ['t' => new DateTimeImmutable('2026-02-15 23:30', new DateTimeZone('UTC')), 'v' => 2],
    ]
    PHP;

    $html = $this->blade(<<<BLADE
        <x-kore::chart :data="{$data}" x="t">
            <x-kore::chart.line y="v" />
            <x-kore::chart.tooltip />
            <x-kore::chart.axis-x timezone="Asia/Tokyo" />
        </x-kore::chart>
    BLADE)->__toString();

    // 2026-02-14 23:30 UTC son las 08:30 del 15 en Tokio.
    expect($html)->toContain('15 feb. 2026, 08:30');
});

it('avisa si le das la fecha ya formateada', function () {
    // Es EL error que hay que hacer imposible: con el texto, el gráfico sólo puede colocar los
    // puntos por su orden, y los huecos del calendario desaparecen sin que nadie se entere.
    $data = "[['dia' => '1 feb', 'v' => 1], ['dia' => '6 feb', 'v' => 2]]";

    $mensaje = null;

    try {
        $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="dia">
                <x-kore::chart.line y="v" />
                <x-kore::chart.axis-x scale="time" />
            </x-kore::chart>
        BLADE)->__toString();
    } catch (Throwable $e) {
        $mensaje = $e->getMessage();
    }

    expect($mensaje)->toContain('no trae fechas');
});

it('sigue tratando las categorías como categorías', function () {
    // El eje de siempre no se toca. Éste es el test que impide que el eje temporal se lleve por
    // delante el gráfico de todo el que ya tenía uno.
    $data = "[['mes' => 'Ene', 'v' => 1], ['mes' => 'Feb', 'v' => 2], ['mes' => 'Mar', 'v' => 3]]";

    $html = $this->blade(<<<BLADE
        <x-kore::chart :data="{$data}" x="mes">
            <x-kore::chart.line y="v" />
            <x-kore::chart.axis-x />
        </x-kore::chart>
    BLADE)->__toString();

    expect($html)->toContain('Ene')->toContain('Feb')->toContain('Mar');
    expect($html)->not->toContain('kore-chart-tick-context');
});

it('una barra en un eje de fechas no pisa a la siguiente', function () use ($lecturas) {
    $html = $this->blade(<<<BLADE
        <x-kore::chart :data="{$lecturas}" x="medido_en">
            <x-kore::chart.bar y="temp" />
        </x-kore::chart>
    BLADE)->__toString();

    preg_match_all('/--kx: ([\d.-]+); --kw: ([\d.-]+)/', $html, $m, PREG_SET_ORDER);

    expect($m)->toHaveCount(3);

    // El ancho sale del hueco MÍNIMO entre dos fechas (un día = 20 %), menos el padding.
    foreach ($m as $bar) {
        expect((float) $bar[2])->toBe(16.0);
    }

    // Y ninguna se solapa con la siguiente.
    for ($i = 1; $i < count($m); $i++) {
        expect((float) $m[$i][1])->toBeGreaterThanOrEqual((float) $m[$i - 1][1] + (float) $m[$i - 1][2]);
    }
});

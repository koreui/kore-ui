<?php

use KoreUi\Charts\ChartContext;
use KoreUi\Charts\Exceptions\OrphanMarkException;

$data = <<<'PHP'
[
    ['mes' => 'Ene', 'ingresos' => 1240, 'gastos' => 800],
    ['mes' => 'Feb', 'ingresos' => 3180, 'gastos' => 1500],
    ['mes' => 'Mar', 'ingresos' => 2470, 'gastos' => 1100],
]
PHP;

it('renderiza el SVG desde el servidor', function () use ($data) {
    $view = $this->blade(<<<BLADE
        <x-kore::chart :data="{$data}" x="mes">
            <x-kore::chart.line y="ingresos" />
        </x-kore::chart>
    BLADE);

    $view->assertSee('data-kore-chart="kore-chart-1"', false)
        ->assertSee('<svg', false)
        ->assertSee('class="kore-chart-line"', false)
        ->assertSee('preserveAspectRatio="none"', false)
        // El trazo sale ya calculado del servidor: no hay ningún JS que lo dibuje.
        ->assertSee('d="M', false);
});

it('el color viaja como token, nunca como valor', function () use ($data) {
    // Ésta es la razón de que el gráfico se repinte solo al cambiar de tema, sin ejecutar JS.
    $view = $this->blade(<<<BLADE
        <x-kore::chart :data="{$data}" x="mes">
            <x-kore::chart.line y="ingresos" />
            <x-kore::chart.line y="gastos" />
        </x-kore::chart>
    BLADE);

    $view->assertSee('--kore-series: var(--kore-chart-1)', false)
        ->assertSee('--kore-series: var(--kore-chart-2)', false);
});

describe('la regla del SVG', function () use ($data) {
    it('dentro del SVG no hay más que <path>', function () use ($data) {
        // Con preserveAspectRatio="none" un <text> sale deformado, un <circle> elíptico y el
        // rx de un <rect> ovalado. Este test convierte la regla en invariante: si alguien mete
        // un <text> "porque es más fácil", salta aquí y no en producción.
        $html = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes">
                <x-kore::chart.line y="ingresos" dots />
                <x-kore::chart.bar y="gastos" />
            </x-kore::chart>
        BLADE)->__toString();

        preg_match_all('/<svg.*?<\/svg>/s', $html, $matches);

        expect($matches[0])->not->toBeEmpty();

        foreach ($matches[0] as $svg) {
            expect($svg)->not->toContain('<text');
            expect($svg)->not->toContain('<circle');
            expect($svg)->not->toContain('<rect');
            expect($svg)->not->toContain('stroke-dasharray');
        }
    });

    it('las barras, los puntos y la rejilla son HTML', function () use ($data) {
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes">
                <x-kore::chart.bar y="gastos" />
                <x-kore::chart.line y="ingresos" dots />
            </x-kore::chart>
        BLADE);

        $view->assertSee('class="kore-chart-bar"', false)
            ->assertSee('class="kore-chart-dot"', false)
            ->assertSee('class="kore-chart-grid-line"', false);
    });

    it('emite la geometría como custom properties, no como valores literales', function () use ($data) {
        // Con `left: 42.31%` hardcodeado, reescalar al ocultar una serie sería imposible sin
        // duplicar el motor en JS. Con --kx/--ky + calc(), es un scaleY: una transformación afín.
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes">
                <x-kore::chart.bar y="gastos" />
            </x-kore::chart>
        BLADE);

        $view->assertSee('--kx:', false)->assertSee('--kh:', false);
        $view->assertDontSee('left: 42', false);
    });
});

describe('el orden de las marcas es el orden de pintado', function () use ($data) {
    it('intercala las capas para respetarlo', function () use ($data) {
        // Barra (HTML) y luego línea (SVG): la línea tiene que quedar ENCIMA. Como viven en
        // medios distintos, se emite una capa por cada tramo contiguo del mismo medio.
        $html = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes">
                <x-kore::chart.bar y="gastos" />
                <x-kore::chart.line y="ingresos" />
            </x-kore::chart>
        BLADE)->__toString();

        $bar = strpos($html, 'kore-chart-bar');
        $line = strpos($html, 'kore-chart-line');

        expect($bar)->toBeLessThan($line);   // la barra antes en el DOM ⇒ la línea encima
    });
});

describe('la marca huérfana', function () {
    it('lanza en vez de dejar una serie fantasma', function () {
        // Con un registro plano, esta marca se la comería el siguiente gráfico de la página:
        // aparecería una serie que nadie pidió, con un color robado de la paleta, y sin ningún
        // error. Es el bug que Filament lleva años sin poder reproducir.
        //
        // Blade envuelve lo que se lance dentro de una vista en una ViewException, así que lo
        // que se comprueba es lo que de verdad ve el desarrollador: el mensaje.
        try {
            $this->blade('<x-kore::chart.line y="x" />');
            $this->fail('Una marca fuera de un gráfico debería lanzar, no renderizar nada.');
        } catch (Throwable $e) {
            expect($e->getMessage())->toContain('tiene que ir DENTRO de un <x-kore::chart>');

            // Blade anida varias ViewException, así que se recorre la cadena hasta la causa real.
            $root = $e;
            while ($root->getPrevious() !== null) {
                $root = $root->getPrevious();
            }

            expect($root)->toBeInstanceOf(OrphanMarkException::class);
        }
    });

    it('no deja la pila sucia para el siguiente gráfico', function () {
        try {
            $this->blade('<x-kore::chart.bar y="x" />');
        } catch (Throwable) {
            // ignorada a propósito
        }

        expect(app(ChartContext::class)->depth())->toBe(0);
    });
});

describe('varios gráficos en la misma página', function () use ($data) {
    it('no se mezclan y cada uno tiene su id', function () use ($data) {
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes">
                <x-kore::chart.line y="ingresos" />
            </x-kore::chart>
            <x-kore::chart :data="{$data}" x="mes">
                <x-kore::chart.bar y="gastos" />
            </x-kore::chart>
        BLADE);

        $view->assertSee('data-kore-chart="kore-chart-1"', false)
            ->assertSee('data-kore-chart="kore-chart-2"', false);

        // El segundo gráfico tiene UNA serie, no dos: no ha heredado la del primero.
        expect(app(ChartContext::class)->depth())->toBe(0);
    });

    it('deja la pila vacía aunque haya varios', function () use ($data) {
        $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes"><x-kore::chart.line y="ingresos" /></x-kore::chart>
            <x-kore::chart :data="{$data}" x="mes"><x-kore::chart.line y="gastos" /></x-kore::chart>
        BLADE);

        expect(app(ChartContext::class)->depth())->toBe(0);
    });
});

describe('accesibilidad', function () use ($data) {
    it('sirve los datos en una tabla, que es lo único que un lector de pantalla puede leer', function () use ($data) {
        // Un <svg> es tan mudo como un <canvas>. Nadie en el ecosistema hace esto: PrimeVue lo
        // insinúa en su doc y no lo implementa; en Filament es un issue de prioridad alta.
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes" title="Ventas">
                <x-kore::chart.line y="ingresos" label="Ingresos" />
            </x-kore::chart>
        BLADE);

        $view->assertSee('<table class="sr-only">', false)
            ->assertSee('<caption>Ventas</caption>', false)
            ->assertSee('<th scope="col">Ingresos</th>', false)
            ->assertSee('<th scope="row">Ene</th>', false)
            ->assertSee('<td>1.240</td>', false);
    });

    it('el SVG está oculto para el lector: los datos están en la tabla', function () use ($data) {
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes">
                <x-kore::chart.line y="ingresos" />
            </x-kore::chart>
        BLADE);

        $view->assertSee('aria-hidden="true"', false);
    });
});

describe('estado vacío', function () {
    it('enseña el estado vacío en vez de dividir por cero', function () {
        $view = $this->blade('<x-kore::chart :data="[]"><x-kore::chart.line y="x" /></x-kore::chart>');

        $view->assertSee('data-kore-chart-empty="true"', false);
    });

    it('trata una serie toda nula como vacía', function () {
        $view = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'v' => null], ['m' => 'B', 'v' => null]]" x="m">
                <x-kore::chart.line y="v" />
            </x-kore::chart>
        BLADE);

        $view->assertSee('data-kore-chart-empty="true"', false);
    });
});

describe('el payload del tooltip', function () use ($data) {
    it('no se emite si no hay tooltip: es una segunda copia del dato', function () use ($data) {
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes">
                <x-kore::chart.line y="ingresos" />
            </x-kore::chart>
        BLADE);

        $view->assertDontSee('data-kore-chart-payload', false);
    });

    it('viaja con las etiquetas YA formateadas, para no tener que portar Format a JS', function () use ($data) {
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes">
                <x-kore::chart.line y="ingresos" />
                <x-kore::chart.tooltip />
            </x-kore::chart>
        BLADE);

        $view->assertSee('data-kore-chart-payload', false)
            ->assertSee('1.240', false);   // formateado en PHP, no un 1240 crudo
    });
});

describe('seguridad', function () use ($data) {
    it('no deja colar CSS por la altura', function () use ($data) {
        // La altura acaba en un atributo style: sin validar, sería una vía de inyección.
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes" height="16rem; background: url(evil)">
                <x-kore::chart.line y="ingresos" />
            </x-kore::chart>
        BLADE);

        $view->assertDontSee('url(evil)', false);
    });

    it('no deja colar CSS por el aspect ratio', function () use ($data) {
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes" aspect="16/9; background: url(evil)">
                <x-kore::chart.line y="ingresos" />
            </x-kore::chart>
        BLADE);

        $view->assertDontSee('url(evil)', false);
    });
});

describe('la canaleta del eje Y', function () use ($data) {
    it('se dimensiona contando caracteres, porque el servidor no puede medir texto', function () use ($data) {
        // La columna del grid NO puede ser `auto`: las etiquetas van en position:absolute
        // (cada una a la altura de su tick), así que no aportan anchura y la columna
        // colapsaría — las etiquetas se saldrían por la izquierda, fuera de la tarjeta.
        // Es un bug que solo se ve mirando, y por eso tiene test.
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes">
                <x-kore::chart.line y="ingresos" />
                <x-kore::chart.axis-y format="currency" />
            </x-kore::chart>
        BLADE);

        // "3.000 €" son 7 caracteres → 7.5ch
        $view->assertSee('--kore-chart-gutter-y: 7.5ch', false);
    });

    it('se ensancha con etiquetas más largas', function () {
        $view = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'v' => 1500000], ['m' => 'B', 'v' => 2500000]]" x="m">
                <x-kore::chart.line y="v" />
                <x-kore::chart.axis-y format="currency" />
            </x-kore::chart>
        BLADE);

        // "2.500.000 €" son 11 caracteres
        $view->assertSee('--kore-chart-gutter-y: 11.5ch', false);
    });
});

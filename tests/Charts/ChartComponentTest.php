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

        // El sr-only va en el <div>, no en la <table>: sobre una tabla, el `width: 1px` se ignora
        // (el layout de tablas lo acota al min-content) y la caja arrastraba scroll horizontal.
        $view->assertSee('<div class="sr-only">', false)
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

        // "3.000 €" NO son 7ch: son 4 cifras (1ch cada una, por tabular-nums) + un punto
        // (0,5) + un espacio (0,5) + el símbolo (1,3) = 6,3ch. Contando 1ch por carácter, la
        // canaleta pedía 31px de más y empujaba el gráfico entero a la derecha.
        $view->assertSee('--kore-chart-gutter-y: 6.3ch', false);
    });

    it('se ensancha con etiquetas más largas', function () {
        $view = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'v' => 1500000], ['m' => 'B', 'v' => 2500000]]" x="m">
                <x-kore::chart.line y="v" />
                <x-kore::chart.axis-y format="currency" />
            </x-kore::chart>
        BLADE);

        // "2.500.000 €": 7 cifras + 2 puntos + espacio + símbolo = 7 + 1 + 0,5 + 1,3 = 9,8ch
        $view->assertSee('--kore-chart-gutter-y: 9.8ch', false);
    });
});

describe('los decimales del dato', function () {
    it('no se pierden en el tooltip ni en la tabla', function () {
        // Un sensor que marca 21,4 °C no puede aparecer como "21". El eje deduce sus decimales
        // del paso entre ticks; la serie no tiene paso, así que los deduce de sus propios
        // valores. Es un bug que sólo se ve mirando el gráfico con datos reales.
        $view = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'Ene', 'v' => 21.4], ['m' => 'Feb', 'v' => 23.1]]" x="m">
                <x-kore::chart.line y="v" label="Sensor" />
                <x-kore::chart.tooltip />
            </x-kore::chart>
        BLADE);

        $view->assertSee('21,4', false)
            ->assertSee('23,1', false)
            ->assertDontSee('<td>21</td>', false);
    });
});

describe('el donut', function () {
    it('enlaza cada arco con su fila de la leyenda por el mismo atributo', function () {
        // De ese atributo compartido cuelga TODA la interacción del donut, y es CSS puro
        // (`:has()` en kore-theme.css): ni una línea de JavaScript. Si alguien renombra el
        // atributo en uno de los dos sitios, el enlace se rompe sin ningún error.
        $view = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'v' => 10], ['m' => 'B', 'v' => 30]]" x="m">
                <x-kore::chart.donut y="v" />
            </x-kore::chart>
        BLADE);

        $view->assertSee('<path class="kore-chart-slice"', false)
            ->assertSee('data-slice="0"', false)
            ->assertSee('data-slice="1"', false)
            // el arco y la fila comparten el índice
            ->assertSee('<li class="kore-chart-legend-item" data-slice="0">', false);
    });

    it('no lleva tooltip: sus valores ya están en la leyenda', function () {
        // No es un olvido. La leyenda imprime etiqueta, valor y porcentaje de cada porción de
        // forma permanente; un tooltip repetiría lo que ya está en pantalla y metería en el
        // DOM una segunda copia de los datos a cambio de nada.
        $view = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'v' => 10], ['m' => 'B', 'v' => 30]]" x="m">
                <x-kore::chart.donut y="v" />
            </x-kore::chart>
        BLADE);

        $view->assertDontSee('data-kore-chart-payload', false)
            ->assertSee('kore-chart-legend-value', false)
            ->assertSee('kore-chart-legend-percent', false);
    });
});

describe('el donut no comparte gráfico con nada', function () {
    // Blade envuelve en ViewException lo que se lance dentro de una vista, así que se
    // comprueba lo que de verdad ve el desarrollador: el mensaje.
    // Se invoca con ->call($this, …): una closure declarada aquí no está ligada al test.
    $lanza = function (string $blade, string $mensaje) {
        try {
            $this->blade($blade);
            $this->fail('Debería haber lanzado en vez de descartar la marca en silencio.');
        } catch (Throwable $e) {
            expect($e->getMessage())->toContain($mensaje);
        }
    };

    it('lanza si le metes un tooltip, en vez de descartarlo en silencio', function () use ($lanza) {
        // Antes: el tooltip no se pintaba, no avisaba, y encima el gráfico montaba un
        // componente de Alpine que no hacía nada. Escribías una marca y el gráfico decidía
        // por su cuenta que no valía. Es la misma regla que al mezclar escalas: no se adivina.
        $lanza->call($this, <<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'v' => 10]]" x="m">
                <x-kore::chart.donut y="v" />
                <x-kore::chart.tooltip />
            </x-kore::chart>
        BLADE, 'el donut no lleva <x-kore::chart.tooltip>');
    });

    it('lanza si le metes una marca cartesiana', function () use ($lanza) {
        $lanza->call($this, <<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'v' => 10]]" x="m">
                <x-kore::chart.donut y="v" />
                <x-kore::chart.line y="v" />
            </x-kore::chart>
        BLADE, 'no comparte gráfico con otras marcas');
    });

    it('lanza si le metes ejes: un donut no tiene ejes', function () use ($lanza) {
        $lanza->call($this, <<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'v' => 10]]" x="m">
                <x-kore::chart.donut y="v" />
                <x-kore::chart.axis-y />
            </x-kore::chart>
        BLADE, 'un donut no tiene ejes');
    });

    it('no monta Alpine: su interacción es CSS puro', function () {
        $view = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'v' => 10], ['m' => 'B', 'v' => 30]]" x="m">
                <x-kore::chart.donut y="v" />
            </x-kore::chart>
        BLADE);

        $view->assertDontSee('KoreChart(', false)
            ->assertSee('data-highlight', false);
    });

    it('el resaltado se apaga por gráfico', function () {
        // Todas las reglas de :has() cuelgan de data-highlight, así que quitarlo las desactiva
        // enteras. No hay nada que "desmontar": no había JavaScript que montar.
        $view = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'v' => 10]]" x="m">
                <x-kore::chart.donut y="v" :highlight="false" />
            </x-kore::chart>
        BLADE);

        $view->assertDontSee('data-highlight', false)
            ->assertSee('kore-chart-slice', false);
    });
});

describe('todo se puede apagar', function () use ($data) {
    it('una serie oculta CONSERVA su color: las de detrás no se recolocan', function () {
        // Ésta es toda la razón de que exista :show en vez de envolver la marca en un @if.
        // Con @if, la marca desaparece del árbol, la siguiente hereda su slot y TODAS las
        // series de detrás cambian de color. El lector creería estar mirando otra cosa.
        $conTodas = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'a' => 1, 'b' => 2, 'c' => 3]]" x="m">
                <x-kore::chart.line y="a" />
                <x-kore::chart.line y="b" />
                <x-kore::chart.line y="c" />
            </x-kore::chart>
        BLADE)->__toString();

        $sinLaDelMedio = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'a' => 1, 'b' => 2, 'c' => 3]]" x="m">
                <x-kore::chart.line y="a" />
                <x-kore::chart.line y="b" :show="false" />
                <x-kore::chart.line y="c" />
            </x-kore::chart>
        BLADE)->__toString();

        // La tercera serie sigue siendo la 3 de la paleta, no la 2.
        expect($conTodas)->toContain('--kore-series: var(--kore-chart-3)');
        expect($sinLaDelMedio)->toContain('--kore-series: var(--kore-chart-3)');
        expect($sinLaDelMedio)->not->toContain('--kore-series: var(--kore-chart-2)');
    });

    it('la serie oculta desaparece del tooltip y de la tabla accesible', function () {
        // No basta con no pintar el trazo: si siguiera en el payload, el tooltip enseñaría
        // una serie que no está en pantalla.
        $view = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'v' => 10, 'w' => 20]]" x="m">
                <x-kore::chart.line y="v" label="Visible" />
                <x-kore::chart.line y="w" label="Oculta" :show="false" />
                <x-kore::chart.tooltip />
            </x-kore::chart>
        BLADE);

        $view->assertSee('Visible', false)->assertDontSee('Oculta', false);
    });

    it('si se ocultan todas, sale el estado vacío', function () {
        $view = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'A', 'v' => 10]]" x="m">
                <x-kore::chart.line y="v" :show="false" />
            </x-kore::chart>
        BLADE);

        $view->assertSee('data-kore-chart-empty="true"', false);
    });

    it('los ejes salen por defecto y la marca los apaga', function () use ($data) {
        $conEjes = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes"><x-kore::chart.line y="ingresos" /></x-kore::chart>
        BLADE)->__toString();

        $sinEjes = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes">
                <x-kore::chart.line y="ingresos" />
                <x-kore::chart.axis-y :show="false" />
                <x-kore::chart.axis-x :show="false" />
            </x-kore::chart>
        BLADE)->__toString();

        expect($conEjes)->toContain('class="kore-chart-gutter-y"');
        expect($sinEjes)->not->toContain('class="kore-chart-gutter-y"');
        expect($sinEjes)->not->toContain('class="kore-chart-gutter-x"');

        // Y la canaleta mide 0: si no, la columna del grid seguiría reservando su ancho y el
        // gráfico dejaría una franja vacía a la izquierda.
        expect($sinEjes)->toContain('--kore-chart-gutter-y: 0ch');
    });

    it('la rejilla se apaga desde el gráfico', function () use ($data) {
        // ChartFrame::$grid llevaba desde el principio en `true` y NADIE le escribía nunca:
        // un interruptor que no encendía nada.
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes" :grid="false">
                <x-kore::chart.line y="ingresos" />
            </x-kore::chart>
        BLADE);

        $view->assertDontSee('kore-chart-grid-line', false);
    });

    it('el crosshair del tooltip se apaga', function () use ($data) {
        // La prop `crosshair` existía desde el principio... y no la leía nadie.
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes">
                <x-kore::chart.line y="ingresos" />
                <x-kore::chart.tooltip :crosshair="false" />
            </x-kore::chart>
        BLADE);

        $view->assertDontSee('kore-chart-crosshair', false)
            ->assertSee('kore-chart-tooltip', false);   // el tooltip sigue
    });

    it('apagar el tooltip no esconde el payload: no lo emite', function () use ($data) {
        // Aquí :show="false" no es cosmético. El payload es una SEGUNDA copia del dato en el
        // DOM; a 2.000 puntos pesa más que el propio trazo. El gráfico adelgaza de verdad.
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes">
                <x-kore::chart.line y="ingresos" />
                <x-kore::chart.tooltip :show="false" />
                <x-kore::chart.legend :show="false" />
            </x-kore::chart>
        BLADE);

        $view->assertDontSee('data-kore-chart-payload', false)
            ->assertDontSee('kore-chart-legend', false)
            ->assertDontSee('KoreChart(', false);   // sin nada que interactuar, ni Alpine
    });
});

describe('la punta de una barra apilada', function () {
    it('sólo redondea el último tramo, no cada uno', function () {
        // Si cada tramo lleva su propio redondeo, la pila se ve como una torre de piezas
        // sueltas en vez de como UNA barra partida en tramos.
        $html = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'Ene', 'a' => 10, 'b' => 20, 'c' => 30]]" x="m">
                <x-kore::chart.bar y="a" stack="s" />
                <x-kore::chart.bar y="b" stack="s" />
                <x-kore::chart.bar y="c" stack="s" />
            </x-kore::chart>
        BLADE)->__toString();

        expect(substr_count($html, 'class="kore-chart-bar"'))->toBe(3);
        expect(substr_count($html, 'data-cap="true"'))->toBe(1);
    });

    it('si al mes le falta la serie de arriba, la punta es la de debajo', function () {
        // Éste es el caso que hay que probar: la punta es el último tramo CON VALOR, no el
        // último que declaraste. Con un null en «c», la punta de Feb es «b».
        $html = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[
                ['m' => 'Ene', 'a' => 10, 'b' => 20, 'c' => 30],
                ['m' => 'Feb', 'a' => 10, 'b' => 20, 'c' => null],
            ]" x="m">
                <x-kore::chart.bar y="a" stack="s" label="A" />
                <x-kore::chart.bar y="b" stack="s" label="B" />
                <x-kore::chart.bar y="c" stack="s" label="C" />
            </x-kore::chart>
        BLADE)->__toString();

        // 5 barras: Ene tiene 3 tramos, Feb sólo 2 (el null no dibuja nada).
        expect(substr_count($html, 'class="kore-chart-bar"'))->toBe(5);

        // Una punta por columna: la de Ene y la de Feb.
        expect(substr_count($html, 'data-cap="true"'))->toBe(2);

        // Y en Feb la punta es la serie B (la 2 de la paleta), no la C.
        preg_match_all('/<div class="kore-chart-bar"[^>]*data-index="1"[^>]*>/', $html, $feb);
        $puntaDeFeb = array_values(array_filter($feb[0], fn ($bar) => str_contains($bar, 'data-cap="true"')));

        expect($puntaDeFeb)->toHaveCount(1);
        expect($puntaDeFeb[0])->toContain('var(--kore-chart-2)');   // B, no C
    });

    it('un cero no cuenta como punta: no se ve, y dejaría cuadrado el tramo que sí se ve', function () {
        $html = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'Ene', 'a' => 10, 'b' => 0]]" x="m">
                <x-kore::chart.bar y="a" stack="s" />
                <x-kore::chart.bar y="b" stack="s" />
            </x-kore::chart>
        BLADE)->__toString();

        // El 0 SÍ dibuja un tramo (un cero es un dato, a diferencia de un null), pero mide un
        // píxel. La punta es «a».
        preg_match_all('/<div class="kore-chart-bar"[^>]*>/', $html, $bars);
        $punta = array_values(array_filter($bars[0], fn ($bar) => str_contains($bar, 'data-cap="true"')));

        expect($punta)->toHaveCount(1);
        expect($punta[0])->toContain('var(--kore-chart-1)');
    });

    it('una barra suelta siempre es su propia punta, y la negativa la redondea abajo', function () {
        $html = $this->blade(<<<'BLADE'
            <x-kore::chart :data="[['m' => 'Ene', 'v' => 10], ['m' => 'Feb', 'v' => -5]]" x="m">
                <x-kore::chart.bar y="v" />
            </x-kore::chart>
        BLADE)->__toString();

        expect(substr_count($html, 'data-cap="true"'))->toBe(2);
        expect($html)->toContain('data-negative="true"');
    });
});

describe('las etiquetas del eje X no se salen de la tarjeta', function () use ($data) {
    it('el servidor emite lo que MIDE la etiqueta, y el CSS la acota', function () use ($data) {
        // El servidor no puede medir texto, pero sí contarlo. Con el ancho en `ch`, el CSS hace
        //
        //     left: clamp(0, kx% - ancho/2, 100% - ancho)
        //
        // — la centra sobre su tick si cabe, y la apoya en el borde si no. Exacto, sin umbrales.
        $view = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="mes"><x-kore::chart.line y="ingresos" /></x-kore::chart>
        BLADE);

        $view->assertSee('--ktw:', false);

        // El anclaje por borde ya no existe: era un parche que solo cubría los extremos.
        $view->assertDontSee('data-edge', false);
    });

    it('cada tick lleva SU ancho, no uno cualquiera', function () {
        $plot = new \KoreUi\Charts\Plot(
            frame: tap(new \KoreUi\Charts\ChartFrame('c', [
                ['m' => 'E', 'v' => 1],
                ['m' => 'Septiembre', 'v' => 2],
            ], 'm'), fn ($f) => $f->add(new \KoreUi\Charts\Marks\LineMark('v'))),
        );

        // «E» cae en el mínimo de 2ch; «Septiembre» son diez letras a 1,3ch de cota superior.
        expect($plot->xTicks[0]['width'])->toBe(2.0);
        expect($plot->xTicks[1]['width'])->toBe(13.0);
    });

    it('la etiqueta más ancha del tick manda: la de arriba o la de contexto', function () {
        // En un eje temporal, la etiqueta dice «14» (2 caracteres) y el contexto «feb. 2026»
        // (mucho más ancho). Si la caja se dimensionara por la de arriba, el contexto se saldría.
        $tz = new DateTimeZone('Europe/Madrid');
        $filas = [];

        for ($i = 0; $i < 20; $i++) {
            $filas[] = ['t' => (new DateTimeImmutable('2026-01-10', $tz))->modify("+{$i} days"), 'v' => $i];
        }

        $plot = new \KoreUi\Charts\Plot(
            frame: tap(new \KoreUi\Charts\ChartFrame('c', $filas, 't'),
                fn ($f) => $f->add(new \KoreUi\Charts\Marks\LineMark('v'))),
            timeFormat: new \KoreUi\Charts\Time\TimeFormat('es'),
        );

        $conContexto = array_values(array_filter($plot->xTicks, fn ($t) => $t['context'] !== null));

        expect($conContexto)->not->toBeEmpty();

        foreach ($conContexto as $tick) {
            expect($tick['width'])->toBeGreaterThanOrEqual(\KoreUi\Charts\TextWidth::ch($tick['context']));
        }
    });
});

it('la tabla accesible no arrastra scroll horizontal a toda la página', function () use ($data) {
    // `sr-only` esconde la caja con `width: 1px` + `overflow: hidden` + `clip-path`. Sobre una
    // <table>, el `width: 1px` SE IGNORA: el algoritmo de layout de tablas acota el ancho por
    // abajo al min-content. Medido en Brave, con un móvil de 375 px: la tabla ocupaba 321 px de
    // ancho. El clip-path sí aplicaba —así que no se veía y nadie se enteró— pero la caja seguía
    // ocupando, y con ella llegaba el scroll horizontal en toda la página.
    $html = $this->blade(<<<BLADE
        <x-kore::chart :data="{$data}" x="mes"><x-kore::chart.line y="ingresos" /></x-kore::chart>
    BLADE)->__toString();

    // El sr-only va en un <div>, que sí es una caja de bloque normal y sí obedece.
    expect($html)->toContain('<div class="sr-only">');
    expect($html)->not->toContain('<table class="sr-only">');
});

it('max-labels FUNCIONA — llevaba sin funcionar desde el primer día', function () {
    // Una prop con guion declarada en @props NO se puede leer con $attributes->get(): Blade ya la
    // ha extraído del bag. Así que `$attributes->get('max-labels')` devolvía null SIEMPRE, y el
    // tope de etiquetas del eje X no se aplicaba nunca. Salían todas.
    //
    // La forma correcta es declararla en camelCase (`maxLabels`) y dejar que Blade mapee el
    // `max-labels` del call-site. Salió a la luz al añadir `max-gap`, que fallaba igual.
    $data = "[['m'=>'A','v'=>1],['m'=>'B','v'=>2],['m'=>'C','v'=>3],['m'=>'D','v'=>4]]";

    $html = $this->blade(<<<BLADE
        <x-kore::chart :data="{$data}" x="m">
            <x-kore::chart.line y="v" />
            <x-kore::chart.axis-x max-labels="2" />
        </x-kore::chart>
    BLADE)->__toString();

    preg_match('/kore-chart-gutter-x.*?<\/div>/s', $html, $gutter);

    // Con el tope en 2, el salto es 2: sólo A y C… y siempre la última (D).
    expect(substr_count($gutter[0], 'kore-chart-tick'))->toBeLessThan(4);
    expect($gutter[0])->toContain('>A<')->toContain('>D<')->not->toContain('>B<');
});

describe('las etiquetas del eje X no se pisan entre sí', function () {
    it('cada etiqueta lleva el hueco que tiene hasta su vecina, en %', function () {
        // El servidor no mide texto, pero sí sabe DÓNDE cae cada tick. Con la distancia a la vecina
        // más cercana, el CSS acota cada etiqueta para que no se meta encima de la de al lado —
        // una categoría larga («Coste de ventas») se recorta con puntos suspensivos. Es un choque
        // distinto del de salirse de la tarjeta (ése lo tapa `width`), y era el que se veía en un
        // móvil con categorías largas.
        $data = "[
            ['m' => 'Coste de ventas', 'v' => 1],
            ['m' => 'Marketing', 'v' => 2],
            ['m' => 'Personal', 'v' => 3],
            ['m' => 'Impuestos', 'v' => 4],
        ]";

        $html = $this->blade(<<<BLADE
            <x-kore::chart :data="{$data}" x="m"><x-kore::chart.bar y="v" /></x-kore::chart>
        BLADE)->__toString();

        // 4 categorías → bandas cada 25 %, hueco 25 % × 0,9 = 22,5 %.
        expect($html)->toContain('--kroom: 22.5');
    });

    it('un solo tick tiene toda la anchura para él', function () {
        $frame = new \KoreUi\Charts\ChartFrame('c', [['m' => 'Único', 'v' => 1]], 'm');
        $frame->add(new \KoreUi\Charts\Marks\LineMark('v'));

        expect((new \KoreUi\Charts\Plot($frame))->xTicks[0]['room'])->toBe(100.0);
    });
});

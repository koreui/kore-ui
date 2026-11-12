<?php

use KoreUi\Core\Support\Motion;
use Symfony\Component\Finder\Finder;

/**
 * El movimiento se puede apagar, y de tres maneras que son la misma.
 *
 * Todo lo que se mueve en la librería lee tres tokens de duración. Apagarlo es
 * ponerlos a cero bajo `[data-kore-motion="off"]`; lo único que cambia entre
 * los tres niveles es quién escribe ese atributo.
 */
describe('el interruptor', function () {
    it('está encendido si nadie dice nada', function () {
        expect(Motion::activo())->toBeTrue();
    });

    it('obedece a la configuración', function () {
        config()->set('kore-ui.ui.motion', false);

        expect(Motion::activo())->toBeFalse();
    });

    it('y el prop de la etiqueta manda sobre la configuración', function () {
        config()->set('kore-ui.ui.motion', false);

        expect(Motion::activo(true))->toBeTrue();
    });
});

describe('`:animated="false"` en un componente', function () {
    it('marca la raíz', function (string $etiqueta) {
        $this->blade($etiqueta)->assertSee('data-kore-motion="off"', false);
    })->with([
        'button'    => ['<x-kore::button label="Guardar" :animated="false" />'],
        'dropdown'  => ['<x-kore::dropdown :animated="false"><x-slot:trigger><button>a</button></x-slot:trigger></x-kore::dropdown>'],
        'accordion' => ['<x-kore::accordion :animated="false"></x-kore::accordion>'],
        'tab'       => ['<x-kore::tab :animated="false"></x-kore::tab>'],
        'card'      => ['<x-kore::card :animated="false">x</x-kore::card>'],
        'alert'     => ['<x-kore::alert title="a" :animated="false" />'],
        'input'     => ['<x-kore::input name="a" :animated="false" />'],
        'select'    => ['<x-kore::select name="a" :options="[]" :animated="false" />'],
        'toggle'    => ['<x-kore::toggle name="a" :animated="false" />'],
        'checkbox'  => ['<x-kore::checkbox name="a" :animated="false" />'],
        'tooltip'   => ['<x-kore::tooltip text="a" :animated="false"><button>a</button></x-kore::tooltip>'],
        'stats'     => ['<x-kore::stats label="a" :value="1" :animated="false" />'],
    ]);

    it('no deja el prop escrito como atributo', function () {
        // Un prop que no se declara en @props acaba en el DOM tal cual:
        // `animated="false"`, que no es HTML y no hace nada.
        $this->blade('<x-kore::button label="Guardar" :animated="false" />')
            ->assertDontSee('animated=', false);
    });

    it('no marca nada cuando no se pide', function () {
        $this->blade('<x-kore::button label="Guardar" />')
            ->assertDontSee('data-kore-motion', false);
    });

    it('no marca cada componente cuando se apaga desde la configuración', function () {
        // Serían cientos de atributos idénticos en una página. Lo pone
        // `@koreScripts`, una vez, en <html>.
        config()->set('kore-ui.ui.motion', false);

        $this->blade('<x-kore::button label="Guardar" />')
            ->assertDontSee('data-kore-motion', false);
    });
});

describe('apagado para toda la aplicación', function () {
    it('lo deja escrito en la etiqueta del bundle', function () {
        config()->set('kore-ui.ui.motion', false);

        $this->blade('@koreScripts')
            ->assertSee('data-kore-motion="off"', false);
    });

    it('y no escribe nada cuando está encendido', function () {
        $this->blade('@koreScripts')
            ->assertDontSee('data-kore-motion', false);
    });

    it('el bundle lo pasa a <html> sin un script en línea', function () {
        $js = file_get_contents(__DIR__.'/../../resources/js/index.js');

        expect($js)->toContain('document.currentScript?.dataset.koreMotion')
            ->toContain("'livewire:navigated'");
    });
});

describe('la hoja de estilos', function () {
    beforeEach(function () {
        $this->css = file_get_contents(__DIR__.'/../../resources/css/kore-theme.css');
    });

    it('pone a cero todos los tokens de duración', function () {
        preg_match('/\[data-kore-motion="off"\],[^{]*\{([^}]*)\}/', $this->css, $m);

        $bloque = $m[1] ?? '';

        foreach ([
            '--kore-duration-fast', '--kore-duration-base', '--kore-duration-slow',
            '--kore-spring-duration', '--kore-sidebar-duration', '--kore-chart-tween',
        ] as $token) {
            expect($bloque)->toContain("{$token}: 0ms");
        }
    });

    it('hace lo mismo con prefers-reduced-motion', function () {
        $corte = strpos($this->css, '@media (prefers-reduced-motion: reduce)');
        $bloque = substr($this->css, $corte, 900);

        foreach (['--kore-duration-fast', '--kore-duration-base', '--kore-duration-slow'] as $token) {
            expect($bloque)->toContain("{$token}: 0ms");
        }
    });

    it('alcanza a los componentes cuya raíz no recibe los atributos', function () {
        // En un campo o un interruptor los atributos van al <input>. Sin el
        // `:has()`, apagar un toggle dejaba moviéndose la pista.
        expect($this->css)->toContain(':where(.kore-field, .kore-toggle, .kore-checkbox, .kore-radio):has([data-kore-motion="off"])');
    });

    it('no apaga el spinner', function () {
        preg_match_all('/\[data-kore-motion="off"\][^{]*\{[^}]*\}/', $this->css, $m);

        expect(implode("\n", $m[0]))->not->toContain('kore-anim-spinner');
    });
});

/**
 * La red que evita que el próximo componente vuelva a traer su propia duración.
 *
 * Una transición con `duration-200` escrita a mano funciona igual de bien que
 * una con el token… hasta que alguien apaga el movimiento y esa sigue
 * animándose. No da error en ninguna parte.
 */
it('ninguna plantilla trae una duración o una curva escrita a mano', function () {
    $problemas = [];

    $vistas = Finder::create()->files()->in(__DIR__.'/../../resources/views')->name('*.blade.php');

    foreach ($vistas as $vista) {
        foreach (preg_split('/\R/', $vista->getContents()) as $n => $linea) {
            if (preg_match('/(?<![\w:-])(duration-\d+|ease-(?:in|out|in-out|linear)|transition(?:-(?:colors|all|opacity|transform|shadow))?)(?![\w-])/', $linea, $m)
                && ! preg_match('/transition-all kore-duration-|\$transition|\'transition\'|transition:|`transition/', $linea)) {
                $problemas[] = sprintf('%s:%d  %s', $vista->getRelativePathname(), $n + 1, $m[1]);
            }
        }
    }

    expect($problemas)->toBe([], "Usa kore-transition, kore-transition-base/slow o kore-duration-*:\n".implode("\n", $problemas));
});

it('los paneles se abren sin directivas x-transition', function () {
    // Eran seis atributos por panel. Solo el gestor de overlays las conserva,
    // porque sus animaciones son configurables por tipo.
    $con = [];

    $vistas = Finder::create()->files()->in(__DIR__.'/../../resources/views')->name('*.blade.php');

    foreach ($vistas as $vista) {
        if (str_contains($vista->getContents(), 'x-transition') && $vista->getRelativePathname() !== 'overlay/manager.blade.php') {
            $con[] = $vista->getRelativePathname();
        }
    }

    expect($con)->toBe([]);
});

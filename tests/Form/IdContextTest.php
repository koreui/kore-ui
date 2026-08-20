<?php

use KoreUi\Core\Support\IdContext;

/**
 * Ids de campo deterministas.
 *
 * Con `uniqid()` el id cambiaba en cada render, y eso no es cosmético: el morph
 * de Livewire empareja los nodos por id, así que un id nuevo significa «otro
 * nodo» y el nodo se reemplaza en vez de actualizarse. Alpine arranca de cero y
 * se lleva por delante lo que el usuario estuviera haciendo — el desplegable
 * abierto, el mes que estaba mirando, la búsqueda a medio escribir.
 *
 * Y en los componentes que van con `wire:ignore`, el efecto es aún más
 * silencioso: el control conserva el id de la primera carga mientras la etiqueta
 * —que vive fuera— estrena uno nuevo, así que a partir del SEGUNDO render la
 * etiqueta apunta a un id que ya no existe.
 */
it('da el mismo id al mismo campo en dos peticiones distintas', function () {
    // Dos peticiones, no dos llamadas seguidas: dentro de un mismo render, dos
    // campos distintos deben llevar ids distintos. Lo que tiene que repetirse es
    // el id del MISMO campo cuando el servidor vuelve a pintar la misma vista,
    // que es lo que ocurre en cada ida y vuelta de Livewire.
    $primero = (string) $this->blade('<x-kore::input label="Sin nombre" />');

    $this->refreshApplication();

    $segundo = (string) $this->blade('<x-kore::input label="Sin nombre" />');

    preg_match('/id="(kore-[^"]+)"/', $primero, $a);
    preg_match('/id="(kore-[^"]+)"/', $segundo, $b);

    expect($a[1])->not->toBeEmpty()
        ->and($a[1])->toBe($b[1]);
});

it('da ids distintos a dos campos del mismo render', function () {
    $vista = $this->blade('
        <div>
            <x-kore::input label="Uno" />
            <x-kore::input label="Dos" />
        </div>
    ');

    preg_match_all('/id="(kore-f\d+)"/', (string) $vista, $todos);

    expect($todos[1])->toHaveCount(2)
        ->and($todos[1][0])->not->toBe($todos[1][1]);
});

it('deriva el id del name cuando lo hay', function () {
    expect(IdContext::para('email'))->toBe('kore-email')
        ->and(IdContext::para('form.datos.nombre'))->toBe('kore-form-datos-nombre');
});

it('convierte los corchetes en algo que un selector CSS admite', function () {
    // `kore-items[0]` obliga a escapar en cualquier `querySelector` y en
    // cualquier `label[for]`. Se normaliza a guiones.
    expect(IdContext::para('items[0]'))->toBe('kore-items-0');
});

it('empieza a contar de nuevo en cada petición', function () {
    // El contador es `scoped`, no `singleton`: con Octane el contenedor se
    // reutiliza entre peticiones, y si el contador no se reiniciara los ids
    // dejarían de coincidir entre el render inicial y el siguiente.
    $primero = IdContext::para(null);

    $this->refreshApplication();

    expect(IdContext::para(null))->toBe($primero);
});

it('acota el contador al componente Livewire que lo pide', function () {
    $contexto = new IdContext;

    expect($contexto->nextId('abc'))->toBe('kore-abc-f1')
        ->and($contexto->nextId('abc'))->toBe('kore-abc-f2')
        ->and($contexto->nextId('xyz'))->toBe('kore-xyz-f1')
        ->and($contexto->nextId())->toBe('kore-f1');
});

it('ningún componente se saca el id con uniqid()', function () {
    // Este cepo nace de una regresión propia: un `git checkout` de rescate
    // revirtió `checkbox.blade.php` a HEAD y se llevó su `IdContext` sin que
    // nada fallara. Los tests de arriba prueban `input`, y con eso el resto
    // podía volver a `uniqid()` en silencio.
    //
    $culpables = [];

    $vistas = \Symfony\Component\Finder\Finder::create()
        ->files()->in(__DIR__.'/../../resources/views/components')->name('*.blade.php');

    foreach ($vistas as $vista) {
        $rel = str_replace(['\\', '.blade.php'], ['/', ''], $vista->getRelativePathname());

        // Solo el código: la palabra aparece también en los comentarios que
        // explican por qué ya no se usa, y esos no son el fallo.
        $codigo = preg_replace('#(//|\{\{--).*#', '', $vista->getContents());

        if (str_contains($codigo, 'uniqid()')) {
            $culpables[] = $rel;
        }
    }

    expect($culpables)->toBe([], 'Usan uniqid() para su id: '.implode(', ', $culpables));
});

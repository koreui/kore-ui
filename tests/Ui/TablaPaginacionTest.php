<?php

/**
 * La paginación de `<x-kore::table>`: el mismo pie que el DataTable, y sus
 * controles con la vista de la librería en vez de la del framework.
 */
describe('paginación de la tabla normal', function () {
    $paginador = fn (int $pagina = 2) => (new \Illuminate\Pagination\LengthAwarePaginator(
        [['name' => 'Ana']], 95, 10, $pagina, ['path' => '/usuarios'],
    ));

    it('pinta el pie dentro del marco de la tabla', function () use ($paginador) {
        $html = (string) $this->blade(
            '<x-kore::table :headers="[[\'key\' => \'name\', \'label\' => \'Nombre\']]" :rows="$p->items()" :paginator="$p" />',
            ['p' => $paginador()],
        );

        expect($html)->toContain('Mostrando 11 a 11 de 95 resultados')
            ->toContain('aria-current="page"')
            // El recuento va después de la tabla y antes de cerrar el marco.
            ->and(strpos($html, '</table>'))->toBeLessThan(strpos($html, 'Mostrando 11'));
    });

    it('usa la vista de la librería, no la del framework', function () use ($paginador) {
        $html = (string) $this->blade('<x-kore::table.pagination :paginator="$p" />', ['p' => $paginador()]);

        // La de Laravel trae grises fijos de Tailwind; la nuestra, los tokens.
        expect($html)->not->toContain('text-gray-')
            ->not->toContain('bg-white')
            ->toContain('bg-kore-field text-kore-fg font-semibold')
            ->toContain('kore-focus');
    });

    it('fuera de Livewire los controles son enlaces', function () use ($paginador) {
        $html = (string) $this->blade('<x-kore::table.pagination :paginator="$p" />', ['p' => $paginador()]);

        expect($html)->toContain('href="/usuarios?page=1"')
            ->toContain('href="/usuarios?page=3"')
            ->toContain('rel="next"')
            ->not->toContain('wire:click');
    });

    it('no pinta un botón deshabilitado cuando no hay a dónde ir', function () use ($paginador) {
        $html = (string) $this->blade('<x-kore::table.pagination :paginator="$p" />', ['p' => $paginador(1)]);

        expect($html)->toContain('aria-disabled="true"')
            ->not->toContain('disabled>')
            ->not->toContain('rel="prev"');
    });

    it('con una sola página no pinta nada', function () {
        $uno = new \Illuminate\Pagination\LengthAwarePaginator([['name' => 'Ana']], 1, 10, 1);

        expect(trim((string) $this->blade('<x-kore::table.pagination :paginator="$p" />', ['p' => $uno])))->toBe('');
    });

    it('traduce el nombre de cada página', function () use ($paginador) {
        config()->set('kore-ui.datatable.translations.go_to_page', 'Ves a la pàgina :page');

        $this->blade('<x-kore::table.pagination :paginator="$p" />', ['p' => $paginador()])
            ->assertSee('Ves a la pàgina 3');
    });

    it('usa la ventana corta del DataTable y no la de Laravel', function () {
        // Página 9 de 19: primera, 7 a 11 y última. Con la ventana del framework
        // salían hasta diez números seguidos.
        $p = new \Illuminate\Pagination\LengthAwarePaginator([['name' => 'x']], 95, 5, 9, ['path' => '/usuarios']);
        $html = (string) $this->blade('<x-kore::table.pagination :paginator="$p" />', ['p' => $p]);

        preg_match_all('/(?:aria-current="page">|page=\d+"[^>]*>)(\d+)</', $html, $m);

        expect($m[1])->toBe(['1', '7', '8', '9', '10', '11', '19'])
            ->and(substr_count($html, '>…<'))->toBe(2);
    });

    it('sin huecos no pinta elipsis', function () {
        $p = new \Illuminate\Pagination\LengthAwarePaginator([['name' => 'x']], 20, 5, 2, ['path' => '/usuarios']);
        $html = (string) $this->blade('<x-kore::table.pagination :paginator="$p" />', ['p' => $p]);

        expect(substr_count($html, '>…<'))->toBe(0)
            ->and($html)->toContain('page=4');
    });
});

<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\Component;
use Livewire\Livewire;
use Livewire\WithPagination;

/**
 * La paginación de `<x-kore::table>` dentro de un componente Livewire.
 *
 * Fuera de Livewire los controles son enlaces; dentro tienen que ser botones con
 * `wire:click`, o cada cambio de página recargaría el documento entero y el
 * componente perdería su estado. La rama se decide por `$__livewire`, que es una
 * variable compartida con las vistas: si un día deja de llegar a la vista del
 * paginador, la tabla seguiría «funcionando» —con enlaces— y nadie lo vería.
 */
class TablaPaginadaConDatos extends Component
{
    use WithPagination;

    public function render()
    {
        $pagina = Paginator::resolveCurrentPage();
        $filas = collect(range(1, 95))->forPage($pagina, 5)
            ->map(fn ($i) => ['name' => 'Usuario '.$i])->values()->all();
        $paginador = new LengthAwarePaginator($filas, 95, 5, $pagina, ['path' => '/usuarios']);

        return view('kore-test::tabla-paginada', compact('filas', 'paginador'));
    }
}

beforeEach(function () {
    $dir = sys_get_temp_dir().'/kore-test-views';
    @mkdir($dir, 0777, true);
    file_put_contents($dir.'/tabla-paginada.blade.php', <<<'BLADE'
    <div>
        <x-kore::table
            :headers="[['key' => 'name', 'label' => 'Nombre']]"
            :rows="$filas"
            :paginator="$paginador"
        />
    </div>
    BLADE);
    app('view')->addNamespace('kore-test', $dir);
});

it('pinta botones con wire:click, no enlaces, dentro de Livewire', function () {
    Livewire::test(TablaPaginadaConDatos::class)
        ->assertSee('Usuario 1')
        ->assertSeeHtml("wire:click=\"gotoPage(2, 'page')\"")
        ->assertSeeHtml("wire:click=\"nextPage('page')\"")
        ->assertDontSeeHtml('href="/usuarios?page=2"');
});

it('cambia de página sin recargar y repinta filas, recuento y página actual', function () {
    Livewire::test(TablaPaginadaConDatos::class)
        ->call('gotoPage', 3, 'page')
        ->assertSee('Usuario 11')
        ->assertDontSee('Usuario 1 ')
        ->assertSee('Mostrando 11 a 15 de 95 resultados')
        ->assertSeeHtml("wire:click=\"previousPage('page')\"")
        ->call('nextPage', 'page')
        ->assertSee('Usuario 16')
        ->assertSee('Mostrando 16 a 20 de 95 resultados');
});

it('en la última página el siguiente no es un botón', function () {
    Livewire::test(TablaPaginadaConDatos::class)
        ->call('gotoPage', 19, 'page')
        ->assertSee('Usuario 95')
        ->assertDontSeeHtml("wire:click=\"nextPage('page')\"");
});

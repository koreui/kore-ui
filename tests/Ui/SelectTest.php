
/**
 * El panel del select tenía DOS `role="listbox"` anidados, y el de fuera
 * contenía la caja de búsqueda — un listbox solo admite opciones y grupos.
 * Un lector encontraba dos listas donde solo hay una, y ninguna con nombre.
 */
it('tiene un solo listbox, y con nombre', function () {
    $view = $this->blade('<x-kore::select name="pais" label="País" :options="[\'a\' => \'A\']" />');
    $html = $view->__toString();

    expect(substr_count($html, 'role="listbox"'))->toBe(1);
    $view->assertSee('aria-labelledby="', false)
        ->assertSee('aria-controls="', false);
});

<?php

$fields = "[['key' => 'name', 'label' => 'Nombre', 'type' => 'text'], ['key' => 'qty', 'label' => 'Cantidad', 'type' => 'number']]";

it('renders with label', function () use ($fields) {
    $view = $this->blade("<x-kore::repeater label=\"Ítems\" name=\"items\" :fields=\"{$fields}\" />");
    $view->assertSee('Ítems');
});

it('renders KoreRepeater Alpine data', function () use ($fields) {
    $view = $this->blade("<x-kore::repeater name=\"items\" :fields=\"{$fields}\" />");
    $view->assertSee('KoreRepeater', false);
});

it('wraps the editor in wire:ignore', function () use ($fields) {
    $view = $this->blade("<x-kore::repeater name=\"items\" :fields=\"{$fields}\" />");
    $view->assertSee('wire:ignore', false);
});

it('passes the field keys to the JS config', function () use ($fields) {
    $view = $this->blade("<x-kore::repeater name=\"items\" :fields=\"{$fields}\" />");
    $view->assertSee('name', false)
        ->assertSee('qty', false);
});

it('renders an input per field with x-model bound to the row', function () use ($fields) {
    $view = $this->blade("<x-kore::repeater name=\"items\" :fields=\"{$fields}\" />");
    $view->assertSee("row['name']", false)
        ->assertSee("row['qty']", false);
});

it('renders field labels', function () use ($fields) {
    $view = $this->blade("<x-kore::repeater name=\"items\" :fields=\"{$fields}\" />");
    $view->assertSee('Nombre')
        ->assertSee('Cantidad');
});

it('renders the add button', function () use ($fields) {
    $view = $this->blade("<x-kore::repeater name=\"items\" :fields=\"{$fields}\" />");
    $view->assertSee('addRow()', false);
});

it('renders remove buttons', function () use ($fields) {
    $view = $this->blade("<x-kore::repeater name=\"items\" :fields=\"{$fields}\" />");
    $view->assertSee('removeRow(index)', false);
});

it('renders a select field with options', function () {
    $selectFields = "[['key' => 'role', 'label' => 'Rol', 'type' => 'select', 'options' => ['admin' => 'Admin', 'user' => 'Usuario']]]";
    $view = $this->blade("<x-kore::repeater name=\"members\" :fields=\"{$selectFields}\" />");
    $view->assertSee('<select', false)
        ->assertSee('Admin')
        ->assertSee('Usuario');
});

it('renders drag handles when reorderable', function () use ($fields) {
    $view = $this->blade("<x-kore::repeater name=\"items\" :fields=\"{$fields}\" reorderable />");
    $view->assertSee('x-sort', false)
        ->assertSee('moveRow', false);
});

it('passes max config', function () use ($fields) {
    $view = $this->blade("<x-kore::repeater name=\"items\" :fields=\"{$fields}\" :max=\"3\" />");
    $view->assertSee('&quot;max&quot;:3', false);
});

/**
 * `size` estaba declarado y resuelto contra la configuración, y no llegaba a
 * ninguna parte: los campos de las filas tenían su tamaño escrito a mano. Ahora
 * lo heredan con `@aware`, con las mismas medidas que `<x-kore::input>`.
 */
it('el tamaño llega a los campos de las filas', function () {
    $campos = ':fields="[[\'key\' => \'nombre\']]"';

    expect((string) $this->blade('<x-kore::repeater label="P" name="p" size="sm" '.$campos.' />'))
        ->toContain('text-xs py-1.5 px-2.5');

    expect((string) $this->blade('<x-kore::repeater label="P" name="p" size="lg" '.$campos.' />'))
        ->toContain('text-base py-2.5 px-3.5');
});

it('el tamaño de las filas también sale de la configuración', function () {
    config()->set('kore-ui.form.size', 'lg');

    expect((string) $this->blade('<x-kore::repeater label="P" name="p" :fields="[[\'key\' => \'nombre\']]" />'))
        ->toContain('text-base py-2.5 px-3.5');
});

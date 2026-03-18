<?php

it('renders the container', function () {
    $view = $this->blade('<x-kore::tree :nodes="[]" />');
    $view->assertSee('KoreTree', false);
});

it('renders role tree', function () {
    $view = $this->blade('<x-kore::tree :nodes="[]" />');
    $view->assertSee('role="tree"', false);
});

it('passes nodes data via js directive', function () {
    $nodes = [['key' => 'a', 'label' => 'Node A', 'children' => []]];
    $view = $this->blade('<x-kore::tree :nodes="$nodes" />', ['nodes' => $nodes]);
    $view->assertSee('Node A', false);
});

it('passes nested children data', function () {
    $nodes = [
        ['key' => 'a', 'label' => 'Parent', 'children' => [
            ['key' => 'b', 'label' => 'Child', 'children' => []],
        ]],
    ];
    $view = $this->blade('<x-kore::tree :nodes="$nodes" />', ['nodes' => $nodes]);
    $view->assertSee('Parent', false)
        ->assertSee('Child', false);
});

it('renders filter input when enabled', function () {
    $view = $this->blade('<x-kore::tree :nodes="[]" :filter="true" />');
    $view->assertSee('x-model', false)
        ->assertSee('filterText', false);
});

it('does not render filter by default', function () {
    $view = $this->blade('<x-kore::tree :nodes="[]" />');
    $view->assertDontSee('filterText', false);
});

it('renders custom filter placeholder', function () {
    $view = $this->blade('<x-kore::tree :nodes="[]" :filter="true" filterPlaceholder="Buscar..." />');
    $view->assertSee('Buscar...', false);
});

it('passes selectable config', function () {
    $view = $this->blade('<x-kore::tree :nodes="[]" :selectable="true" />');
    $view->assertSee('selectable: true', false);
});

it('passes selection mode', function () {
    $view = $this->blade('<x-kore::tree :nodes="[]" :selectable="true" selectionMode="multiple" />');
    $view->assertSee("selectionMode: 'multiple'", false);
});

it('passes expanded keys', function () {
    $view = $this->blade('<x-kore::tree :nodes="[]" :expandedKeys="[\'a\', \'b\']" />');
    $view->assertSee('expandedKeys', false);
});

it('passes selected keys', function () {
    $view = $this->blade('<x-kore::tree :nodes="[]" :selectedKeys="[\'x\']" />');
    $view->assertSee('selectedKeys', false);
});

it('renders treeitem role in template', function () {
    $view = $this->blade('<x-kore::tree :nodes="[]" />');
    $view->assertSee('role="treeitem"', false);
});

it('renders aria-expanded binding', function () {
    $view = $this->blade('<x-kore::tree :nodes="[]" />');
    $view->assertSee('aria-expanded', false);
});

it('renders group role for nodes container', function () {
    $view = $this->blade('<x-kore::tree :nodes="[]" />');
    $view->assertSee('role="group"', false);
});

it('renders with custom attributes', function () {
    $view = $this->blade('<x-kore::tree :nodes="[]" class="mt-4" />');
    $view->assertSee('mt-4', false);
});

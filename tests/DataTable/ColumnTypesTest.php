<?php

use KoreUi\DataTable\Columns\ActionColumn;
use KoreUi\DataTable\Columns\BadgeColumn;
use KoreUi\DataTable\Columns\BooleanColumn;
use KoreUi\DataTable\Columns\ColorColumn;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\Columns\ComponentColumn;
use KoreUi\DataTable\Columns\DateColumn;
use KoreUi\DataTable\Columns\ImageColumn;
use KoreUi\DataTable\Columns\LinkColumn;
use KoreUi\DataTable\Columns\NumberColumn;
use KoreUi\DataTable\Columns\ProgressColumn;
use KoreUi\DataTable\Actions\RowAction;

// --- Column base: getType / getComponentProps ---

it('base Column returns text type', function () {
    $column = Column::make('Name', 'name');

    expect($column->getType())->toBe('text')
        ->and($column->getComponentProps())->toBe([]);
});

// --- BooleanColumn ---

it('creates BooleanColumn with correct type and defaults', function () {
    $column = BooleanColumn::make('Activo', 'is_active');

    expect($column->getType())->toBe('boolean')
        ->and($column->getAlign())->toBe('center')
        ->and($column->getComponentProps())->toHaveKey('size', 'md');
});

it('BooleanColumn supports custom icons and colors', function () {
    $column = BooleanColumn::make('Activo', 'is_active')
        ->trueIcon('check-circle')
        ->falseIcon('x-circle')
        ->trueColor('info')
        ->falseColor('warning')
        ->size('lg');

    $props = $column->getComponentProps();

    expect($props['trueIcon'])->toBe('check-circle')
        ->and($props['falseIcon'])->toBe('x-circle')
        ->and($props['trueColor'])->toBe('info')
        ->and($props['falseColor'])->toBe('warning')
        ->and($props['size'])->toBe('lg');
});

it('BooleanColumn inherits sorting and search', function () {
    $column = BooleanColumn::make('Activo', 'is_active')
        ->sortable()
        ->searchable();

    expect($column->isSortable())->toBeTrue()
        ->and($column->isSearchable())->toBeTrue();
});

// --- BadgeColumn ---

it('creates BadgeColumn with correct type', function () {
    $column = BadgeColumn::make('Rol', 'role');

    expect($column->getType())->toBe('badge');
});

it('BadgeColumn resolves color from mapping', function () {
    $column = BadgeColumn::make('Rol', 'role')
        ->colors([
            'admin'  => 'primary',
            'editor' => 'info',
            'user'   => 'muted',
        ]);

    expect($column->resolveColor('admin'))->toBe('primary')
        ->and($column->resolveColor('editor'))->toBe('info')
        ->and($column->resolveColor('unknown'))->toBe('muted');
});

it('BadgeColumn resolves icons from mapping', function () {
    $column = BadgeColumn::make('Rol', 'role')
        ->icons([
            'admin' => 'shield',
            'user'  => 'user',
        ]);

    expect($column->resolveIcon('admin'))->toBe('shield')
        ->and($column->resolveIcon('unknown'))->toBeNull();
});

it('BadgeColumn supports variant and size', function () {
    $column = BadgeColumn::make('Status', 'status')
        ->variant('outline')
        ->size('sm');

    $props = $column->getComponentProps();

    expect($props['variant'])->toBe('outline')
        ->and($props['size'])->toBe('sm');
});

// --- DateColumn ---

it('creates DateColumn with correct type', function () {
    $column = DateColumn::make('Creado', 'created_at');

    expect($column->getType())->toBe('date');
});

it('DateColumn formats date values', function () {
    $column = DateColumn::make('Creado', 'created_at')
        ->dateFormat('Y-m-d');

    $row = ['created_at' => '2025-03-15 14:30:00'];

    expect($column->getValue($row))->toBe('2025-03-15');
});

it('DateColumn supports diffForHumans', function () {
    $column = DateColumn::make('Creado', 'created_at')
        ->diffForHumans();

    $row = ['created_at' => now()->subHour()];

    expect($column->getValue($row))->toContain('1');
});

it('DateColumn supports tooltip format', function () {
    $column = DateColumn::make('Creado', 'created_at')
        ->dateFormat('d/m/Y')
        ->tooltipFormat('l, d F Y H:i');

    $row = ['created_at' => '2025-06-15 14:30:00'];

    expect($column->getTooltipValue($row))->not->toBeNull()
        ->and($column->getComponentProps())->toHaveKey('tooltipFormat');
});

it('DateColumn respects format callback priority', function () {
    $column = DateColumn::make('Creado', 'created_at')
        ->dateFormat('Y-m-d')
        ->format(fn ($value) => 'CUSTOM');

    $row = ['created_at' => '2025-03-15'];

    expect($column->getValue($row))->toBe('CUSTOM');
});

// --- ImageColumn ---

it('creates ImageColumn with correct type and defaults', function () {
    $column = ImageColumn::make('Avatar', 'avatar_url');

    expect($column->getType())->toBe('image')
        ->and($column->getAlign())->toBe('center');

    $props = $column->getComponentProps();
    expect($props['isAvatar'])->toBeTrue()
        ->and($props['size'])->toBe('sm')
        ->and($props['shape'])->toBe('circle');
});

it('ImageColumn resolves name from nameField', function () {
    $column = ImageColumn::make('Avatar', 'avatar_url')
        ->nameField('name');

    $row = ['avatar_url' => null, 'name' => 'John Doe'];

    expect($column->getNameValue($row))->toBe('John Doe');
});

// --- LinkColumn ---

it('creates LinkColumn with correct type', function () {
    $column = LinkColumn::make('Enlace', 'name');

    expect($column->getType())->toBe('link');
});

it('LinkColumn interpolates URL pattern', function () {
    $column = LinkColumn::make('Nombre', 'name')
        ->urlPattern('/users/{id}/edit');

    $row = ['id' => 42, 'name' => 'John'];

    expect($column->getUrl($row))->toBe('/users/42/edit');
});

it('LinkColumn supports URL callback', function () {
    $column = LinkColumn::make('Nombre', 'name')
        ->url(fn ($row) => '/custom/' . data_get($row, 'id'));

    $row = ['id' => 5, 'name' => 'Alice'];

    expect($column->getUrl($row))->toBe('/custom/5');
});

it('LinkColumn supports openInNewTab and icon', function () {
    $column = LinkColumn::make('Nombre', 'name')
        ->openInNewTab()
        ->icon('external-link')
        ->color('info');

    $props = $column->getComponentProps();

    expect($props['openInNewTab'])->toBeTrue()
        ->and($props['icon'])->toBe('external-link')
        ->and($props['color'])->toBe('info');
});

// --- NumberColumn ---

it('creates NumberColumn with right alignment', function () {
    $column = NumberColumn::make('Salario', 'salary');

    expect($column->getType())->toBe('number')
        ->and($column->getAlign())->toBe('right');
});

it('NumberColumn formats with number_format', function () {
    $column = NumberColumn::make('Monto', 'amount')
        ->decimals(2)
        ->separators('.', ',');

    $row = ['amount' => 1234567.89];

    expect($column->getValue($row))->toBe('1,234,567.89');
});

it('NumberColumn supports prefix and suffix', function () {
    $column = NumberColumn::make('Peso', 'weight')
        ->decimals(1)
        ->suffix(' kg');

    $row = ['weight' => 75.5];

    expect($column->getValue($row))->toBe('75.5 kg');
});

it('NumberColumn formats currency with money()', function () {
    $column = NumberColumn::make('Salario', 'salary')
        ->money('USD', 'en_US');

    $row = ['salary' => 50000];
    $value = $column->getValue($row);

    expect($value)->toContain('50,000')
        ->and($value)->toContain('$');
});

it('NumberColumn respects format callback priority', function () {
    $column = NumberColumn::make('Amount', 'amount')
        ->decimals(2)
        ->format(fn ($value) => 'CUSTOM');

    $row = ['amount' => 100];

    expect($column->getValue($row))->toBe('CUSTOM');
});

// --- ProgressColumn ---

it('creates ProgressColumn with correct type', function () {
    $column = ProgressColumn::make('Progreso', 'completion');

    expect($column->getType())->toBe('progress');
});

it('ProgressColumn configures all props', function () {
    $column = ProgressColumn::make('Progreso', 'completion')
        ->max(200)
        ->size('lg')
        ->color('success')
        ->showValue(false)
        ->circle()
        ->striped();

    $props = $column->getComponentProps();

    expect($props['max'])->toBe(200)
        ->and($props['size'])->toBe('lg')
        ->and($props['color'])->toBe('success')
        ->and($props['showValue'])->toBeFalse()
        ->and($props['circle'])->toBeTrue()
        ->and($props['striped'])->toBeTrue();
});

// --- ColorColumn ---

it('creates ColorColumn with correct type', function () {
    $column = ColorColumn::make('Color', 'hex_color');

    expect($column->getType())->toBe('color');
});

it('ColorColumn configures display props', function () {
    $column = ColorColumn::make('Color', 'hex_color')
        ->showLabel(false)
        ->swatchSize('lg')
        ->swatchShape('circle')
        ->copyable();

    $props = $column->getComponentProps();

    expect($props['showLabel'])->toBeFalse()
        ->and($props['swatchSize'])->toBe('lg')
        ->and($props['swatchShape'])->toBe('circle')
        ->and($props['copyable'])->toBeTrue();
});

// --- ComponentColumn ---

it('creates ComponentColumn with correct type', function () {
    $column = ComponentColumn::make('Custom', 'data');

    expect($column->getType())->toBe('component');
});

it('ComponentColumn supports component mode', function () {
    $column = ComponentColumn::make('Custom', 'data')
        ->component('x-my-component');

    expect($column->getComponentName())->toBe('x-my-component')
        ->and($column->isBladeComponent())->toBeTrue();
});

it('ComponentColumn supports view mode', function () {
    $column = ComponentColumn::make('Custom', 'data')
        ->view('partials.my-view');

    expect($column->getComponentName())->toBe('partials.my-view')
        ->and($column->isBladeComponent())->toBeFalse();
});

it('ComponentColumn resolves props via callback', function () {
    $column = ComponentColumn::make('Custom', 'data')
        ->component('x-comp')
        ->props(fn ($value, $row) => ['name' => data_get($row, 'name'), 'count' => $value]);

    $row = ['data' => 5, 'name' => 'Test'];
    $resolved = $column->resolveProps(5, $row);

    expect($resolved)->toBe(['name' => 'Test', 'count' => 5]);
});

it('ComponentColumn returns default props without callback', function () {
    $column = ComponentColumn::make('Custom', 'data')
        ->view('some.view');

    $row = ['data' => 'hello'];
    $resolved = $column->resolveProps('hello', $row);

    expect($resolved)->toBe(['value' => 'hello', 'row' => $row]);
});

// --- ActionColumn ---

it('creates ActionColumn with correct defaults', function () {
    $column = ActionColumn::make();

    expect($column->getType())->toBe('action')
        ->and($column->getLabel())->toBe('Acciones')
        ->and($column->getField())->toBe('__actions')
        ->and($column->getAlign())->toBe('center')
        ->and($column->isSortable())->toBeFalse()
        ->and($column->isSearchable())->toBeFalse()
        ->and($column->getValue([]))->toBeNull();
});

it('ActionColumn holds RowActions and filters by visibility', function () {
    $column = ActionColumn::make()
        ->actions([
            RowAction::make('edit', 'Editar')->icon('pencil'),
            RowAction::make('delete', 'Eliminar')
                ->icon('trash')
                ->hidden(fn ($row) => data_get($row, 'role') === 'admin'),
        ]);

    $adminRow = ['id' => 1, 'role' => 'admin'];
    $userRow = ['id' => 2, 'role' => 'user'];

    expect($column->getActions())->toHaveCount(2)
        ->and($column->getVisibleActions($adminRow))->toHaveCount(1)
        ->and($column->getVisibleActions($userRow))->toHaveCount(2);
});

it('ActionColumn supports inline mode', function () {
    $column = ActionColumn::make()->inline();

    $props = $column->getComponentProps();
    expect($props['displayMode'])->toBe('inline');
});

// --- All types inherit Column features ---

it('all column types support hidden/hiddenIf', function () {
    $types = [
        BooleanColumn::make('A', 'a')->hidden(),
        BadgeColumn::make('B', 'b')->hidden(),
        DateColumn::make('C', 'c')->hidden(),
        ImageColumn::make('D', 'd')->hidden(),
        LinkColumn::make('E', 'e')->hidden(),
        NumberColumn::make('F', 'f')->hidden(),
        ProgressColumn::make('G', 'g')->hidden(),
        ColorColumn::make('H', 'h')->hidden(),
        ComponentColumn::make('I', 'i')->hidden(),
    ];

    foreach ($types as $col) {
        expect($col->isHidden())->toBeTrue();
    }
});

it('all column types support collapseOnMobile', function () {
    $types = [
        BooleanColumn::make('A', 'a')->collapseOnMobile(),
        BadgeColumn::make('B', 'b')->collapseOnMobile(),
        DateColumn::make('C', 'c')->collapseOnMobile(),
        NumberColumn::make('F', 'f')->collapseOnMobile(),
        ProgressColumn::make('G', 'g')->collapseOnMobile(),
        ColorColumn::make('H', 'h')->collapseOnMobile(),
    ];

    foreach ($types as $col) {
        expect($col->isCollapsedOnMobile())->toBeTrue();
    }
});

it('blocks javascript: URL in LinkColumn url callback', function () {
    $column = LinkColumn::make('Link', 'field')
        ->url(fn ($row) => "javascript:alert('XSS')");
    expect($column->getUrl([]))->toBeNull();
});

it('blocks javascript: URL in LinkColumn urlPattern', function () {
    $column = LinkColumn::make('Link', 'field')
        ->urlPattern("javascript:void({id})");
    expect($column->getUrl(['id' => 1]))->toBeNull();
});

it('allows https URL in LinkColumn urlPattern', function () {
    $column = LinkColumn::make('Link', 'field')
        ->urlPattern('https://example.com/users/{id}');
    expect($column->getUrl(['id' => 5]))->toBe('https://example.com/users/5');
});

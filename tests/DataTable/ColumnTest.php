<?php

use KoreUi\DataTable\Columns\Column;

it('creates a column with make', function () {
    $column = Column::make('Nombre', 'name');

    expect($column->getLabel())->toBe('Nombre')
        ->and($column->getField())->toBe('name');
});

it('infers field from label when not provided', function () {
    $column = Column::make('First Name');

    expect($column->getField())->toBe('first_name');
});

it('supports fluent API for sorting', function () {
    $column = Column::make('Name', 'name')->sortable();

    expect($column->isSortable())->toBeTrue()
        ->and($column->getSortField())->toBe('name');
});

it('supports custom sort field', function () {
    $column = Column::make('Name', 'name')->sortableAs('users.name');

    expect($column->isSortable())->toBeTrue()
        ->and($column->getSortField())->toBe('users.name');
});

it('supports fluent API for search', function () {
    $column = Column::make('Name', 'name')->searchable();

    expect($column->isSearchable())->toBeTrue()
        ->and($column->getSearchField())->toBe('name');
});

it('supports custom search field', function () {
    $column = Column::make('Name', 'name')->searchableAs('users.name');

    expect($column->isSearchable())->toBeTrue()
        ->and($column->getSearchField())->toBe('users.name');
});

it('supports search callback', function () {
    $callback = fn ($query, $term) => $query->where('name', 'like', "%{$term}%");
    $column = Column::make('Name', 'name')->searchCallback($callback);

    expect($column->isSearchable())->toBeTrue()
        ->and($column->getSearchCallback())->toBe($callback);
});

it('supports visibility control', function () {
    $column = Column::make('ID', 'id')->hidden();

    expect($column->isHidden())->toBeTrue();
});

it('supports conditional visibility', function () {
    $column = Column::make('ID', 'id')->hiddenIf(fn () => true);

    expect($column->isHidden())->toBeTrue();

    $column2 = Column::make('ID', 'id')->hiddenIf(fn () => false);

    expect($column2->isHidden())->toBeFalse();
});

it('gets value from array row', function () {
    $column = Column::make('Name', 'name');
    $row = ['name' => 'John', 'email' => 'john@example.com'];

    expect($column->getValue($row))->toBe('John');
});

it('gets value from object row', function () {
    $column = Column::make('Name', 'name');
    $row = (object) ['name' => 'John', 'email' => 'john@example.com'];

    expect($column->getValue($row))->toBe('John');
});

it('gets value with dot notation', function () {
    $column = Column::make('City', 'address.city');
    $row = ['address' => ['city' => 'Madrid']];

    expect($column->getValue($row))->toBe('Madrid');
});

it('returns default value when field missing', function () {
    $column = Column::make('Phone', 'phone')->default('N/A');
    $row = ['name' => 'John'];

    expect($column->getValue($row))->toBe('N/A');
});

it('applies format callback', function () {
    $column = Column::make('Name', 'name')
        ->format(fn ($value, $row) => strtoupper($value));

    $row = ['name' => 'John'];

    expect($column->getValue($row))->toBe('JOHN');
});

it('supports width and minWidth', function () {
    $column = Column::make('Name', 'name')->width(200)->minWidth(100);

    expect($column->getWidth())->toBe(200)
        ->and($column->getMinWidth())->toBe(100);
});

it('supports align', function () {
    $column = Column::make('Amount', 'amount')->align('right');

    expect($column->getAlign())->toBe('right');
});

it('supports html mode', function () {
    $column = Column::make('Status', 'status')->html();

    expect($column->isHtml())->toBeTrue();
});

it('supports wrap control', function () {
    $column = Column::make('Name', 'name')->wrap(false);

    expect($column->isWrap())->toBeFalse();
});

it('converts to array', function () {
    $column = Column::make('Name', 'name')
        ->sortable()
        ->searchable()
        ->align('center')
        ->width(200);

    $array = $column->toArray();

    expect($array)->toMatchArray([
        'label'      => 'Name',
        'field'      => 'name',
        'sortable'   => true,
        'searchable' => true,
        'hidden'     => false,
        'align'      => 'center',
        'wrap'       => true,
        'html'       => false,
        'width'      => 200,
        'minWidth'   => null,
    ]);
});

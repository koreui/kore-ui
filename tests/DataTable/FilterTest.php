<?php

use KoreUi\DataTable\Filters\BooleanFilter;
use KoreUi\DataTable\Filters\DateFilter;
use KoreUi\DataTable\Filters\DateRangeFilter;
use KoreUi\DataTable\Filters\MultiSelectFilter;
use KoreUi\DataTable\Filters\NumberFilter;
use KoreUi\DataTable\Filters\NumberRangeFilter;
use KoreUi\DataTable\Filters\SelectFilter;
use KoreUi\DataTable\Filters\TextFilter;

// --- Filter Base / IsFilter ---

it('creates a filter with make()', function () {
    $filter = TextFilter::make('Nombre', 'name');

    expect($filter->getLabel())->toBe('Nombre')
        ->and($filter->getColumn())->toBe('name')
        ->and($filter->getKey())->toBe('name');
});

it('infers column from label if not provided', function () {
    $filter = TextFilter::make('First Name');

    expect($filter->getColumn())->toBe('first_name');
});

it('converts dot notation to underscore for key', function () {
    $filter = TextFilter::make('Department', 'department.name');

    expect($filter->getKey())->toBe('department_name');
});

it('supports custom key', function () {
    $filter = TextFilter::make('Name', 'name')->key('custom_name');

    expect($filter->getKey())->toBe('custom_name');
});

it('supports fluent default value', function () {
    $filter = TextFilter::make('Name', 'name')->default('test');

    expect($filter->getDefault())->toBe('test');
});

it('supports fluent placeholder', function () {
    $filter = TextFilter::make('Name', 'name')->placeholder('Buscar...');

    expect($filter->getPlaceholder())->toBe('Buscar...');
});

it('supports fluent position', function () {
    $filter = TextFilter::make('Name', 'name')->position(5);

    expect($filter->getPosition())->toBe(5);
});

it('supports hidden with boolean', function () {
    $filter = TextFilter::make('Name', 'name')->hidden();

    expect($filter->isHidden())->toBeTrue();
});

it('supports hiddenIf with closure', function () {
    $filter = TextFilter::make('Name', 'name')->hiddenIf(fn () => true);

    expect($filter->isHidden())->toBeTrue();
});

it('hiddenIf closure returning false shows filter', function () {
    $filter = TextFilter::make('Name', 'name')->hiddenIf(fn () => false);

    expect($filter->isHidden())->toBeFalse();
});

it('supports custom pill callback', function () {
    $filter = TextFilter::make('City', 'city')
        ->pill(fn ($value) => "Ciudad: {$value}");

    expect($filter->getPillText('Madrid'))->toBe('Ciudad: Madrid');
});

it('generates default pill text', function () {
    $filter = TextFilter::make('City', 'city');

    expect($filter->getPillText('Madrid'))->toBe('City: Madrid');
});

it('returns null pill text when no value', function () {
    $filter = TextFilter::make('City', 'city');

    expect($filter->getPillText(null))->toBeNull()
        ->and($filter->getPillText(''))->toBeNull();
});

it('supports custom callback for apply', function () {
    $filter = TextFilter::make('Name', 'name')
        ->callback(fn ($query, $value) => $query->where('name', 'like', "{$value}%"));

    expect($filter->getCallback())->not->toBeNull();
});

it('hasValue returns false for null, empty string, empty array', function () {
    $filter = TextFilter::make('Name', 'name');

    expect($filter->hasValue(null))->toBeFalse()
        ->and($filter->hasValue(''))->toBeFalse()
        ->and($filter->hasValue([]))->toBeFalse();
});

it('hasValue returns true for non-empty values', function () {
    $filter = TextFilter::make('Name', 'name');

    expect($filter->hasValue('test'))->toBeTrue()
        ->and($filter->hasValue(0))->toBeTrue()
        ->and($filter->hasValue(false))->toBeTrue();
});

// --- TextFilter ---

it('text filter has correct type', function () {
    expect(TextFilter::make('Name', 'name')->getType())->toBe('text');
});

it('text filter supports debounce', function () {
    $filter = TextFilter::make('Name', 'name')->debounce(500);

    expect($filter->getDebounce())->toBe(500)
        ->and($filter->getComponentProps()['debounce'])->toBe(500);
});

it('text filter default debounce is 300', function () {
    $filter = TextFilter::make('Name', 'name');

    expect($filter->getDebounce())->toBe(300);
});

// --- SelectFilter ---

it('select filter has correct type', function () {
    expect(SelectFilter::make('City', 'city')->getType())->toBe('select');
});

it('select filter supports options', function () {
    $options = [['label' => 'A', 'value' => 'a']];
    $filter = SelectFilter::make('City', 'city')
        ->options($options)
        ->optionLabel('label')
        ->optionValue('value');

    expect($filter->getOptions())->toBe($options)
        ->and($filter->getOptionLabel())->toBe('label')
        ->and($filter->getOptionValue())->toBe('value');
});

// --- MultiSelectFilter ---

it('multi-select filter has correct type', function () {
    expect(MultiSelectFilter::make('Cities', 'city')->getType())->toBe('multi-select');
});

it('multi-select filter supports max', function () {
    $filter = MultiSelectFilter::make('Cities', 'city')->max(3);

    expect($filter->getMax())->toBe(3);
});

it('multi-select hasValue checks array count', function () {
    $filter = MultiSelectFilter::make('Cities', 'city');

    expect($filter->hasValue([]))->toBeFalse()
        ->and($filter->hasValue(['Madrid']))->toBeTrue()
        ->and($filter->hasValue(null))->toBeFalse();
});

// --- BooleanFilter ---

it('boolean filter has correct type', function () {
    expect(BooleanFilter::make('Active', 'is_active')->getType())->toBe('boolean');
});

it('boolean filter supports custom labels', function () {
    $filter = BooleanFilter::make('Active', 'is_active')
        ->trueLabel('Activo')
        ->falseLabel('Inactivo')
        ->allLabel('Cualquiera');

    expect($filter->getTrueLabel())->toBe('Activo')
        ->and($filter->getFalseLabel())->toBe('Inactivo')
        ->and($filter->getAllLabel())->toBe('Cualquiera');
});

it('boolean filter hasValue is false for null and empty string', function () {
    $filter = BooleanFilter::make('Active', 'is_active');

    expect($filter->hasValue(null))->toBeFalse()
        ->and($filter->hasValue(''))->toBeFalse();
});

it('boolean filter hasValue is true for "0" and "1"', function () {
    $filter = BooleanFilter::make('Active', 'is_active');

    expect($filter->hasValue('0'))->toBeTrue()
        ->and($filter->hasValue('1'))->toBeTrue();
});

it('boolean filter pill text shows correct label', function () {
    $filter = BooleanFilter::make('Active', 'is_active');

    expect($filter->getPillText('1'))->toBe('Active: Sí')
        ->and($filter->getPillText('0'))->toBe('Active: No');
});

// --- DateFilter ---

it('date filter has correct type', function () {
    expect(DateFilter::make('Fecha', 'created_at')->getType())->toBe('date');
});

it('date filter supports min/max dates', function () {
    $filter = DateFilter::make('Fecha', 'created_at')
        ->minDate('2024-01-01')
        ->maxDate('2024-12-31');

    expect($filter->getMinDate())->toBe('2024-01-01')
        ->and($filter->getMaxDate())->toBe('2024-12-31');
});

// --- DateRangeFilter ---

it('date range filter has correct type', function () {
    expect(DateRangeFilter::make('Rango', 'created_at')->getType())->toBe('date-range');
});

it('date range hasValue checks array with start or end', function () {
    $filter = DateRangeFilter::make('Rango', 'created_at');

    expect($filter->hasValue(null))->toBeFalse()
        ->and($filter->hasValue([]))->toBeFalse()
        ->and($filter->hasValue(['start' => null, 'end' => null]))->toBeFalse()
        ->and($filter->hasValue(['start' => '2024-01-01', 'end' => null]))->toBeTrue()
        ->and($filter->hasValue(['start' => null, 'end' => '2024-12-31']))->toBeTrue()
        ->and($filter->hasValue(['start' => '2024-01-01', 'end' => '2024-12-31']))->toBeTrue();
});

it('date range pill text shows range', function () {
    $filter = DateRangeFilter::make('Fecha', 'created_at');

    expect($filter->getPillText(['start' => '2024-01-01', 'end' => '2024-12-31']))
        ->toBe('Fecha: 2024-01-01 — 2024-12-31');
});

it('date range pill text shows desde when only start', function () {
    $filter = DateRangeFilter::make('Fecha', 'created_at');

    expect($filter->getPillText(['start' => '2024-01-01', 'end' => null]))
        ->toBe('Fecha: desde 2024-01-01');
});

it('date range pill text shows hasta when only end', function () {
    $filter = DateRangeFilter::make('Fecha', 'created_at');

    expect($filter->getPillText(['start' => null, 'end' => '2024-12-31']))
        ->toBe('Fecha: hasta 2024-12-31');
});

// --- NumberFilter ---

it('number filter has correct type', function () {
    expect(NumberFilter::make('Age', 'age')->getType())->toBe('number');
});

it('number filter supports min/max/step/operator', function () {
    $filter = NumberFilter::make('Age', 'age')
        ->min(0)
        ->max(120)
        ->step(1)
        ->operator('>=');

    expect($filter->getMin())->toBe(0.0)
        ->and($filter->getMax())->toBe(120.0)
        ->and($filter->getStep())->toBe(1.0)
        ->and($filter->getOperator())->toBe('>=');
});

it('number filter hasValue is false for null and empty string', function () {
    $filter = NumberFilter::make('Age', 'age');

    expect($filter->hasValue(null))->toBeFalse()
        ->and($filter->hasValue(''))->toBeFalse();
});

it('number filter hasValue is true for zero', function () {
    $filter = NumberFilter::make('Age', 'age');

    expect($filter->hasValue(0))->toBeTrue();
});

// --- NumberRangeFilter ---

it('number range filter has correct type', function () {
    expect(NumberRangeFilter::make('Age', 'age')->getType())->toBe('number-range');
});

it('number range hasValue checks array with min or max', function () {
    $filter = NumberRangeFilter::make('Age', 'age');

    expect($filter->hasValue(null))->toBeFalse()
        ->and($filter->hasValue([]))->toBeFalse()
        ->and($filter->hasValue(['min' => null, 'max' => null]))->toBeFalse()
        ->and($filter->hasValue(['min' => '', 'max' => '']))->toBeFalse()
        ->and($filter->hasValue(['min' => 18, 'max' => null]))->toBeTrue()
        ->and($filter->hasValue(['min' => null, 'max' => 65]))->toBeTrue()
        ->and($filter->hasValue(['min' => 18, 'max' => 65]))->toBeTrue();
});

it('number range pill shows range', function () {
    $filter = NumberRangeFilter::make('Edad', 'age');

    expect($filter->getPillText(['min' => 18, 'max' => 65]))->toBe('Edad: 18 — 65');
});

it('number range pill shows >= when only min', function () {
    $filter = NumberRangeFilter::make('Edad', 'age');

    expect($filter->getPillText(['min' => 18, 'max' => null]))->toBe('Edad: >= 18');
});

it('number range pill shows <= when only max', function () {
    $filter = NumberRangeFilter::make('Edad', 'age');

    expect($filter->getPillText(['min' => null, 'max' => 65]))->toBe('Edad: <= 65');
});

it('number range supports min/max/step', function () {
    $filter = NumberRangeFilter::make('Age', 'age')
        ->min(0)
        ->max(120)
        ->step(5);

    expect($filter->getMin())->toBe(0.0)
        ->and($filter->getMax())->toBe(120.0)
        ->and($filter->getStep())->toBe(5.0);
});

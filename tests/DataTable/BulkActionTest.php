<?php

use KoreUi\DataTable\Actions\BulkAction;

it('creates a bulk action with make()', function () {
    $action = BulkAction::make('delete', 'Eliminar');

    expect($action->getIdentifier())->toBe('delete')
        ->and($action->getLabel())->toBe('Eliminar');
});

it('supports fluent icon', function () {
    $action = BulkAction::make('delete', 'Eliminar')->icon('trash-2');

    expect($action->getIcon())->toBe('trash-2');
});

it('supports fluent color', function () {
    $action = BulkAction::make('delete', 'Eliminar')->color('destructive');

    expect($action->getColor())->toBe('destructive');
});

it('has default color of primary', function () {
    $action = BulkAction::make('delete', 'Eliminar');

    expect($action->getColor())->toBe('primary');
});

it('supports confirm message and description', function () {
    $action = BulkAction::make('delete', 'Eliminar')
        ->confirm('¿Eliminar?', 'No se puede deshacer.');

    expect($action->hasConfirm())->toBeTrue()
        ->and($action->getConfirmMessage())->toBe('¿Eliminar?')
        ->and($action->getConfirmDescription())->toBe('No se puede deshacer.');
});

it('has no confirm by default', function () {
    $action = BulkAction::make('delete', 'Eliminar');

    expect($action->hasConfirm())->toBeFalse()
        ->and($action->getConfirmMessage())->toBeNull();
});

it('supports hidden with boolean', function () {
    $action = BulkAction::make('delete', 'Eliminar')->hidden();

    expect($action->isHidden())->toBeTrue();
});

it('supports hidden with closure', function () {
    $action = BulkAction::make('delete', 'Eliminar')
        ->hidden(fn () => true);

    expect($action->isHidden())->toBeTrue();
});

it('supports hiddenWhenEmpty', function () {
    $action = BulkAction::make('delete', 'Eliminar')->hiddenWhenEmpty();

    expect($action->isHiddenWhenEmpty())->toBeTrue();
});

it('supports separator', function () {
    $action = BulkAction::make('delete', 'Eliminar')->separator();

    expect($action->hasSeparator())->toBeTrue();
});

it('is not hidden by default', function () {
    $action = BulkAction::make('delete', 'Eliminar');

    expect($action->isHidden())->toBeFalse();
});

it('icon is null by default', function () {
    $action = BulkAction::make('delete', 'Eliminar');

    expect($action->getIcon())->toBeNull();
});

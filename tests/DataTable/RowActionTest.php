<?php

use KoreUi\DataTable\Actions\RowAction;

it('creates a RowAction with make', function () {
    $action = RowAction::make('edit', 'Editar');

    expect($action->getIdentifier())->toBe('edit')
        ->and($action->getLabel())->toBe('Editar')
        ->and($action->getColor())->toBe('primary')
        ->and($action->getIcon())->toBeNull()
        ->and($action->hasConfirm())->toBeFalse()
        ->and($action->hasSeparator())->toBeFalse()
        ->and($action->hasUrl())->toBeFalse();
});

it('supports fluent configuration', function () {
    $action = RowAction::make('delete', 'Eliminar')
        ->icon('trash')
        ->color('destructive')
        ->confirm('¿Estás seguro?', 'Esta acción no se puede deshacer')
        ->separator()
        ->openInNewTab();

    expect($action->getIcon())->toBe('trash')
        ->and($action->getColor())->toBe('destructive')
        ->and($action->hasConfirm())->toBeTrue()
        ->and($action->getConfirmMessage())->toBe('¿Estás seguro?')
        ->and($action->getConfirmDescription())->toBe('Esta acción no se puede deshacer')
        ->and($action->hasSeparator())->toBeTrue()
        ->and($action->opensInNewTab())->toBeTrue();
});

it('interpolates URL pattern with row data', function () {
    $action = RowAction::make('view', 'Ver')
        ->urlPattern('/users/{id}/profile');

    $row = ['id' => 42, 'name' => 'John'];

    expect($action->hasUrl())->toBeTrue()
        ->and($action->getUrl($row))->toBe('/users/42/profile');
});

it('interpolates dot notation in URL pattern', function () {
    $action = RowAction::make('view', 'Ver')
        ->urlPattern('/teams/{team.id}/users/{id}');

    $row = ['id' => 5, 'team' => ['id' => 3]];

    expect($action->getUrl($row))->toBe('/teams/3/users/5');
});

it('supports URL callback', function () {
    $action = RowAction::make('view', 'Ver')
        ->url(fn ($row) => route('users.show', ['user' => data_get($row, 'id')]));

    // We can't test route() without Laravel, but we can test callback execution
    $action2 = RowAction::make('view', 'Ver')
        ->url(fn ($row) => '/custom/' . data_get($row, 'id'));

    $row = ['id' => 7];

    expect($action2->getUrl($row))->toBe('/custom/7');
});

it('supports wireMethod', function () {
    $action = RowAction::make('activate', 'Activar')
        ->wireMethod('activateUser');

    expect($action->getWireMethod())->toBe('activateUser');
});

it('supports static hidden', function () {
    $action = RowAction::make('edit', 'Editar')->hidden();

    expect($action->isHidden())->toBeTrue();
});

it('supports callback hidden with row context', function () {
    $action = RowAction::make('delete', 'Eliminar')
        ->hidden(fn ($row) => data_get($row, 'role') === 'admin');

    $adminRow = ['id' => 1, 'role' => 'admin'];
    $userRow = ['id' => 2, 'role' => 'user'];

    expect($action->isHidden($adminRow))->toBeTrue()
        ->and($action->isHidden($userRow))->toBeFalse();
});

it('returns null URL when neither pattern nor callback set', function () {
    $action = RowAction::make('edit', 'Editar');

    expect($action->getUrl(['id' => 1]))->toBeNull();
});

it('blocks javascript: URL in RowAction url callback', function () {
    $action = RowAction::make('evil', 'Evil')
        ->url(fn ($row) => "javascript:alert('XSS')");
    expect($action->getUrl([]))->toBeNull();
});

it('blocks javascript: URL in RowAction urlPattern', function () {
    $action = RowAction::make('evil', 'Evil')
        ->urlPattern("javascript:alert('{id}')");
    expect($action->getUrl(['id' => 1]))->toBeNull();
});

it('allows relative urlPattern in RowAction', function () {
    $action = RowAction::make('view', 'Ver')
        ->urlPattern('/users/{id}');
    expect($action->getUrl(['id' => 42]))->toBe('/users/42');
});

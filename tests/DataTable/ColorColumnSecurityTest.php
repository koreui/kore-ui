<?php

use Illuminate\Support\Js;
use KoreUi\DataTable\Columns\ColorColumn;

function renderColorColumn(ColorColumn $column, mixed $value): string
{
    return view('kore::datatable.columns.color', [
        'column' => $column,
        'value'  => $value,
    ])->render();
}

// C2 — the copy handler interpolates the cell value into an Alpine JS string.
// Blade {{ }} escapes the quote to &#039;, but the browser decodes it back to '
// before Alpine evaluates the attribute, so the string can be broken out of.
// @js()/Js::from() emits a properly escaped JS literal (') that can't.

it('escapes the cell value in the clipboard handler (no JS string break-out)', function () {
    $payload = "'); alert(document.cookie); ('";
    $column = ColorColumn::make('Color', 'hex')->copyable();

    $html = renderColorColumn($column, $payload);

    expect($html)->toContain((string) Js::from($payload));
});

// The swatch background interpolates the value straight into a style attribute,
// allowing extra CSS declarations to be injected (e.g. a full-screen overlay).
it('does not allow injecting extra CSS declarations into the swatch style', function () {
    $payload = 'red; position: fixed; inset: 0; z-index: 9999';
    // showLabel(false) so the only place the value could appear is the style
    // attribute — the label echoes it as harmless (escaped) text otherwise.
    $column = ColorColumn::make('Color', 'hex')->showLabel(false);

    $html = renderColorColumn($column, $payload);

    expect($html)->not->toContain('position: fixed')
        ->and($html)->not->toContain('z-index: 9999')
        ->and($html)->not->toContain('style=');
});

it('still renders a valid hex color in the swatch style', function () {
    $column = ColorColumn::make('Color', 'hex');

    $html = renderColorColumn($column, '#3366ff');

    expect($html)->toContain('background-color: #3366ff');
});

it('still renders a valid functional color in the swatch style', function () {
    $column = ColorColumn::make('Color', 'hex');

    $html = renderColorColumn($column, 'rgb(51, 102, 255)');

    expect($html)->toContain('background-color: rgb(51, 102, 255)');
});

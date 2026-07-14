<?php

use KoreUi\Shell\SidebarState;

// La cookie la escribe JavaScript, así que es input no confiable: cualquiera puede
// editarla desde la consola. Estos tests fijan que nada de lo que salga de aquí
// pueda hacer daño, por muy retorcida que venga.

afterEach(function () {
    unset($_COOKIE[SidebarState::COOKIE]);
});

function withSidebarCookie(string $value): void
{
    $_COOKIE[SidebarState::COOKIE] = $value;
    request()->cookies->set(SidebarState::COOKIE, $value);
}

it('reads the collapsed state of each sidebar', function () {
    withSidebarCookie('{"main":1,"tools":0}');

    expect(SidebarState::all())->toBe(['main' => true, 'tools' => false])
        ->and(SidebarState::collapsed('main'))->toBeTrue()
        ->and(SidebarState::collapsed('tools'))->toBeFalse();
});

it('falls back to the default when the sidebar is not in the cookie', function () {
    withSidebarCookie('{"main":1}');

    expect(SidebarState::collapsed('unknown'))->toBeFalse()
        ->and(SidebarState::collapsed('unknown', default: true))->toBeTrue();
});

it('returns the default when there is no cookie at all', function () {
    expect(SidebarState::all())->toBe([])
        ->and(SidebarState::collapsed('main'))->toBeFalse();
});

it('emits a closed enum for the data attribute, never raw cookie content', function () {
    withSidebarCookie('{"main":1}');

    expect(SidebarState::attribute('main'))->toBe('collapsed')
        ->and(SidebarState::attribute('other'))->toBe('expanded');
});

it('accepts the several shapes a truthy value can arrive in', function () {
    // JSON.stringify escribe 1/0, pero una cookie editada a mano puede traer
    // true/false o "1"/"0". Todas significan lo mismo.
    withSidebarCookie('{"a":1,"b":true,"c":"1","d":0,"e":false,"f":"nonsense"}');

    expect(SidebarState::all())->toBe([
        'a' => true, 'b' => true, 'c' => true,
        'd' => false, 'e' => false, 'f' => false,
    ]);
});

// --- Entrada hostil ---

it('ignores a cookie that is not valid JSON', function () {
    withSidebarCookie('{not json at all');

    expect(SidebarState::all())->toBe([]);
});

it('ignores a JSON list, which says nothing about any sidebar', function () {
    withSidebarCookie('[1,2,3]');

    expect(SidebarState::all())->toBe([]);
});

it('ignores a bare scalar', function () {
    withSidebarCookie('"just a string"');

    expect(SidebarState::all())->toBe([]);
});

it('drops ids that are not plain identifiers', function () {
    // Un id llega al HTML como selector/atributo: solo se admiten identificadores.
    withSidebarCookie('{"main":1,"<script>":1,"a b":1,"ok_id-2":1}');

    expect(SidebarState::all())->toBe(['main' => true, 'ok_id-2' => true]);
});

it('caps the number of entries', function () {
    $payload = [];
    for ($i = 0; $i < 50; $i++) {
        $payload["id{$i}"] = 1;
    }
    withSidebarCookie(json_encode($payload));

    expect(SidebarState::all())->toHaveCount(10);
});

it('ignores an oversized cookie without parsing it', function () {
    withSidebarCookie('{"main":1,"pad":"'.str_repeat('x', 600).'"}');

    expect(SidebarState::all())->toBe([]);
});

// --- Saneado de longitudes CSS ---

it('accepts valid CSS lengths for the width', function () {
    expect(SidebarState::cssLength('16rem', '4rem'))->toBe('16rem')
        ->and(SidebarState::cssLength('280px', '4rem'))->toBe('280px')
        ->and(SidebarState::cssLength('50%', '4rem'))->toBe('50%')
        ->and(SidebarState::cssLength('  20rem  ', '4rem'))->toBe('20rem');
});

it('rejects anything that could smuggle CSS into the style attribute', function () {
    // `width` acaba en un atributo style: sin esta guarda es inyección de CSS.
    expect(SidebarState::cssLength('16rem; background: url(evil)', '4rem'))->toBe('4rem')
        ->and(SidebarState::cssLength('red', '4rem'))->toBe('4rem')
        ->and(SidebarState::cssLength('calc(100% - 2rem)', '4rem'))->toBe('4rem')
        ->and(SidebarState::cssLength('w-64', '4rem'))->toBe('4rem')
        ->and(SidebarState::cssLength(null, '4rem'))->toBe('4rem');
});

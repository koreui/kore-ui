<?php

it('un campo de moneda admite decimales aunque el paso sea entero', function () {
    // `step` es cuánto mueven las flechas —un euro por clic es lo normal— y no
    // tiene nada que ver con cuántos decimales admite el importe. La deducción
    // «paso entero → precisión 0» dejaba TODA moneda sin céntimos con el `step`
    // por defecto, y de paso `_onKeydown` bloqueaba la tecla del separador
    // decimal: no había forma de escribirlos. La documentación promete 2.
    // El jsConfig viaja dentro de un atributo, así que llega escapado.
    $html = html_entity_decode((string) $this->blade('<x-kore::number label="Importe" name="i" mode="currency" currency="EUR" />'));

    expect($html)->toContain('"precision":2');
});

it('un contador decimal con paso entero sigue sin decimales', function () {
    // El caso para el que se hizo la deducción: un contador de unidades.
    $html = html_entity_decode((string) $this->blade('<x-kore::number label="Unidades" name="u" :step="1" />'));

    expect($html)->toContain("['e','E','.',',']");
});

it('un paso fraccionario deja escribir decimales', function () {
    $html = html_entity_decode((string) $this->blade('<x-kore::number label="Peso" name="p" :step="0.5" />'));

    expect($html)->toContain("['e','E']")
        ->and($html)->not->toContain("['e','E','.',',']");
});

it('una precisión explícita manda sobre el paso', function () {
    $html = html_entity_decode((string) $this->blade('<x-kore::number label="Total" name="t" mode="currency" :precision="0" />'));

    expect($html)->toContain('"precision":0');
});

it('restar sobre un campo vacío arranca del mínimo, igual que sumar', function () {
    // Sin `min` declarado, el punto de partida de decrement() era -Infinity: el
    // navegador rechaza ese valor en un <input type="number"> y lo deja vacío,
    // así que pulsar «−» no hacía nada mientras «+» sí daba 0.
    $html = html_entity_decode((string) $this->blade('<x-kore::number label="Cantidad" name="c" />'));

    expect($html)->toContain('let arranque = 0;')
        ->and($html)->toContain('isNaN(val) ? arranque :');
});

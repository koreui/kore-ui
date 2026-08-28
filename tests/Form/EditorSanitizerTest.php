<?php

use KoreUi\Editor\HtmlSanitizer;

/**
 * El sanitizador de servidor.
 *
 * El editor limpia en el navegador, pero eso no es una frontera de seguridad: el
 * valor viaja por `wire:model` y cualquiera puede mandar por ese hilo lo que
 * quiera. Como el texto enriquecido solo se ve enriquecido si se pinta con
 * `{!! !!}`, lo que se guarde sin filtrar es un XSS almacenado.
 */

it('deja pasar lo que el editor produce', function (string $html) {
    expect(HtmlSanitizer::limpiar($html))->toBe($html);
})->with([
    '<p>Hola</p>',
    '<p><strong>Negrita</strong> y <em>cursiva</em></p>',
    '<h2>Título</h2><p>Texto</p>',
    '<ul><li>Uno</li><li>Dos</li></ul>',
    '<ol><li>Primero</li></ol>',
    '<blockquote><p>Citado</p></blockquote>',
    '<p>Con <code>código</code> dentro</p>',
    '<p><a href="https://ejemplo.test">un enlace</a></p>',
]);

it('tira el script entero, no solo su etiqueta', function () {
    // Desenvolver un <script> dejaría su código como texto plano, que es peor:
    // parece inofensivo hasta que alguien lo vuelve a meter en un innerHTML.
    expect(HtmlSanitizer::limpiar('<p>Hola</p><script>alert(1)</script>'))
        ->toBe('<p>Hola</p>')
        ->and(HtmlSanitizer::limpiar('<style>body{display:none}</style><p>x</p>'))
        ->toBe('<p>x</p>');
});

it('desenvuelve lo que no conoce pero conserva el texto', function () {
    // Un `<span style>` pierde el span, no lo que el usuario escribió.
    expect(HtmlSanitizer::limpiar('<p><span style="color:red">rojo</span></p>'))
        ->toBe('<p>rojo</p>')
        ->and(HtmlSanitizer::limpiar('<p><font size="7">grande</font></p>'))
        ->toBe('<p>grande</p>');
});

it('quita cualquier atributo que no esté en la lista', function () {
    expect(HtmlSanitizer::limpiar('<p onclick="alert(1)" style="color:red" class="x">Hola</p>'))
        ->toBe('<p>Hola</p>');
});

it('unifica las etiquetas que el navegador escribe a su manera', function () {
    // `execCommand` produce <b>/<i> en unos motores y <strong>/<em> en otros.
    expect(HtmlSanitizer::limpiar('<b>uno</b> <i>dos</i> <strike>tres</strike>'))
        ->toBe('<strong>uno</strong> <em>dos</em> <s>tres</s>');
});

it('rechaza los esquemas que ejecutan código', function (string $destino) {
    $limpio = HtmlSanitizer::limpiar('<p><a href="' . $destino . '">pinchar</a></p>');

    // El enlace desaparece; su texto se queda.
    expect($limpio)->toBe('<p>pinchar</p>');
})->with([
    'javascript directo' => 'javascript:alert(1)',
    'con mayúsculas' => 'JaVaScRiPt:alert(1)',
    'con espacios delante' => '  javascript:alert(1)',
    // Un navegador ignora los caracteres de control al resolver la URL, así que
    // una comparación ingenua sobre la cadena cruda no basta.
    'con tabulación dentro' => "java\tscript:alert(1)",
    'con salto de línea' => "java\nscript:alert(1)",
    'entidad html' => '&#106;avascript:alert(1)',
    'data con html' => 'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
    'vbscript' => 'vbscript:msgbox(1)',
]);

it('admite los destinos razonables', function (string $destino) {
    expect(HtmlSanitizer::limpiar('<a href="' . $destino . '">x</a>'))
        ->toContain('href="' . $destino . '"');
})->with([
    'https://ejemplo.test/ruta?a=1',
    'http://ejemplo.test',
    'mailto:hola@ejemplo.test',
    'tel:+34600000000',
    '/interna',
    '#ancla',
    'relativa.html',
]);

it('protege la pestaña nueva contra window.opener', function () {
    expect(HtmlSanitizer::limpiar('<a href="https://ejemplo.test" target="_blank">x</a>'))
        ->toContain('rel="noopener noreferrer"');
});

it('conserva los acentos', function () {
    // DOMDocument lee ISO-8859-1 si no se le dice lo contrario y destroza la ñ.
    expect(HtmlSanitizer::limpiar('<p>Año más añejo: ñ, é, ü</p>'))
        ->toBe('<p>Año más añejo: ñ, é, ü</p>');
});

it('escapa lo que parece marcado pero es texto', function () {
    expect(HtmlSanitizer::limpiar('<p>2 &lt; 3 &amp;&amp; 4 &gt; 1</p>'))
        ->toContain('&lt;')
        ->and(HtmlSanitizer::limpiar('<p>2 &lt; 3</p>'))->not->toContain('<3');
});

it('dice si un HTML pasaría entero', function () {
    expect(HtmlSanitizer::esSeguro('<p>Hola</p>'))->toBeTrue()
        ->and(HtmlSanitizer::esSeguro('<p onclick="x()">Hola</p>'))->toBeFalse();
});

it('acepta una lista blanca más corta', function () {
    // Un campo de notas puede querer negrita y nada más.
    expect(HtmlSanitizer::limpiar('<h2>Título</h2><p><strong>x</strong></p>', ['p', 'strong']))
        ->toBe('Título<p><strong>x</strong></p>');
});

it('con la entrada vacía devuelve cadena vacía y no revienta', function () {
    expect(HtmlSanitizer::limpiar(null))->toBe('')
        ->and(HtmlSanitizer::limpiar(''))->toBe('')
        ->and(HtmlSanitizer::limpiar('   '))->toBe('');
});

it('admite imágenes con origen razonable', function (string $origen) {
    $limpio = HtmlSanitizer::limpiar('<p><img src="' . $origen . '" alt="Un gato"></p>');

    expect($limpio)->toContain('src="' . $origen . '"')
        ->and($limpio)->toContain('alt="Un gato"')
        // Una imagen dentro de un texto largo casi nunca está en pantalla al
        // cargar: que el navegador la pida cuando toque.
        ->and($limpio)->toContain('loading="lazy"');
})->with([
    'https://cdn.test/gato.png',
    'http://cdn.test/gato.jpg',
    '/storage/editor/gato.webp',
    'gato.png',
]);

it('rechaza data: en una imagen, aunque ahí parezca lo natural', function () {
    // Un `data:image/svg+xml` es un documento SVG completo, y un SVG puede
    // llevar <script> dentro: abierto en su propia pestaña, se ejecuta.
    $limpio = HtmlSanitizer::limpiar('<p><img src="data:image/svg+xml;base64,PHN2Zz48c2NyaXB0PmFsZXJ0KDEpPC9zY3JpcHQ+PC9zdmc+" alt="Aviso"></p>');

    expect($limpio)->not->toContain('<img')
        ->and($limpio)->not->toContain('data:')
        // El `alt` sobrevive como texto: es lo que el autor quiso decir.
        ->and($limpio)->toContain('Aviso');
});

it('una imagen sin origen utilizable y sin alt desaparece entera', function () {
    expect(HtmlSanitizer::limpiar('<p>antes<img src="javascript:alert(1)">después</p>'))
        ->toBe('<p>antesdespués</p>');
});

it('a la imagen tampoco se le cuelan atributos', function () {
    expect(HtmlSanitizer::limpiar('<img src="/x.png" onerror="alert(1)" onload="alert(2)" style="width:9999px">'))
        ->toBe('<img src="/x.png" loading="lazy">');
});

it('un bloque de código siempre es pre dentro de code', function () {
    // Misma forma venga de donde venga: es la convención de HTML y es lo que
    // produce el parser de markdown.
    expect(HtmlSanitizer::limpiar('<pre>const x = 1;</pre>'))
        ->toBe('<pre><code>const x = 1;</code></pre>')
        ->and(HtmlSanitizer::limpiar('<pre><code>ya lo tiene</code></pre>'))
        ->toBe('<pre><code>ya lo tiene</code></pre>');
});

it('deja pasar la alineación, y solo la nuestra', function () {
    // No es «se permite class»: es «se permite esta lista cerrada de cuatro».
    expect(HtmlSanitizer::limpiar('<p class="kore-editor-centro">x</p>'))
        ->toBe('<p class="kore-editor-centro">x</p>')
        ->and(HtmlSanitizer::limpiar('<p class="lo-que-sea">x</p>'))
        ->toBe('<p>x</p>')
        ->and(HtmlSanitizer::limpiar('<p class="kore-editor-centro lo-que-sea">x</p>'))
        ->toBe('<p class="kore-editor-centro">x</p>');
});

it('la alineación solo vale en un bloque', function () {
    expect(HtmlSanitizer::limpiar('<p><strong class="kore-editor-centro">x</strong></p>'))
        ->toBe('<p><strong>x</strong></p>');
});

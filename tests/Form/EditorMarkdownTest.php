<?php

use KoreUi\Editor\Markdown;

/**
 * El markdown del editor.
 *
 * Lo que cambia respecto a guardar HTML no es el formato: es de quién es el
 * marcado. Guardando HTML, lo almacenado son etiquetas que alguien pintará con
 * `{!! !!}` y todo depende de que el saneado se haya ejecutado por el camino.
 * Guardando markdown, lo almacenado es texto y el HTML lo fabrica este parser
 * con las etiquetas que decide él.
 */

it('convierte lo que el editor sabe escribir', function (string $markdown, string $html) {
    expect(Markdown::aHtml($markdown))->toBe($html);
})->with([
    'párrafo' => ['Hola mundo', '<p>Hola mundo</p>'],
    'título' => ['## Un título', '<h2>Un título</h2>'],
    'subtítulo' => ['### Un subtítulo', '<h3>Un subtítulo</h3>'],
    'negrita' => ['Con **negrita** dentro', '<p>Con <strong>negrita</strong> dentro</p>'],
    'cursiva' => ['Con *cursiva* dentro', '<p>Con <em>cursiva</em> dentro</p>'],
    'tachado' => ['Con ~~tachado~~ dentro', '<p>Con <s>tachado</s> dentro</p>'],
    'código' => ['Con `código` dentro', '<p>Con <code>código</code> dentro</p>'],
    'enlace' => ['Ver [aquí](https://ejemplo.test)', '<p>Ver <a href="https://ejemplo.test">aquí</a></p>'],
    'lista' => ["- uno\n- dos", '<ul><li>uno</li><li>dos</li></ul>'],
    'numerada' => ["1. uno\n2. dos", '<ol><li>uno</li><li>dos</li></ol>'],
    'cita' => ['> citado', '<blockquote><p>citado</p></blockquote>'],
]);

it('junta las líneas del mismo párrafo y separa los párrafos', function () {
    expect(Markdown::aHtml("una\ndos\n\ntres"))
        ->toBe('<p>una<br>dos</p><p>tres</p>');
});

it('cierra la lista cuando llega otra cosa', function () {
    expect(Markdown::aHtml("- uno\n- dos\ntexto suelto"))
        ->toBe('<ul><li>uno</li><li>dos</li></ul><p>texto suelto</p>');
});

it('cambiar de viñetas a números abre otra lista', function () {
    expect(Markdown::aHtml("- uno\n1. dos"))
        ->toBe('<ul><li>uno</li></ul><ol><li>dos</li></ol>');
});

it('el texto del usuario es texto, no marcado', function () {
    // Esta es la razón de ser de todo esto: lo guardado no puede convertirse en
    // etiquetas por su cuenta.
    expect(Markdown::aHtml('<script>alert(1)</script>'))
        ->toBe('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>')
        ->and(Markdown::aHtml('<img src=x onerror=alert(1)>'))
        ->not->toContain('<img');
});

it('rechaza los esquemas peligrosos también aquí', function (string $destino) {
    // Un `javascript:` colado por el markdown sería el mismo agujero por otra
    // puerta: el enlace desaparece y su texto se queda.
    $html = Markdown::aHtml("Pincha [aqui]({$destino})");

    expect($html)->toBe('<p>Pincha aqui</p>');
})->with([
    'javascript:alert(1)',
    'JaVaScRiPt:alert(1)',
    'data:text/html;base64,PHN2Zz4=',
    'vbscript:msgbox(1)',
]);

it('no formatea dentro de un trozo de código', function () {
    // Un asterisco entre comillas invertidas es un asterisco.
    expect(Markdown::aHtml('El operador `2 * 3 * 4` multiplica'))
        ->toBe('<p>El operador <code>2 * 3 * 4</code> multiplica</p>');
});

it('la barra invertida deja el marcador como texto', function () {
    expect(Markdown::aHtml('Un \*asterisco\* literal'))
        ->toBe('<p>Un *asterisco* literal</p>');
});

it('la negrita gana a la cursiva', function () {
    // Al revés, `**x**` se leería como dos cursivas pegadas.
    expect(Markdown::aHtml('**fuerte** y *suave*'))
        ->toBe('<p><strong>fuerte</strong> y <em>suave</em></p>');
});

it('no confunde el guion bajo de un nombre con una cursiva', function () {
    expect(Markdown::aHtml('la variable mi_nombre_largo'))
        ->toBe('<p>la variable mi_nombre_largo</p>');
});

it('un título con más almohadillas de las que hay se queda en la última', function () {
    expect(Markdown::aHtml('###### hondo'))->toBe('<h3>hondo</h3>');
});

it('con la entrada vacía devuelve cadena vacía', function () {
    expect(Markdown::aHtml(null))->toBe('')
        ->and(Markdown::aHtml(''))->toBe('')
        ->and(Markdown::aHtml("  \n  "))->toBe('');
});

it('lo que sale se puede pintar sin sanear', function () {
    // Es la promesa del modo markdown: el HTML lo fabrica el parser, así que ya
    // pasa entero por la lista blanca del sanitizador.
    $html = Markdown::aHtml("## Título\n\nCon **negrita**, [enlace](https://x.test) y `código`.\n\n- uno\n- dos");

    expect(KoreUi\Editor\HtmlSanitizer::limpiar($html))->toBe($html);
});

it('convierte un bloque de código con vallas', function () {
    expect(Markdown::aHtml("```\nconst x = 1;\n```"))
        ->toBe('<pre><code>const x = 1;</code></pre>');
});

it('dentro de un bloque de código no se interpreta nada', function () {
    // Una almohadilla ahí dentro es una almohadilla, no un título.
    expect(Markdown::aHtml("```\n# no es un titulo\n- ni una lista\n**ni negrita**\n```"))
        ->toBe('<pre><code># no es un titulo' . "\n" . '- ni una lista' . "\n" . '**ni negrita**</code></pre>');
});

it('escapa el HTML que haya dentro del código', function () {
    expect(Markdown::aHtml("```\nif (a < b) return '<b>';\n```"))
        ->toContain('&lt;b&gt;')
        ->and(Markdown::aHtml("```\n<script>alert(1)</script>\n```"))
        ->not->toContain('<script>');
});

it('un bloque sin cerrar no se pierde', function () {
    // El texto se acabó antes de la valla de cierre: perderlo sería tirar lo que
    // el usuario escribió.
    expect(Markdown::aHtml("```\nsin cerrar"))->toBe('<pre><code>sin cerrar</code></pre>');
});

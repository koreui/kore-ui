// @vitest-environment jsdom
import { describe, it, expect } from 'vitest';
import { aHtml, aMarkdown } from '../../resources/js/form/editor-markdown.js';

/**
 * Las dos mitades de la traducción tienen que encajar.
 *
 * Si `aMarkdown` escribe algo que `aHtml` no sabe leer, el texto cambia solo al
 * recargar: se escribe una cita, se guarda, se vuelve y es un párrafo. Y si el
 * de PHP lee otra cosa, lo que se ve al editar y lo que se publica dejan de
 * coincidir. Por eso lo que más se prueba aquí es la ida y vuelta.
 */
const desdeHtml = (html) => {
    const molde = document.createElement('div');
    molde.innerHTML = html;

    return aMarkdown(molde);
};

describe('HTML → markdown', () => {
    it.each([
        ['<p>Hola mundo</p>', 'Hola mundo'],
        ['<h2>Un título</h2>', '## Un título'],
        ['<h3>Un subtítulo</h3>', '### Un subtítulo'],
        ['<p>Con <strong>negrita</strong></p>', 'Con **negrita**'],
        ['<p>Con <em>cursiva</em></p>', 'Con *cursiva*'],
        ['<p>Con <s>tachado</s></p>', 'Con ~~tachado~~'],
        ['<p>Con <code>codigo</code></p>', 'Con `codigo`'],
        ['<p><a href="https://x.test">enlace</a></p>', '[enlace](https://x.test)'],
        ['<ul><li>uno</li><li>dos</li></ul>', '- uno\n- dos'],
        ['<ol><li>uno</li><li>dos</li></ol>', '1. uno\n2. dos'],
        ['<blockquote><p>citado</p></blockquote>', '> citado'],
    ])('%s', (html, markdown) => {
        expect(desdeHtml(html)).toBe(markdown);
    });

    it('separa los bloques con una línea en blanco', () => {
        expect(desdeHtml('<h2>T</h2><p>uno</p><p>dos</p>')).toBe('## T\n\nuno\n\ndos');
    });

    it('el subrayado se pierde, pero no su texto', () => {
        // Markdown no tiene subrayado. Inventarse una sintaxis que el servidor
        // no leería sería peor: el texto cambiaría solo al recargar.
        expect(desdeHtml('<p>Con <u>subrayado</u></p>')).toBe('Con subrayado');
    });

    it('escapa lo que se leería como sintaxis al volver', () => {
        expect(desdeHtml('<p>2 * 3 y _guiones_</p>')).toBe('2 \\* 3 y \\_guiones\\_');
        expect(aHtml(desdeHtml('<p>2 * 3 y _guiones_</p>'))).toBe('<p>2 * 3 y _guiones_</p>');
    });

    it('no se inventa marcadores alrededor de nada', () => {
        expect(desdeHtml('<p><strong> </strong></p>')).not.toContain('**');
    });
});

describe('markdown → HTML', () => {
    it('produce lo mismo que el editor pintaría', () => {
        expect(aHtml('## Hola\n\nCon **negrita**\n\n- uno\n- dos'))
            .toBe('<h2>Hola</h2><p>Con <strong>negrita</strong></p><ul><li>uno</li><li>dos</li></ul>');
    });

    it('el texto del usuario no se convierte en etiquetas', () => {
        expect(aHtml('<script>alert(1)</script>')).toBe('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>');
    });

    it('rechaza los esquemas peligrosos', () => {
        expect(aHtml('[x](javascript:alert(1))')).toBe('<p>x</p>');
        expect(aHtml('[x](https://ok.test)')).toBe('<p><a href="https://ok.test">x</a></p>');
    });

    it('no formatea dentro del código', () => {
        expect(aHtml('El `2 * 3` multiplica')).toBe('<p>El <code>2 * 3</code> multiplica</p>');
    });
});

describe('ida y vuelta', () => {
    it.each([
        '<h2>Título</h2><p>Un párrafo con <strong>negrita</strong> y <em>cursiva</em></p>',
        '<ul><li>uno</li><li>dos</li></ul>',
        '<ol><li>primero</li></ol>',
        '<blockquote><p>una cita</p></blockquote>',
        '<p>Con <a href="https://x.test">enlace</a> dentro</p>',
        '<p>Con <code>codigo</code> y <s>tachado</s></p>',
    ])('%s sobrevive al viaje', (html) => {
        expect(aHtml(desdeHtml(html))).toBe(html);
    });
});

describe('imágenes', () => {
    it('convierte la imagen en su sintaxis', () => {
        expect(desdeHtml('<p><img src="/gato.png" alt="Un gato"></p>')).toBe('![Un gato](/gato.png)');
    });

    it('una imagen suelta en la raíz también cuenta', () => {
        // `insertHTML` la deja así cuando el cursor no estaba dentro de un
        // párrafo: sin hijos que recorrer, se perdía al guardar.
        expect(desdeHtml('<p>texto</p><img src="/x.png" alt="X">')).toBe('texto\n\n![X](/x.png)');
    });

    it('sin alt sigue siendo una imagen', () => {
        expect(desdeHtml('<img src="/x.png">')).toBe('![](/x.png)');
    });

    it('vuelve a ser HTML con el mismo aspecto', () => {
        expect(aHtml('![Un gato](/gato.png)'))
            .toBe('<p><img src="/gato.png" alt="Un gato" loading="lazy"></p>');
    });

    it('rechaza data: también en imágenes', () => {
        // Un `data:image/svg+xml` es un documento SVG entero, y un SVG puede
        // llevar <script> dentro.
        expect(aHtml('![Aviso](data:image/svg+xml;base64,PHN2Zz4=)')).toBe('<p>Aviso</p>');
    });

    it('no confunde una imagen con un enlace', () => {
        // `![x](url)` empieza por `!` y luego es igual que un enlace: si los
        // enlaces miran primero, se llevan la parte de dentro y dejan el `!`.
        expect(aHtml('![foto](/x.png)')).not.toContain('!<a');
    });
});

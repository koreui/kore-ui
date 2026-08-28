import { execFileSync } from 'node:child_process';
import { aHtml } from '../resources/js/form/editor-markdown.js';

/**
 * Verifica que el markdown se lee IGUAL en el navegador y en el servidor.
 *
 * `<x-kore::editor markdown>` tiene dos parsers: `editor-markdown.js` pinta lo
 * que el usuario ve mientras edita, y `KoreUi\Editor\Markdown` fabrica el HTML
 * que se publica. Son la misma gramática escrita dos veces, y en cuanto una de
 * las dos se toque sin la otra empiezan a discrepar: se escribe algo, se ve de
 * una manera y se publica de otra. Eso no lo detecta ningún test de los dos
 * lados por separado, porque cada uno pasa con su propia idea de la verdad.
 *
 * Se ejecuta con `npm run markdown:check`.
 */
const CASOS = [
    'Hola mundo',
    '## Un título',
    '### Un subtítulo',
    '###### más hondo de lo que existe',
    'Con **negrita** dentro',
    'Con *cursiva* dentro',
    'Con ~~tachado~~ dentro',
    'Con `código` dentro',
    'El operador `2 * 3 * 4` multiplica',
    'Ver [aquí](https://ejemplo.test)',
    'Ver [aquí](https://ejemplo.test/con(parentesis))',
    'Pincha [aqui](javascript:alert(1))',
    'Pincha [aqui](data:text/html;base64,PHN2Zz4=)',
    '![Un gato](https://cdn.test/gato.png)',
    '![Un gato](/storage/gato.webp)',
    '![Con texto](data:image/svg+xml;base64,PHN2Zz4=)',
    '![](https://cdn.test/sin-alt.png)',
    'Texto con ![una imagen](/x.png) dentro',
    'Un [enlace](https://x.test) y una ![imagen](/y.png)',
    '```\nconst x = 1;\n```',
    '```js\nif (a < b) { return "<b>"; }\n```',
    '```\n# esto no es un titulo\n- ni esto una lista\n```',
    'antes\n\n```\ncodigo\n```\n\ndespues',
    '```\nsin cerrar',
    'la variable mi_nombre_largo',
    'Un \\*asterisco\\* literal',
    '**fuerte** y *suave*',
    '- uno\n- dos',
    '1. uno\n2. dos',
    '- uno\n1. dos',
    '> citado',
    '> dos\n> líneas',
    'una\ndos\n\ntres',
    '- uno\n- dos\ntexto suelto',
    '<script>alert(1)</script>',
    '<img src=x onerror=alert(1)>',
    'Un & y un < sueltos',
    '',
    '   ',
];

const php = JSON.parse(execFileSync('php', ['-r', `
    require 'vendor/autoload.php';
    $casos = json_decode(file_get_contents('php://stdin'), true);
    echo json_encode(array_map(fn ($c) => KoreUi\\Editor\\Markdown::aHtml($c), $casos));
`], { input: JSON.stringify(CASOS), encoding: 'utf8' }).trim().replace(/^[^[]*/, ''));

let fallos = 0;

CASOS.forEach((caso, i) => {
    const enJs = aHtml(caso);
    const enPhp = php[i];

    if (enJs !== enPhp) {
        fallos++;
        console.error(`\n✗ ${JSON.stringify(caso)}`);
        console.error(`  navegador: ${JSON.stringify(enJs)}`);
        console.error(`  servidor : ${JSON.stringify(enPhp)}`);
    }
});

if (fallos > 0) {
    console.error(`\n${fallos} de ${CASOS.length} casos se leen distinto en cada lado.`);
    console.error('Los dos parsers tienen que cubrir la misma gramática: resources/js/form/editor-markdown.js y src/Editor/Markdown.php.');
    process.exit(1);
}

console.log(`✓ los ${CASOS.length} casos se leen igual en el navegador y en el servidor`);

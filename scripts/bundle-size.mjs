import { gzipSync } from 'node:zlib';
import { readFileSync } from 'node:fs';

/**
 * El presupuesto del bundle, en bytes gzip.
 *
 * Existe porque "el JavaScript es poco" es una promesa que esta librería hace en su
 * documentación, y una promesa que nadie mide deja de ser verdad sin que nadie se entere.
 *
 * Subirlo es una DECISIÓN, no un trámite: si este script falla, la pregunta no es "cuánto lo
 * subo" sino "qué acabo de meter en el bundle y por qué". El gráfico entero —tooltip, crosshair
 * y leyenda incluidos— son 1,6 kB de estos.
 */
// Historial de las subidas, para que la próxima también sea una decisión y no un trámite:
//   34 kB → el módulo chart entero (tooltip, crosshair, leyenda): 1,6 kB
//   35 kB → el zoom (brush, pan, slider, suelo de la ventana):    +0,8 kB
//   37 kB → repeater, key-value, transfer y order-list:           +1,3 kB
//            No son código nuevo: estaban en resources/js/ desde la 1.7.0 y nunca llegaron
//            al bundle versionado. El presupuesto medía un dist que no incluía cuatro
//            componentes que la librería ya prometía. Esto es la deuda haciéndose visible.
//   45 kB → el editor de texto enriquecido:                        +5,3 kB
//            Medido quitando su import del bundle: 38,76 kB sin él, 44,02 kB con él. De
//            paso queda registrado que el presupuesto YA estaba excedido en 1,76 kB antes
//            de esto —el guardián solo corre en CI y llevaba tiempo sin mirarse—.
//   41 kB → el editor sale a su propio bundle:                      −4,6 kB
//            Se hizo lo que la línea de arriba dejaba anotado: `dist/kore-ui-editor.js` lo
//            carga el componente cuando aparece en la página, así que quien no lo usa no
//            paga nada. El principal vuelve a su tamaño de antes, con margen otra vez.
const BUDGET = 41_984;   // 41 kB

/**
 * El editor tiene su propio presupuesto.
 *
 * Va aparte, pero no es gratis: quien SÍ lo usa se lo descarga entero. Que esté
 * fuera del bundle principal no es permiso para que crezca sin mirarlo.
 */
const BUDGET_EDITOR = 8_192;   // 8 kB

const kb = (n) => `${(n / 1024).toFixed(2)} kB`;

let fallos = 0;

for (const [file, budget] of [['dist/kore-ui.js', BUDGET], ['dist/kore-ui-editor.js', BUDGET_EDITOR]]) {
    const size = gzipSync(readFileSync(file), { level: 9 }).length;

    if (size > budget) {
        console.error(
            `\n✗ ${file}: ${kb(size)} gzip — se pasa del presupuesto en ${kb(size - budget)}.\n` +
            `  Presupuesto: ${kb(budget)} (scripts/bundle-size.mjs).\n`
        );
        fallos++;

        continue;
    }

    console.log(`✓ ${file}: ${kb(size)} gzip de ${kb(budget)} (queda ${kb(budget - size)})`);
}

if (fallos > 0) process.exit(1);

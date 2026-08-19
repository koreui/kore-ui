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
const BUDGET = 37_888;   // 37 kB

const file = 'dist/kore-ui.js';
const raw = readFileSync(file);
const size = gzipSync(raw, { level: 9 }).length;

const kb = (n) => `${(n / 1024).toFixed(2)} kB`;

if (size > BUDGET) {
    console.error(
        `\n✗ ${file}: ${kb(size)} gzip — se pasa del presupuesto en ${kb(size - BUDGET)}.\n` +
        `  Presupuesto: ${kb(BUDGET)} (scripts/bundle-size.mjs).\n`
    );
    process.exit(1);
}

console.log(`✓ ${file}: ${kb(size)} gzip de ${kb(BUDGET)} (queda ${kb(BUDGET - size)})`);

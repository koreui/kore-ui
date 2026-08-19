import { readFileSync } from 'node:fs';

/**
 * Verifica que el bundle versionado en dist/ registra lo mismo que registran las fuentes.
 *
 * Existe porque dist/kore-ui.js va dentro del paquete: el ServiceProvider lo sirve tal cual
 * en /vendor/kore-ui/kore-ui.js, sin que el proyecto que instala la librería construya nada.
 * Cuando el dist se queda atrás de resources/js/, la Blade del componente sí viaja pero su
 * Alpine.data no, y el componente revienta en el navegador del usuario con un ReferenceError
 * que aquí nadie ve. Pasó en v1.7.0 con repeater, key-value, transfer y order-list.
 *
 * IMPORTANTE: en CI esto tiene que correr ANTES de `npm run build`. Después del build el dist
 * siempre está fresco y la comprobación pasa siempre, que es justo el agujero que la deja ciega.
 */
const ENTRY = 'resources/js/index.js';
const BUNDLE = 'dist/kore-ui.js';

const source = readFileSync(ENTRY, 'utf8');
const bundle = readFileSync(BUNDLE, 'utf8');

// Alpine.data('X', ...) y Alpine.store('X', ...) — lo que el navegador tiene que encontrar.
const registered = [...source.matchAll(/Alpine\.(?:data|store)\(\s*['"]([^'"]+)['"]/g)].map((m) => m[1]);

if (registered.length === 0) {
    console.error(`\n✗ No se encontró ningún Alpine.data/store en ${ENTRY}. ¿Cambió el formato del entry?\n`);
    process.exit(1);
}

// El bundle está minificado: los identificadores locales cambian de nombre, pero la clave con la
// que Alpine registra el componente es un string literal y sobrevive intacta.
const missing = registered.filter((name) => !bundle.includes(`"${name}"`) && !bundle.includes(`'${name}'`));

if (missing.length > 0) {
    console.error(
        `\n✗ ${BUNDLE} está desincronizado de ${ENTRY}.\n` +
        `  Registrados en la fuente pero ausentes del bundle:\n` +
        missing.map((n) => `    - ${n}`).join('\n') +
        `\n\n  El dist se versiona: ejecuta \`npm run build\` y commitea dist/kore-ui.js.\n`
    );
    process.exit(1);
}

console.log(`✓ ${BUNDLE}: los ${registered.length} registros de ${ENTRY} están en el bundle`);

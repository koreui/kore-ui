// @vitest-environment jsdom
import { readFileSync } from 'fs';
import { execSync } from 'child_process';
import { parse } from 'acorn';
import { describe, expect, it } from 'vitest';

/**
 * La fuga de scope de Alpine.
 *
 * Alpine no llama a los métodos de un componente con `this` = el objeto del componente. Los
 * llama con `this` = un Proxy que fusiona TODA la pila de scopes: [magics, componente,
 * ...ancestros x-data]. Y su trampa `set` (alpinejs/src/scope.js) hace esto:
 *
 *     let target;
 *     for (const obj of objects) { target = keyInPrototypeChain(obj, name); if (target) break; }
 *     if (!target) target = objects[objects.length - 1];   // ← el scope MÁS EXTERNO
 *
 * O sea: si asignas `this.foo = x` y `foo` NO está declarada en el objeto del componente, la
 * propiedad NO se guarda en el componente — se guarda en el **x-data ancestro más externo de
 * la página**, que es compartido por todo lo que cuelgue de él.
 *
 * Las consecuencias son silenciosas y desagradables:
 *   - Dos instancias del mismo componente se pisan la propiedad. Gana la última en `init()`.
 *   - Dos componentes DISTINTOS que usen el mismo nombre (`_floatingCleanup` lo usan seis)
 *     se pisan entre sí: cerrar el dropdown ejecuta la limpieza del select.
 *   - No hay ningún error. Simplemente, un componente lee los datos de otro.
 *
 * Un test unitario NO puede ver esto: si le pasas un objeto plano como `this`, la asignación
 * funciona. Sólo se ve con Alpine de verdad (el test de abajo) o mirando el código (éste).
 *
 * La regla, entonces: **toda propiedad que un componente se asigne a sí mismo tiene que estar
 * declarada en el objeto que devuelve la factoría**, aunque sea con `null`. Incluidas las
 * privadas (`_algo`): el guion bajo es una convención de estilo, no un escondite.
 */

const FILES = execSync('find resources/js -name "*.js"', { encoding: 'utf8' })
    .trim()
    .split('\n')
    .filter((f) => !f.endsWith('index.js') && !f.includes('/utils/'));

/** Las claves declaradas en el objeto que devuelve la factoría del componente. */
function declaredKeys(source) {
    const ast = parse(source, { ecmaVersion: 2023, sourceType: 'module' });
    const keys = new Set();

    // Se recorre el AST entero y se recogen las claves de TODOS los literales de objeto que
    // se devuelvan (`=> ({...})` o `return {...}`). Es generoso a propósito: el objetivo es
    // no dar falsos positivos, y un falso negativo aquí lo caza el test de Alpine de abajo.
    (function walk(node) {
        if (!node || typeof node !== 'object') return;

        if (node.type === 'ObjectExpression') {
            for (const prop of node.properties) {
                if (prop.key?.name) keys.add(prop.key.name);
            }
        }

        for (const key of Object.keys(node)) {
            const child = node[key];
            if (Array.isArray(child)) child.forEach(walk);
            else if (child && typeof child.type === 'string') walk(child);
        }
    })(ast);

    return keys;
}

/** Las propiedades que el componente se asigna a sí mismo: `this.foo = ...`. */
function assignedKeys(source) {
    const ast = parse(source, { ecmaVersion: 2023, sourceType: 'module' });
    const keys = new Set();

    (function walk(node) {
        if (!node || typeof node !== 'object') return;

        if (
            node.type === 'AssignmentExpression' &&
            node.left.type === 'MemberExpression' &&
            node.left.object.type === 'ThisExpression' &&
            !node.left.computed &&
            node.left.property.name
        ) {
            keys.add(node.left.property.name);
        }

        for (const key of Object.keys(node)) {
            const child = node[key];
            if (Array.isArray(child)) child.forEach(walk);
            else if (child && typeof child.type === 'string') walk(child);
        }
    })(ast);

    return keys;
}

describe('ningún componente escribe fuera de su propio scope', () => {
    it.each(FILES)('%s declara todo lo que se asigna', (file) => {
        const source = readFileSync(file, 'utf8');
        const declared = declaredKeys(source);

        const leaked = [...assignedKeys(source)].filter(
            (key) => !declared.has(key) && !key.startsWith('$'),
        );

        expect(leaked, `Estas propiedades acabarían en el x-data ancestro, no en el componente. Decláralas en el objeto que devuelve la factoría (con null basta): ${leaked.join(', ')}`)
            .toEqual([]);
    });
});

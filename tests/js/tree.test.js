// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';
import KoreTree from '../../resources/js/ui/tree.js';

/**
 * KoreTree: de dónde saca los nodos y cómo se recorre.
 *
 * Los nodos iban dentro del `x-data`, que Alpine evalúa una sola vez, y el árbol
 * se pinta entero con `x-for` desde el cliente. El morph de Livewire reemplazaba
 * el `<template>` por el del servidor y el `x-for` quedaba MUERTO: medido en
 * navegador, el estado pasaba a nueve filas y el DOM se quedaba en siete, sin
 * reaccionar ni tocando el estado a mano. Ahora la raíz lleva `wire:ignore` y
 * los nodos viajan en un nodo JSON aparte que Livewire sí actualiza.
 *
 * Lo otro que se prueba aquí es el teclado, que no existía: los `treeitem`
 * tenían todos `tabindex="-1"` y el único enfocable de cada fila era el
 * chevrón, así que con `selectable` no había forma de elegir un nodo sin ratón.
 */
const NODOS = [
    {
        key: 'documentos',
        label: 'Documentos',
        children: [
            { key: 'informes', label: 'Informes', children: [{ key: 'anual', label: 'Anual' }] },
            { key: 'contratos', label: 'Contratos' },
        ],
    },
    { key: 'imagenes', label: 'Imágenes' },
];

function montar(nodos = NODOS, config = {}) {
    document.body.innerHTML = '';

    const contenedor = document.createElement('div');
    const nodo = document.createElement('script');
    nodo.type = 'application/json';
    nodo.setAttribute('data-kore-tree-nodes', '');
    nodo.textContent = JSON.stringify(nodos);

    const raiz = document.createElement('div');
    contenedor.append(nodo, raiz);
    document.body.append(contenedor);

    const tree = KoreTree(config);
    tree.$el = raiz;
    tree.$nextTick = (fn) => fn?.();
    tree.$dispatch = vi.fn();
    tree.init();

    return { tree, nodo, contenedor };
}

const tecla = (key) => ({ key, preventDefault: vi.fn() });

describe('los nodos vienen del servidor', () => {
    it('los lee del nodo JSON al iniciar', () => {
        const { tree } = montar();

        expect(tree.nodes).toHaveLength(2);
        expect(tree.flatNodes.map((i) => i.node.key)).toContain('documentos');
    });

    it('los relee cuando el servidor los cambia', () => {
        const { tree, nodo } = montar();
        expect(tree.nodes).toHaveLength(2);

        nodo.textContent = JSON.stringify([...NODOS, { key: 'nuevo', label: 'Carpeta nueva' }]);
        tree._leerNodosDelServidor();

        expect(tree.nodes).toHaveLength(3);
        expect(tree.flatNodes.map((i) => i.node.label)).toContain('Carpeta nueva');
    });

    it('resuelve el nodo JSON en cada lectura, no lo cachea', () => {
        // Al hacer morph, Livewire SUSTITUYE el <script> entero en vez de
        // editarlo: una referencia guardada apuntaría a un nodo desconectado.
        const { tree, nodo, contenedor } = montar();

        nodo.remove();
        const reemplazo = document.createElement('script');
        reemplazo.type = 'application/json';
        reemplazo.setAttribute('data-kore-tree-nodes', '');
        reemplazo.textContent = JSON.stringify([{ key: 'otro', label: 'Otro' }]);
        contenedor.prepend(reemplazo);

        tree._leerNodosDelServidor();

        expect(tree.nodes).toEqual([{ key: 'otro', label: 'Otro' }]);
    });

    it('un JSON roto no deja el árbol sin nodos', () => {
        const { tree, nodo } = montar();

        nodo.textContent = '{ esto no es json';
        tree._leerNodosDelServidor();

        expect(tree.nodes, 'se queda con lo último bueno').toHaveLength(2);
    });

    it('control: sin nodo JSON, el árbol arranca vacío', () => {
        // Es el estado de partida, no un resultado válido: si el test de arriba
        // dejara de leer el JSON, este seguiría pasando y aquel no.
        document.body.innerHTML = '';
        const raiz = document.createElement('div');
        document.body.append(raiz);

        const tree = KoreTree({});
        tree.$el = raiz;
        tree.$nextTick = (fn) => fn?.();
        tree.init();

        expect(tree.nodes).toEqual([]);
    });
});

describe('teclado', () => {
    it('la flecha abajo baja por los nodos VISIBLES', () => {
        const { tree } = montar(NODOS, { expandedKeys: ['documentos'] });
        tree.focusKey = 'documentos';

        tree.onKeydown(tecla('ArrowDown'));

        expect(tree.focusKey).toBe('informes');
    });

    it('se salta lo que está plegado', () => {
        const { tree } = montar();   // nada expandido
        tree.focusKey = 'documentos';

        tree.onKeydown(tecla('ArrowDown'));

        expect(tree.focusKey, 'los hijos de Documentos no se ven').toBe('imagenes');
    });

    it('la flecha derecha abre la rama, y otra vez entra en ella', () => {
        const { tree } = montar();
        tree.focusKey = 'documentos';

        tree.onKeydown(tecla('ArrowRight'));
        expect(tree.isExpanded('documentos')).toBe(true);
        expect(tree.focusKey, 'la primera solo abre').toBe('documentos');

        tree.onKeydown(tecla('ArrowRight'));
        expect(tree.focusKey).toBe('informes');
    });

    it('la flecha izquierda cierra la rama, y otra vez sube al padre', () => {
        const { tree } = montar(NODOS, { expandedKeys: ['documentos'] });
        tree.focusKey = 'informes';

        tree.onKeydown(tecla('ArrowLeft'));
        expect(tree.focusKey, 'un nodo sin abrir sube a su padre').toBe('documentos');

        tree.onKeydown(tecla('ArrowLeft'));
        expect(tree.isExpanded('documentos')).toBe(false);
    });

    it('Home y End van a los extremos visibles', () => {
        const { tree } = montar(NODOS, { expandedKeys: ['documentos'] });
        tree.focusKey = 'informes';

        tree.onKeydown(tecla('End'));
        expect(tree.focusKey).toBe('imagenes');

        tree.onKeydown(tecla('Home'));
        expect(tree.focusKey).toBe('documentos');
    });

    it('Enter selecciona el nodo con el foco', () => {
        const { tree } = montar(NODOS, { selectable: true });
        tree.focusKey = 'imagenes';

        tree.onKeydown(tecla('Enter'));

        expect(tree.isSelected('imagenes')).toBe(true);
    });

    it('una tecla que no es suya se deja pasar', () => {
        const { tree } = montar();
        const e = tecla('a');

        tree.onKeydown(e);

        expect(e.preventDefault).not.toHaveBeenCalled();
    });
});

describe('nombres accesibles', () => {
    it('el chevrón dice de qué rama es y en qué estado está', () => {
        // Antes todos se llamaban «Toggle expand»: un lector oía lo mismo una
        // vez por rama sin saber cuál estaba abriendo.
        const { tree } = montar();
        const item = tree.flatNodes[0];

        expect(tree.etiquetaDeChevron(item)).toBe('Abrir Documentos');

        tree.toggleExpand('documentos');
        expect(tree.etiquetaDeChevron(item)).toBe('Cerrar Documentos');
    });

    it('solo un nodo entra en el tabulador', () => {
        const { tree } = montar(NODOS, { expandedKeys: ['documentos'] });

        const enTabulador = tree.flatNodes.filter((i) => i.visible && tree.esFoco(i.node.key));

        expect(enTabulador).toHaveLength(1);
        expect(enTabulador[0].node.key, 'el primero, mientras nadie tenga el foco').toBe('documentos');
    });
});

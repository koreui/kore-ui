// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';
import KoreSelect from '../../resources/js/form/select.js';

/**
 * KoreSelect: de dónde saca las opciones.
 *
 * Iban dentro del `x-data`, que Alpine evalúa una sola vez. Al cambiar
 * `:options` desde el servidor el atributo se actualizaba en el DOM pero nadie
 * lo volvía a leer, y el panel —teleportado a `body`, fuera del alcance del
 * morph— seguía enseñando la lista de la primera carga. Ahora viajan en un nodo
 * JSON que el plugin vigila.
 */
function nodoOpciones(opciones) {
    const nodo = document.createElement('script');
    nodo.type = 'application/json';
    nodo.setAttribute('data-kore-select-options', '');
    nodo.textContent = JSON.stringify(opciones);
    return nodo;
}

function montar(opciones, { id = 'sel-1' } = {}) {
    document.body.innerHTML = '';

    const contenedor = document.createElement('div');
    const nodo = nodoOpciones(opciones);
    nodo.id = id;
    const raiz = document.createElement('div');

    contenedor.append(nodo, raiz);
    document.body.append(contenedor);

    const select = KoreSelect({ optionsId: id, multiple: false, debounce: 0, minSearch: 0 });
    select.$el = raiz;
    select.$refs = { hiddenInput: null };
    select.$nextTick = (fn) => fn?.();

    return { select, nodo, contenedor };
}

describe('KoreSelect · opciones desde el servidor', () => {
    it('lee las opciones del nodo JSON', () => {
        const { select } = montar([{ value: 'es', label: 'España' }]);
        expect(select.options).toEqual([{ value: 'es', label: 'España' }]);
    });

    it('sigue aceptando opciones en el config (uso directo del plugin)', () => {
        const select = KoreSelect({ options: [{ value: 'a', label: 'A' }] });
        expect(select.options).toHaveLength(1);
    });

    it('devuelve una lista vacía si el JSON está roto, sin lanzar', () => {
        document.body.innerHTML = '<script id="roto" data-kore-select-options>{no es json}</script>';
        vi.spyOn(console, 'error').mockImplementation(() => {});

        const select = KoreSelect({ optionsId: 'roto' });

        expect(select.options).toEqual([]);
    });

    it('se entera cuando el servidor cambia las opciones', async () => {
        const { select, nodo } = montar([{ value: 'es', label: 'España' }]);
        select._vigilarOpciones();

        nodo.textContent = JSON.stringify([
            { value: 'es', label: 'España' },
            { value: 'be', label: 'Bélgica' },
        ]);

        await new Promise((r) => setTimeout(r, 20));

        expect(select.options).toHaveLength(2);
        expect(select.options[1].label).toBe('Bélgica');
    });

    it('se entera aunque el morph sustituya el nodo entero', async () => {
        // Livewire no edita el <script>: lo reemplaza. Un observador colgado del
        // propio nodo se quedaría mirando algo ya desconectado, así que se vigila
        // el contenedor y se vuelve a resolver el nodo en cada aviso.
        const { select, nodo, contenedor } = montar([{ value: 'es', label: 'España' }]);
        select._vigilarOpciones();

        const nuevo = nodoOpciones([{ value: 'pt', label: 'Portugal' }, { value: 'it', label: 'Italia' }]);
        contenedor.replaceChild(nuevo, nodo);

        await new Promise((r) => setTimeout(r, 20));

        expect(select.options.map((o) => o.label)).toEqual(['Portugal', 'Italia']);
    });

    it('no vigila nada en modo async: ahí las opciones las trae el fetch', () => {
        const { select } = montar([{ value: 'es', label: 'España' }], { id: 'sel-async' });

        const asincrono = KoreSelect({ optionsId: 'sel-async', async: '/buscar' });
        asincrono.$el = select.$el;
        asincrono._vigilarOpciones();

        expect(asincrono._observadorOpciones).toBeNull();
    });
});

describe('KoreSelect · teclado', () => {
    function conOpciones() {
        const select = KoreSelect({ options: [{ value: 'a', label: 'A' }, { value: 'b', label: 'B' }] });
        select.$refs = { trigger: { focus: vi.fn() }, dropdown: null, hiddenInput: null };
        select.$nextTick = (fn) => fn?.();
        select.openDropdown = function () { this.open = true; };
        return select;
    }

    it('Enter abre el desplegable cuando está cerrado', () => {
        // Antes caía en el `case` de seleccionar, hacía preventDefault() y no
        // hacía nada más: el disparador es un <button>, así que sin ese
        // preventDefault el navegador habría disparado el click por su cuenta.
        // Desde el teclado solo se podía abrir con las flechas.
        const select = conOpciones();
        const evento = { key: 'Enter', preventDefault: vi.fn() };

        select.onKeydown(evento);

        expect(select.open).toBe(true);
    });

    it('Enter elige la opción resaltada cuando ya está abierto', () => {
        const select = conOpciones();
        select.open = true;
        select.highlighted = 1;
        select.select = vi.fn();

        select.onKeydown({ key: 'Enter', preventDefault: vi.fn() });

        expect(select.select).toHaveBeenCalledWith({ value: 'b', label: 'B' });
    });

    it('la flecha abajo sigue abriéndolo', () => {
        const select = conOpciones();

        select.onKeydown({ key: 'ArrowDown', preventDefault: vi.fn() });

        expect(select.open).toBe(true);
    });
});

import { beforeEach, describe, expect, it, vi } from 'vitest';

// Vitest corre en environment 'node', así que no hay DOM. El módulo solo toca
// document/window DENTRO de sus funciones (nunca al importarse), lo que permite
// stubearlos aquí y probar la lógica de conteo de dueños, que es lo que importa.
function stubDom(scrollY = 0) {
    globalThis.document = { body: { style: {} }, addEventListener: vi.fn() };
    globalThis.window = { scrollY, scrollTo: vi.fn() };
}

let lockScroll, unlockScroll, releaseScrollLock, isScrollLocked;

beforeEach(async () => {
    stubDom(250);
    vi.resetModules(); // el módulo tiene estado a nivel de módulo (owners, savedScrollY)
    ({ lockScroll, unlockScroll, releaseScrollLock, isScrollLocked } = await import('../../resources/js/utils/scroll-lock.js'));
});

describe('lock y unlock', () => {
    it('fija el body y recuerda el scroll', () => {
        lockScroll('modal');

        expect(isScrollLocked()).toBe(true);
        expect(document.body.style.position).toBe('fixed');
        expect(document.body.style.top).toBe('-250px');
    });

    it('restaura el scroll al soltar', () => {
        lockScroll('modal');
        unlockScroll('modal');

        expect(isScrollLocked()).toBe(false);
        expect(document.body.style.position).toBe('');
        expect(window.scrollTo).toHaveBeenCalledWith(0, 250);
    });

    it('ignora un unlock de quien no tenía el lock', () => {
        lockScroll('modal');
        unlockScroll('alguien-mas');

        expect(isScrollLocked()).toBe(true);
        expect(document.body.style.position).toBe('fixed');
    });

    it('tomar el lock dos veces desde el mismo dueño no lo duplica', () => {
        lockScroll('modal');
        lockScroll('modal');
        unlockScroll('modal');

        expect(isScrollLocked()).toBe(false);
    });
});

describe('varios dueños', () => {
    it('no suelta el body mientras quede alguien que lo necesita', () => {
        // El caso real: el drawer del sidebar abierto y encima un modal. Si el
        // primero en cerrarse soltara el body, la página de detrás scrollearía
        // con el modal todavía abierto.
        lockScroll('sidebar');
        lockScroll('modal');

        unlockScroll('modal');

        expect(isScrollLocked()).toBe(true);
        expect(document.body.style.position).toBe('fixed');
        expect(window.scrollTo).not.toHaveBeenCalled();

        unlockScroll('sidebar');

        expect(isScrollLocked()).toBe(false);
        expect(window.scrollTo).toHaveBeenCalledWith(0, 250);
    });

    it('guarda el scroll del primero que llega, no del último', () => {
        lockScroll('sidebar');
        window.scrollY = 999; // el body ya está fijo: esto no es scroll real del usuario
        lockScroll('modal');

        unlockScroll('modal');
        unlockScroll('sidebar');

        expect(window.scrollTo).toHaveBeenCalledWith(0, 250);
    });
});

describe('releaseScrollLock', () => {
    it('suelta a todos SIN restaurar el scroll', () => {
        // Es lo que hace falta tras wire:navigate: el body que llega es nuevo y el
        // scroll guardado pertenece a la página anterior, así que restaurarlo
        // saltaría a una posición arbitraria de la página nueva.
        lockScroll('sidebar');
        lockScroll('modal');

        releaseScrollLock();

        expect(isScrollLocked()).toBe(false);
        expect(document.body.style.position).toBe('');
        expect(window.scrollTo).not.toHaveBeenCalled();
    });
});

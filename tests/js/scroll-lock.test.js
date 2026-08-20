import { beforeEach, describe, expect, it, vi } from 'vitest';

// Vitest corre en environment 'node', así que no hay DOM. El módulo solo toca
// document/window DENTRO de sus funciones (nunca al importarse), lo que permite
// stubearlos aquí y probar la lógica de conteo de dueños, que es lo que importa.
function stubDom(scrollY = 0) {
    globalThis.document = { body: { style: {} }, addEventListener: vi.fn() };
    globalThis.window = { scrollY, scrollTo: vi.fn() };
}

let lockScroll, unlockScroll, releaseScrollLock, isScrollLocked, hayDuenoPorEncima;

beforeEach(async () => {
    stubDom(250);
    vi.resetModules(); // el módulo tiene estado a nivel de módulo (owners, savedScrollY)
    ({ lockScroll, unlockScroll, releaseScrollLock, isScrollLocked, hayDuenoPorEncima } = await import('../../resources/js/utils/scroll-lock.js'));
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

describe('la clave del dueño', () => {
    // Esta suite estaba entera escrita con cadenas, así que pasaba en verde
    // mientras el overlay manager pasaba `this` —un proxy de Alpine distinto en
    // cada evaluación— y dejaba el body fijo para siempre.

    it('rechaza un objeto en lugar de aceptarlo en silencio', () => {
        // El patrón roto, tal cual estaba: dos objetos EQUIVALENTES pero
        // distintos. Con el Set original el lock quedaba huérfano sin avisar.
        expect(() => lockScroll({ soy: 'un componente Alpine' })).toThrow(TypeError);
        expect(() => unlockScroll({ soy: 'un componente Alpine' })).toThrow(TypeError);
        expect(isScrollLocked()).toBe(false);
    });

    it('acepta símbolos, que también se comparan por identidad estable', () => {
        const clave = Symbol('overlay');

        lockScroll(clave);
        expect(isScrollLocked()).toBe(true);

        unlockScroll(clave);
        expect(isScrollLocked()).toBe(false);
    });

    it('control: dos objetos equivalentes NO son el mismo dueño', () => {
        // Si esto dejara de ser cierto, el test de arriba ya no probaría nada.
        expect(new Set([{ a: 1 }, { a: 1 }]).size).toBe(2);
    });
});

describe('quién está por encima de quién', () => {
    // El `Set` conserva el orden de inserción, así que la lista de dueños es
    // también el orden de las capas. Lo usa el drawer del sidebar para decidir
    // si un Escape es suyo o de un modal abierto encima: sin esto, los dos
    // escuchaban en `window` y una sola pulsación cerraba las dos cosas.

    it('el último en tomar el lock no tiene a nadie encima', () => {
        lockScroll('sidebar:main');
        lockScroll('overlay');

        expect(hayDuenoPorEncima('overlay')).toBe(false);
        expect(hayDuenoPorEncima('sidebar:main')).toBe(true);
    });

    it('el único dueño tampoco', () => {
        lockScroll('sidebar:main');

        expect(hayDuenoPorEncima('sidebar:main')).toBe(false);
    });

    it('quien no tiene el lock cede ante quien sí', () => {
        // El caso del drawer con `overlay: false`: no bloquea el scroll, así que
        // no está en la lista. Si algo tiene el body tomado, está por encima.
        lockScroll('overlay');

        expect(hayDuenoPorEncima('sidebar:main')).toBe(true);
    });

    it('sin nadie con el lock, no hay capa por encima', () => {
        expect(hayDuenoPorEncima('sidebar:main')).toBe(false);
    });

    it('al soltar la capa de arriba, la de abajo pasa a mandar', () => {
        lockScroll('sidebar:main');
        lockScroll('overlay');
        expect(hayDuenoPorEncima('sidebar:main')).toBe(true);

        unlockScroll('overlay');

        expect(hayDuenoPorEncima('sidebar:main')).toBe(false);
    });

    it('exige una clave estable, igual que lock y unlock', () => {
        expect(() => hayDuenoPorEncima({ soy: 'un componente' })).toThrow(TypeError);
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

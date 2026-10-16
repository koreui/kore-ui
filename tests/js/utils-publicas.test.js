// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';

// `window.KoreUi.utils` es API pública: kore-ui/calendar la consume desde su
// propio bundle. Este test fija la forma, para que renombrar una función de
// utils/ no rompa a los paquetes dependientes sin que nadie se entere.

beforeEach(() => {
    delete window.KoreUi;
    vi.resetModules();
});

describe('window.KoreUi.utils', () => {
    it('existe nada más evaluar el bundle, antes de alpine:init', async () => {
        await import('../../resources/js/index.js');

        expect(window.KoreUi?.utils).toBeDefined();
    });

    it('expone floating, focusTrap y scrollLock con sus funciones', async () => {
        await import('../../resources/js/index.js');
        const { floating, focusTrap, scrollLock } = window.KoreUi.utils;

        expect(Object.keys(floating).sort()).toEqual(['startFloating', 'stopFloating', 'virtualReference']);
        expect(Object.keys(focusTrap)).toEqual(['createFocusTrap']);
        expect(Object.keys(scrollLock).sort()).toEqual(['isScrollLocked', 'lockScroll', 'unlockScroll']);

        for (const grupo of [floating, focusTrap, scrollLock]) {
            for (const fn of Object.values(grupo)) expect(typeof fn).toBe('function');
        }
    });

    it('no se puede modificar desde fuera', async () => {
        await import('../../resources/js/index.js');

        expect(Object.isFrozen(window.KoreUi.utils)).toBe(true);
        expect(Object.isFrozen(window.KoreUi.utils.floating)).toBe(true);
    });

    it('conserva lo que otro bundle ya hubiera puesto en window.KoreUi', async () => {
        window.KoreUi = { calendar: { version: 'x' } };

        await import('../../resources/js/index.js');

        expect(window.KoreUi.calendar).toEqual({ version: 'x' });
        expect(window.KoreUi.utils).toBeDefined();
    });
});

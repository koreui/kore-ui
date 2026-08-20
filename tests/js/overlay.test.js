// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';
import KoreOverlay from '../../resources/js/overlay.js';
import { isScrollLocked, releaseScrollLock } from '../../resources/js/utils/scroll-lock.js';

/**
 * KoreOverlay: el manager de overlays.
 *
 * Lo que se prueba aquí es lo que no se ve en una captura: qué clases sale
 * pidiendo cada tipo de overlay y quién tiene el scroll del body. Los dos
 * defectos que motivaron el archivo —el `text-center` que se heredaba a todo el
 * contenido y el lock que no se soltaba nunca— pasaban desapercibidos por vías
 * distintas: el primero porque la captura «se veía bien» si el modal solo tenía
 * un párrafo, y el segundo porque el síntoma solo aparece DESPUÉS de cerrar.
 */
function montar() {
    const overlay = KoreOverlay();

    // Lo mínimo que Alpine y Livewire aportan y el componente usa.
    overlay.$el = document.createElement('div');
    overlay.$nextTick = (fn) => fn?.();
    overlay.$wire = {
        get: () => ({}),
        resetState: vi.fn(),
        destroyOverlay: vi.fn(),
    };

    return overlay;
}

beforeEach(() => {
    releaseScrollLock();
    document.body.innerHTML = '';
    document.body.style.position = '';
    document.body.style.top = '';
    // jsdom no implementa scrollTo y avisa por cada llamada; el lock lo usa al
    // restaurar la posición y aquí solo estorba.
    window.scrollTo = vi.fn();
});

describe('clases de posición', () => {
    it('no impone la alineación del texto a lo que haya dentro', () => {
        // El `text-center` centraba el panel, sí, pero se heredaba a las
        // etiquetas de un formulario, a los párrafos y a las celdas de tabla que
        // pintara el consumidor. El centrado horizontal lo da `justify-center`.
        const clases = montar().getPositionClasses('modal', 'center');

        expect(clases).toContain('justify-center');
        expect(clases).not.toContain('text-center');
    });

    it('lo mismo para el confirm, que pide su centrado en su propia vista', () => {
        expect(montar().getPositionClasses('confirm', 'center')).not.toContain('text-center');
    });

    it('el drawer se pega al lado que le toca', () => {
        const overlay = montar();

        expect(overlay.getPositionClasses('drawer', 'left')).toContain('justify-start');
        expect(overlay.getPositionClasses('drawer', 'right')).toContain('justify-end');
    });

    it('el bottom-sheet se pega abajo y el fullscreen se estira', () => {
        const overlay = montar();

        expect(overlay.getPositionClasses('bottom-sheet', 'center')).toContain('items-end');
        expect(overlay.getPositionClasses('fullscreen', 'center')).toContain('items-stretch');
    });
});

describe('scroll del body', () => {
    it('lo toma al abrir y lo SUELTA al cerrar', () => {
        // El fallo: se pasaba `this` como dueño del lock, y cada expresión de
        // Alpine evalúa sobre un proxy nuevo del componente, así que el objeto
        // que llegaba al unlock nunca era el del lock. El body se quedaba en
        // `position: fixed` para el resto de la visita.
        vi.useFakeTimers();
        const overlay = montar();

        overlay.lockScroll();
        expect(isScrollLocked()).toBe(true);
        expect(document.body.style.position).toBe('fixed');

        overlay.unlockScroll();
        expect(isScrollLocked()).toBe(false);
        expect(document.body.style.position).toBe('');

        vi.useRealTimers();
    });

    it('lo suelta aunque el lock y el unlock lleguen por instancias distintas', () => {
        // Que es exactamente lo que pasa en el navegador: `activate()` corre con
        // el `this` del listener de Livewire y `toggle(false)` con el de la
        // expresión `x-on:keydown.escape`.
        montar().lockScroll();
        expect(isScrollLocked()).toBe(true);

        montar().unlockScroll();
        expect(isScrollLocked()).toBe(false);
    });

    it('control: dos instancias del manager NO son el mismo objeto', () => {
        // Si esto dejara de ser cierto, el test de arriba ya no probaría nada.
        expect(montar()).not.toBe(montar());
    });

    it('cerrar el overlay libera el body al terminar la animación', () => {
        vi.useFakeTimers();
        const overlay = montar();

        overlay.toggle(true);
        expect(isScrollLocked()).toBe(true);

        overlay.toggle(false);
        // Todavía no: la animación de salida dura 300 ms.
        expect(isScrollLocked()).toBe(true);

        vi.advanceTimersByTime(350);
        expect(isScrollLocked()).toBe(false);
        expect(document.body.style.position).toBe('');
        expect(overlay.$wire.resetState).toHaveBeenCalled();

        vi.useRealTimers();
    });
});

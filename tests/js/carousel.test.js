// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';
import KoreCarousel from '../../resources/js/ui/carousel.js';

/**
 * KoreCarousel: lo que el morph se llevaba, y lo que se enfocaba sin querer.
 *
 * El carrusel escribe el ancho de cada diapositiva y la posición del carril como
 * estilo EN LÍNEA, y nada de eso existe en el HTML que emite el servidor: el
 * morph de Livewire los borraba y las diapositivas se encogían al ancho de su
 * contenido. Medido en un navegador: de 768 px a unos 50, y la siguiente
 * pulsación de «siguiente» dejaba la vista en blanco.
 */

const ANCHO = 600;

function montar(config = {}, cuantas = 4) {
    document.body.innerHTML = `
        <div id="raiz">
            <div id="viewport">
                <div id="track">
                    ${Array.from({ length: cuantas }, (_, i) =>
                        `<div data-carousel-slide><button data-b="${i}">B${i}</button></div>`).join('')}
                </div>
            </div>
        </div>`;

    const viewport = document.getElementById('viewport');
    // jsdom no hace layout: `offsetWidth` es siempre 0 si no se le dice otra cosa.
    Object.defineProperty(viewport, 'offsetWidth', { value: ANCHO, configurable: true });

    const c = KoreCarousel(config);
    c.$refs = { viewport, track: document.getElementById('track') };
    c.$el = document.getElementById('raiz');
    c.$nextTick = (fn) => fn();

    global.ResizeObserver = class { observe() {} disconnect() {} };

    c.init();
    return c;
}

const anchos = () => [...document.querySelectorAll('[data-carousel-slide]')].map((s) => s.style.width);

describe('KoreCarousel · el morph', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        vi.useRealTimers();
    });

    it('dimensiona las diapositivas al montar', () => {
        const c = montar();
        expect(c.totalSlides).toBe(4);
        expect(anchos()).toEqual([`${ANCHO}px`, `${ANCHO}px`, `${ANCHO}px`, `${ANCHO}px`]);
        expect(c.$refs.track.style.transform).toBe('translateX(0px)');
    });

    it('se da cuenta de que le han borrado los estilos', () => {
        const c = montar();

        // Justo lo que hace el morph: dejar los nodos con el `style` del
        // servidor, que no tiene nada.
        document.querySelectorAll('[data-carousel-slide]').forEach((s) => s.removeAttribute('style'));

        expect(c._necesitaRemontaje()).toBe(true);
        c._montar();
        expect(anchos()).toEqual([`${ANCHO}px`, `${ANCHO}px`, `${ANCHO}px`, `${ANCHO}px`]);
    });

    it('cuenta las diapositivas que llegan después', () => {
        const c = montar({}, 3);
        expect(c.totalSlides).toBe(3);
        expect(c.totalPages).toBe(3);

        const nueva = document.createElement('div');
        nueva.setAttribute('data-carousel-slide', '');
        document.getElementById('track').appendChild(nueva);

        expect(c._necesitaRemontaje()).toBe(true);
        c._montar();
        expect(c.totalSlides).toBe(4);
        expect(c.totalPages).toBe(4);
    });

    /** Si el servidor se lleva diapositivas, el carril no puede quedarse en un hueco. */
    it('recorta el índice cuando quedan menos diapositivas', () => {
        const c = montar({ loop: false }, 4);
        c.goTo(3);
        expect(c.currentIndex).toBe(3);

        document.querySelectorAll('[data-carousel-slide]').forEach((s, i) => i >= 2 && s.remove());
        c._montar();

        expect(c.totalSlides).toBe(2);
        expect(c.currentIndex).toBe(1);
    });

    /** Reaplicar los estilos no puede volver a disparar el remontaje. */
    it('no se remonta en bucle', () => {
        const c = montar();
        expect(c._necesitaRemontaje()).toBe(false);
        c._montar();
        expect(c._necesitaRemontaje()).toBe(false);
    });
});

describe('KoreCarousel · lo que hay dentro de una diapositiva', () => {
    beforeEach(() => { document.body.innerHTML = ''; });

    /**
     * El `.prevent` del atributo se tragaba el `pointerdown` de todo lo que
     * hubiera dentro, el foco incluido: medido en un navegador, al pulsar un
     * botón dentro de una diapositiva el foco se quedaba en `<body>`.
     */
    it('no secuestra el gesto que empieza sobre un control', () => {
        const c = montar();
        const boton = document.querySelector('button[data-b="0"]');

        const ev = new MouseEvent('pointerdown', { bubbles: true, cancelable: true });
        Object.defineProperty(ev, 'target', { value: boton });

        c.onPointerDown(ev);

        expect(ev.defaultPrevented).toBe(false);
        expect(c._isDragging).toBe(false);
    });

    it('sí lo secuestra fuera de un control, o el arrastre selecciona el texto', () => {
        const c = montar();
        const slide = document.querySelector('[data-carousel-slide]');

        const ev = new MouseEvent('pointerdown', { bubbles: true, cancelable: true, clientX: 10 });
        Object.defineProperty(ev, 'target', { value: slide });

        c.onPointerDown(ev);

        expect(ev.defaultPrevented).toBe(true);
        expect(c._isDragging).toBe(true);
    });

    /**
     * Un `overflow-hidden` recorta las diapositivas de fuera, pero no las saca
     * del tabulador: el foco se iba a un botón que nadie veía, y la página no
     * desplazaba a ninguna parte porque el carril se mueve con `transform`.
     */
    it('deja inertes las diapositivas fuera de la ventana', () => {
        const c = montar({ loop: false });
        const slides = [...document.querySelectorAll('[data-carousel-slide]')];

        expect(slides.map((s) => s.inert)).toEqual([false, true, true, true]);
        expect(slides.map((s) => s.getAttribute('aria-hidden'))).toEqual(['false', 'true', 'true', 'true']);

        c.next();
        expect(slides.map((s) => s.inert)).toEqual([true, false, true, true]);
    });

    it('con varias visibles, deja pasar todas las de la ventana', () => {
        montar({ numVisible: 2, loop: false });
        expect([...document.querySelectorAll('[data-carousel-slide]')].map((s) => s.inert))
            .toEqual([false, false, true, true]);
    });

    /**
     * Una diapositiva que sale de vista con el foco dentro NO se marca inerte:
     * sacarla del árbol de accesibilidad mandaría el foco a `<body>` de golpe.
     *
     * Y esa excepción tiene que valer también para la comprobación de
     * remontaje. Sin ella el componente leía «falta reponer el inert», remontaba,
     * el remontaje escribía estilos, los estilos disparaban el observador y el
     * ciclo no paraba nunca: un bucle infinito con solo enfocar un botón y pasar
     * de diapositiva.
     */
    it('no se marca inerte la diapositiva que tiene el foco, ni pide remontarse por ello', () => {
        const c = montar({ loop: false });
        const primera = document.querySelectorAll('[data-carousel-slide]')[0];

        document.querySelector('button[data-b="0"]').focus();
        expect(primera.contains(document.activeElement)).toBe(true);

        c.next();

        expect(primera.inert, 'sigue participando mientras tenga el foco').toBe(false);
        expect(c._necesitaRemontaje(), 'y eso no cuenta como estilo perdido').toBe(false);

        // En cuanto el foco sale, vuelve a su sitio al siguiente movimiento.
        document.querySelector('button[data-b="1"]').focus();
        c._actualizarVisibilidad();
        expect(primera.inert).toBe(true);
    });
});

describe('KoreCarousel · autoplay y teclado', () => {
    beforeEach(() => { document.body.innerHTML = ''; });

    /** WCAG 2.2.2: hace falta poder pararlo, y pararlo tiene que ganar al hover. */
    it('la pausa a mano manda sobre la del puntero', () => {
        vi.useFakeTimers();
        const c = montar({ autoplay: true, interval: 100, loop: true });

        c.toggleParado();
        expect(c.parado).toBe(true);

        // Sacar el ratón del carrusel llamaba a resume(), que rearrancaba lo que
        // el usuario acababa de parar.
        c.resume();
        vi.advanceTimersByTime(500);
        expect(c.currentIndex).toBe(0);

        c.toggleParado();
        vi.advanceTimersByTime(150);
        expect(c.currentIndex).toBe(1);
        vi.useRealTimers();
    });

    it('avanza y retrocede con las flechas', () => {
        const c = montar({ loop: false });
        const slide = document.querySelector('[data-carousel-slide]');
        c.onKeydown({ key: 'ArrowRight', target: slide, preventDefault() {} });
        expect(c.currentIndex).toBe(1);

        c.onKeydown({ key: 'ArrowLeft', target: slide, preventDefault() {} });
        expect(c.currentIndex).toBe(0);
    });

    /** Dentro de un campo la flecha mueve el cursor: robársela lo deja inservible. */
    it('no toca las flechas que el usuario escribe', () => {
        const c = montar({ loop: false });
        document.querySelector('[data-carousel-slide]').innerHTML = '<input type="text">';
        const campo = document.querySelector('input');

        c.onKeydown({ key: 'ArrowRight', target: campo, preventDefault() { throw new Error('no debería'); } });
        expect(c.currentIndex).toBe(0);
    });
});

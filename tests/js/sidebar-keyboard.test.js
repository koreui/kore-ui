// @vitest-environment jsdom

import { beforeEach, describe, expect, it, vi } from 'vitest';
import KoreSidebar from '../../resources/js/ui/sidebar.js';

// Estos tests corren con DOM real (jsdom) porque lo que se prueba —foco, closest(),
// sub-menús ocultos— NO se puede simular con stubs sin acabar probando los stubs.
// Es la parte que no cubren los tests de Blade, que solo miran el markup.

// Floating UI toca APIs de layout que jsdom no implementa: se sustituye por un doble
// que solo registra si se pidió posicionar algo. Lo que importa aquí es el flujo, no
// las coordenadas.
const floatingCalls = [];
let cleanupsRun = 0;

vi.mock('../../resources/js/utils/floating.js', () => ({
    startFloating: (reference, floating, opts) => {
        floatingCalls.push({ reference, floating, opts });

        // El cleanup real cancela el autoUpdate de Floating UI (listeners de scroll y
        // resize). Aquí solo se cuenta, para comprobar que nadie se queda sin soltar.
        return () => { cleanupsRun += 1; };
    },
    stopFloating: (cleanup) => {
        if (typeof cleanup === 'function') cleanup();
    },
}));

// «Seguridad» tiene sus propios hijos: el caso del sub-menú DENTRO de otro sub-menú.
const SIDEBAR_HTML = `
<nav data-kore-sidebar-id="main" data-kore-sidebar="expanded" data-placement="left">
    <ul>
        <li><a href="/a"><span class="kore-sidebar-label">Panel</span></a></li>
        <li><a href="/b"><span class="kore-sidebar-label">Informes</span></a></li>
        <li id="ajustes" data-kore-has-children data-kore-open="false">
            <button aria-expanded="false"><span class="kore-sidebar-label">Ajustes</span></button>
            <div class="kore-sidebar-submenu" id="panel-ajustes">
                <div>
                    <ul>
                        <li><a href="/c"><span class="kore-sidebar-label">Perfil</span></a></li>
                        <li id="seguridad" data-kore-has-children data-kore-open="false">
                            <button aria-expanded="false"><span class="kore-sidebar-label">Seguridad</span></button>
                            <div class="kore-sidebar-submenu" id="panel-seguridad">
                                <div>
                                    <ul>
                                        <li><a href="/d"><span class="kore-sidebar-label">Contraseña</span></a></li>
                                    </ul>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </li>
        <li><a href="/e"><span class="kore-sidebar-label">Salir</span></a></li>
    </ul>
    <div class="kore-sidebar-tooltip"></div>
</nav>`;

let component, nav, store;

function makeStore({ collapsed = false, mobile = false } = {}) {
    return {
        isCollapsed: () => collapsed,
        isMobile: () => mobile,
        isOpen: () => false,
        closeMobile: vi.fn(),
        closeMobileOnEscape: vi.fn(),
        unwatchViewport: vi.fn(),
    };
}

function mount({ collapsed = false, mobile = false, hoverExpand = false } = {}) {
    document.body.innerHTML = SIDEBAR_HTML;
    nav = document.querySelector('nav');

    if (collapsed) nav.setAttribute('data-kore-sidebar', 'collapsed');
    if (hoverExpand) nav.setAttribute('data-hover-expand', 'true');

    // Sin window.Alpine, la factory se salta el register() y no hace falta simularlo.
    component = KoreSidebar({ id: 'main', placement: 'left' });

    store = makeStore({ collapsed, mobile });

    component.$el = nav;
    component.$store = { koreSidebar: store };
    component.$refs = { tooltip: nav.querySelector('.kore-sidebar-tooltip') };

    return component;
}

function keydown(key) {
    const event = { key, preventDefault: vi.fn() };
    component.onKeydown(event);

    return event;
}

function labels(items) {
    return items.map((el) => el.textContent.trim());
}

beforeEach(() => {
    floatingCalls.length = 0;
    cleanupsRun = 0;
});

describe('items enfocables', () => {
    it('ignora los que viven en un sub-menú cerrado', () => {
        // Siguen en el DOM: sin este filtro, las flechas saltarían a items invisibles.
        mount();

        expect(labels(component.focusableItems())).toEqual(['Panel', 'Informes', 'Ajustes', 'Salir']);
    });

    it('los incluye en cuanto el sub-menú se abre', () => {
        mount();
        nav.querySelector('[data-kore-has-children]').setAttribute('data-kore-open', 'true');

        expect(labels(component.focusableItems())).toEqual([
            'Panel', 'Informes', 'Ajustes', 'Perfil', 'Seguridad', 'Salir',
        ]);
    });

    it('con el sidebar en iconos, el sub-menú abierto tampoco cuenta', () => {
        // En modo iconos los sub-menús no se despliegan en línea: salen en flyout.
        mount({ collapsed: true });
        nav.querySelector('[data-kore-has-children]').setAttribute('data-kore-open', 'true');

        expect(labels(component.focusableItems())).toEqual(['Panel', 'Informes', 'Ajustes', 'Salir']);
    });
});

describe('navegación con flechas', () => {
    it('baja y sube por los items', () => {
        mount();
        const items = component.focusableItems();

        items[0].focus();
        keydown('ArrowDown');
        expect(document.activeElement.textContent.trim()).toBe('Informes');

        keydown('ArrowUp');
        expect(document.activeElement.textContent.trim()).toBe('Panel');
    });

    it('no se sale por los extremos', () => {
        mount();
        const items = component.focusableItems();

        items[0].focus();
        keydown('ArrowUp');
        expect(document.activeElement.textContent.trim()).toBe('Panel');

        items[items.length - 1].focus();
        keydown('ArrowDown');
        expect(document.activeElement.textContent.trim()).toBe('Salir');
    });

    it('Home y End van a los extremos', () => {
        mount();
        component.focusableItems()[1].focus();

        keydown('End');
        expect(document.activeElement.textContent.trim()).toBe('Salir');

        keydown('Home');
        expect(document.activeElement.textContent.trim()).toBe('Panel');
    });

    it('no secuestra las flechas si el foco no está en un item', () => {
        mount();
        document.body.focus();

        const event = keydown('ArrowDown');

        expect(event.preventDefault).not.toHaveBeenCalled();
    });

    it('ignora las teclas que no le incumben', () => {
        mount();
        component.focusableItems()[0].focus();

        const event = keydown('a');

        expect(event.preventDefault).not.toHaveBeenCalled();
    });
});

describe('flechas sobre un sub-menú', () => {
    it('la derecha abre, y una segunda pulsación entra dentro', () => {
        mount();
        const parent = nav.querySelector('[data-kore-has-children]');
        parent.querySelector('button').focus();

        keydown('ArrowRight');
        expect(parent.getAttribute('data-kore-open')).toBe('true');
        expect(parent.querySelector('button').getAttribute('aria-expanded')).toBe('true');

        keydown('ArrowRight');
        expect(document.activeElement.textContent.trim()).toBe('Perfil');
    });

    it('la izquierda cierra', () => {
        mount();
        const parent = nav.querySelector('[data-kore-has-children]');
        parent.setAttribute('data-kore-open', 'true');
        parent.querySelector('button').focus();

        keydown('ArrowLeft');

        expect(parent.getAttribute('data-kore-open')).toBe('false');
        expect(parent.querySelector('button').getAttribute('aria-expanded')).toBe('false');
    });

    it('desde un sub-item, la izquierda sube al padre', () => {
        mount();
        const parent = nav.querySelector('[data-kore-has-children]');
        parent.setAttribute('data-kore-open', 'true');
        nav.querySelector('a[href="/c"]').focus();

        keydown('ArrowLeft');

        expect(document.activeElement.tagName).toBe('BUTTON');
        expect(document.activeElement.textContent.trim()).toBe('Ajustes');
    });

    it('en un sidebar a la derecha, las flechas se invierten', () => {
        // El sentido "hacia dentro" depende de qué lado esté el sidebar.
        mount();
        component.placement = 'right';

        const parent = nav.querySelector('[data-kore-has-children]');
        parent.querySelector('button').focus();

        keydown('ArrowLeft');
        expect(parent.getAttribute('data-kore-open')).toBe('true');

        keydown('ArrowRight');
        expect(parent.getAttribute('data-kore-open')).toBe('false');
    });
});

describe('flyout y tooltip con el sidebar en iconos', () => {
    it('no hace nada mientras el sidebar está expandido', () => {
        mount({ collapsed: false });

        component.onItemEnter({ currentTarget: nav.querySelector('[data-kore-has-children]') });

        expect(floatingCalls).toHaveLength(0);
    });

    it('saca el sub-menú en un flyout, sin clonar el DOM', () => {
        mount({ collapsed: true });

        const parent = nav.querySelector('[data-kore-has-children]');
        const submenu = parent.querySelector('.kore-sidebar-submenu');

        component.onItemEnter({ currentTarget: parent });

        expect(submenu.getAttribute('data-flyout')).toBe('true');
        expect(floatingCalls[0].floating).toBe(submenu); // el MISMO nodo, no una copia
        expect(floatingCalls[0].opts.placement).toBe('right-start');
    });

    it('muestra el label en un tooltip cuando el item no tiene sub-items', () => {
        mount({ collapsed: true });

        const item = nav.querySelector('li'); // "Panel", sin hijos

        component.onItemEnter({ currentTarget: item });

        expect(component.$refs.tooltip.textContent).toBe('Panel');
        expect(component.$refs.tooltip.getAttribute('data-show')).toBe('true');
    });

    it('limpia los estilos inline al cerrar el flyout', () => {
        // startFloating deja position:fixed + left/top en el style. Si no se borran, al
        // expandir el sidebar el sub-menú seguiría flotando en mitad de la pantalla.
        vi.useFakeTimers();
        mount({ collapsed: true });

        const parent = nav.querySelector('[data-kore-has-children]');
        const submenu = parent.querySelector('.kore-sidebar-submenu');

        component.onItemEnter({ currentTarget: parent });
        submenu.style.position = 'fixed';
        submenu.style.left = '100px';

        component.onItemLeave({});
        vi.advanceTimersByTime(300);

        expect(submenu.hasAttribute('data-flyout')).toBe(false);
        expect(submenu.style.position).toBe('');
        expect(submenu.style.left).toBe('');
        vi.useRealTimers();
    });

    it('aguanta abierto mientras cruzas el hueco hasta el panel', () => {
        // El panel se dibuja separado del icono, y ese hueco no es de ninguno de los dos:
        // al cruzarlo salta el mouseleave. Sin el retardo, el menú se cerraba justo cuando
        // ibas a usarlo. Volver a entrar (el panel es descendiente del item) lo cancela.
        vi.useFakeTimers();
        mount({ collapsed: true });

        const parent = nav.querySelector('[data-kore-has-children]');
        const submenu = parent.querySelector('.kore-sidebar-submenu');

        component.onItemEnter({ currentTarget: parent });

        component.onItemLeave({});               // el cursor entra en el hueco
        vi.advanceTimersByTime(100);             // aún no ha vencido el plazo
        expect(submenu.getAttribute('data-flyout')).toBe('true');

        component.onItemEnter({ currentTarget: parent });  // llega al panel
        vi.advanceTimersByTime(1000);

        expect(submenu.getAttribute('data-flyout')).toBe('true');
        vi.useRealTimers();
    });

    it('acaba cerrándose si el cursor se va de verdad', () => {
        vi.useFakeTimers();
        mount({ collapsed: true });

        const parent = nav.querySelector('[data-kore-has-children]');
        const submenu = parent.querySelector('.kore-sidebar-submenu');

        component.onItemEnter({ currentTarget: parent });
        component.onItemLeave({});
        vi.advanceTimersByTime(500);

        expect(submenu.hasAttribute('data-flyout')).toBe(false);
        vi.useRealTimers();
    });

    it('no cierra el flyout si el foco solo salta a otro hijo del mismo item', () => {
        // focusout salta también entre hermanos: sin esta guarda, el flyout se cerraría
        // bajo los pies de quien lo está recorriendo con el teclado.
        mount({ collapsed: true });

        const parent = nav.querySelector('[data-kore-has-children]');
        const submenu = parent.querySelector('.kore-sidebar-submenu');

        component.onItemEnter({ currentTarget: parent });

        component.onItemLeave({
            currentTarget: parent,
            relatedTarget: nav.querySelector('a[href="/d"]'), // sigue dentro del item
        });

        expect(submenu.getAttribute('data-flyout')).toBe('true');
    });

    it('rail no usa flyout: ya se ensancha solo al pasar el cursor', () => {
        mount({ collapsed: true, hoverExpand: true });

        component.onItemEnter({ currentTarget: nav.querySelector('[data-kore-has-children]') });

        expect(floatingCalls).toHaveLength(0);
    });

    it('en móvil tampoco, que ahí el sidebar es un drawer a pantalla completa', () => {
        mount({ collapsed: true, mobile: true });

        component.onItemEnter({ currentTarget: nav.querySelector('[data-kore-has-children]') });

        expect(floatingCalls).toHaveLength(0);
    });
});

describe('flyouts anidados', () => {
    it('abre el sub-menú del hijo SIN cerrar el panel del padre', () => {
        // Regresión: solo se soportaba un flyout, así que entrar en «Seguridad» —que vive
        // DENTRO del panel de «Ajustes»— cerraba ese panel, o sea el suelo que pisabas.
        mount({ collapsed: true });

        const ajustes = nav.querySelector('#ajustes');
        const seguridad = nav.querySelector('#seguridad');
        const panelAjustes = nav.querySelector('#panel-ajustes');
        const panelSeguridad = nav.querySelector('#panel-seguridad');

        component.onItemEnter({ currentTarget: ajustes });
        expect(panelAjustes.getAttribute('data-flyout')).toBe('true');

        component.onItemEnter({ currentTarget: seguridad });

        expect(panelSeguridad.getAttribute('data-flyout')).toBe('true');
        expect(panelAjustes.getAttribute('data-flyout')).toBe('true'); // el padre AGUANTA
    });

    it('cierra la rama del hijo sin tocar la del padre', () => {
        vi.useFakeTimers();
        mount({ collapsed: true });

        const ajustes = nav.querySelector('#ajustes');
        const seguridad = nav.querySelector('#seguridad');
        const panelAjustes = nav.querySelector('#panel-ajustes');
        const panelSeguridad = nav.querySelector('#panel-seguridad');

        component.onItemEnter({ currentTarget: ajustes });
        component.onItemEnter({ currentTarget: seguridad });

        // El cursor sale de «Seguridad» y vuelve al panel de «Ajustes».
        component.onItemLeave({ currentTarget: seguridad });
        vi.advanceTimersByTime(500);

        expect(panelSeguridad.hasAttribute('data-flyout')).toBe(false);
        expect(panelAjustes.getAttribute('data-flyout')).toBe('true');
        vi.useRealTimers();
    });

    it('salir del padre se lleva también los flyouts anidados', () => {
        vi.useFakeTimers();
        mount({ collapsed: true });

        const ajustes = nav.querySelector('#ajustes');
        const seguridad = nav.querySelector('#seguridad');
        const panelAjustes = nav.querySelector('#panel-ajustes');
        const panelSeguridad = nav.querySelector('#panel-seguridad');

        component.onItemEnter({ currentTarget: ajustes });
        component.onItemEnter({ currentTarget: seguridad });

        component.onItemLeave({ currentTarget: ajustes });
        vi.advanceTimersByTime(500);

        expect(panelAjustes.hasAttribute('data-flyout')).toBe(false);
        expect(panelSeguridad.hasAttribute('data-flyout')).toBe(false);
        vi.useRealTimers();
    });

    it('pasar a una rama hermana cierra la anterior', () => {
        mount({ collapsed: true });

        const ajustes = nav.querySelector('#ajustes');
        const panelAjustes = nav.querySelector('#panel-ajustes');
        const panel = nav.querySelector('li'); // «Panel», sin hijos

        component.onItemEnter({ currentTarget: ajustes });
        expect(panelAjustes.getAttribute('data-flyout')).toBe('true');

        component.onItemEnter({ currentTarget: panel });

        expect(panelAjustes.hasAttribute('data-flyout')).toBe(false);
    });
});

describe('el tooltip no se queda colgado', () => {
    it('se apaga al abrir un flyout', () => {
        // Regresión: hay UN solo tooltip compartido. Al ir de «Mensajes» (tooltip) a
        // «Ajustes» (flyout), nadie lo apagaba y el panel se abría con el nombre del item
        // anterior todavía flotando al lado.
        mount({ collapsed: true });

        const mensajes = nav.querySelector('li');           // sin hijos → tooltip
        const ajustes = nav.querySelector('#ajustes');      // con hijos → flyout
        const tooltip = component.$refs.tooltip;

        component.onItemEnter({ currentTarget: mensajes });
        expect(tooltip.getAttribute('data-show')).toBe('true');
        expect(tooltip.textContent).toBe('Panel');

        component.onItemEnter({ currentTarget: ajustes });

        expect(tooltip.hasAttribute('data-show')).toBe(false);
        expect(nav.querySelector('#panel-ajustes').getAttribute('data-flyout')).toBe('true');
    });

    it('libera el posicionador anterior al saltar de un item a otro', () => {
        // startFloating deja corriendo un autoUpdate (listeners de scroll/resize). Sin
        // soltarlo, se acumula uno por cada icono sobre el que pases el ratón.
        mount({ collapsed: true });

        const items = nav.querySelectorAll('li');

        component.onItemEnter({ currentTarget: items[0] });
        component.onItemEnter({ currentTarget: items[1] });

        expect(component.$refs.tooltip.textContent).toBe('Informes');
        expect(floatingCalls).toHaveLength(2);   // dos posicionamientos...
        expect(cleanupsRun).toBe(1);             // ...y el primero ya liberado
    });
});

describe('Escape', () => {
    // Una capa por pulsación. Antes, un solo Escape cerraba el flyout Y el
    // drawer de golpe; ahora el flyout consume la tecla —marcándola, para que
    // el overlay manager tampoco cierre nada— y el drawer espera su turno.

    it('con un flyout abierto, cierra el flyout y nada más', () => {
        mount({ collapsed: true });

        const parent = nav.querySelector('[data-kore-has-children]');
        const submenu = parent.querySelector('.kore-sidebar-submenu');

        component.onItemEnter({ currentTarget: parent });
        const evento = keydown('Escape');

        expect(submenu.hasAttribute('data-flyout')).toBe(false);
        expect(evento.preventDefault, 'la tecla queda marcada como consumida').toHaveBeenCalled();
        expect(store.closeMobileOnEscape, 'el drawer no se toca todavía').not.toHaveBeenCalled();
    });

    it('sin flyout abierto, el Escape pasa al drawer', () => {
        mount({ collapsed: true });

        const evento = keydown('Escape');

        // Quien decide si la tecla es suya es el store: puede haber un modal
        // por encima que la reclame antes.
        expect(store.closeMobileOnEscape).toHaveBeenCalledWith('main', evento);
    });
});

// @vitest-environment jsdom
import { beforeEach, describe, expect, it, vi } from 'vitest';
import KoreTab from '../../resources/js/ui/tab.js';
import KoreStepper from '../../resources/js/ui/stepper.js';

/**
 * Quién decide la selección inicial de `tab` y `stepper`.
 *
 * Los dos la resolvían en un `$nextTick` dentro de `init()`, y ahí la lista de
 * items todavía está vacía: cuando el padre inicializa, los hijos aún no se han
 * registrado. La condición no se cumplía, nadie volvía a intentarlo, y el
 * resultado era un `<x-kore::tab>` con las pestañas pintadas y NINGÚN panel
 * debajo —o un stepper con los tres círculos apagados— hasta que el usuario
 * pulsaba. Ahora se decide al registrar cada item.
 */
function montarTab(config = {}) {
    const tab = KoreTab(config);
    tab.$nextTick = (fn) => fn?.();
    tab.$el = document.createElement('div');
    tab.$refs = {};

    return tab;
}

function montarStepper(config = {}) {
    const stepper = KoreStepper(config);
    stepper.$nextTick = (fn) => fn?.();
    stepper.$el = document.createElement('div');
    stepper.$refs = {};

    return stepper;
}

describe('KoreTab · selección inicial', () => {
    it('selecciona la primera pestaña en cuanto se registra', () => {
        const tab = montarTab({});
        expect(tab.selected).toBe(null);

        tab.registerTab({ id: 'a', disabled: false });

        expect(tab.selected).toBe('a');
    });

    it('salta las pestañas deshabilitadas', () => {
        const tab = montarTab({});

        tab.registerTab({ id: 'a', disabled: true });
        expect(tab.selected, 'una pestaña bloqueada no puede ser la inicial').toBe(null);

        tab.registerTab({ id: 'b', disabled: false });
        expect(tab.selected).toBe('b');
    });

    it('respeta la que venga declarada', () => {
        const tab = montarTab({ selected: 'b' });

        tab.registerTab({ id: 'a', disabled: false });
        tab.registerTab({ id: 'b', disabled: false });

        expect(tab.selected).toBe('b');
    });

    it('no se pisa a sí misma al registrar la segunda', () => {
        const tab = montarTab({});

        tab.registerTab({ id: 'a', disabled: false });
        tab.registerTab({ id: 'b', disabled: false });

        expect(tab.selected).toBe('a');
    });

    it('registrar dos veces el mismo id no lo duplica', () => {
        const tab = montarTab({});

        tab.registerTab({ id: 'a', disabled: false });
        tab.registerTab({ id: 'a', disabled: false });

        expect(tab.tabs).toHaveLength(1);
    });

    it('control: sin ninguna pestaña registrada no hay nada que seleccionar', () => {
        // Es el estado en el que se quedaba el componente entero: es válido
        // como punto de partida, no como resultado final.
        expect(montarTab({}).selected).toBe(null);
    });
});

describe('KoreTab · teclado', () => {
    // El teclado ya estaba implementado, pero `onKeydown` sale pronto si no hay
    // ninguna pestaña seleccionada: sin la selección inicial, las flechas no
    // hacían nada tampoco.
    function conTres() {
        const tab = montarTab({});
        tab.registerTab({ id: 'a', disabled: false });
        tab.registerTab({ id: 'b', disabled: false });
        tab.registerTab({ id: 'c', disabled: true });

        return tab;
    }

    const tecla = (key) => ({ key, preventDefault: vi.fn() });

    it('la flecha avanza a la siguiente habilitada', () => {
        const tab = conTres();

        tab.onKeydown(tecla('ArrowRight'));

        expect(tab.selected).toBe('b');
    });

    it('da la vuelta saltándose la deshabilitada', () => {
        const tab = conTres();
        tab.selected = 'b';

        tab.onKeydown(tecla('ArrowRight'));

        expect(tab.selected, 'la tercera está bloqueada: vuelve a la primera').toBe('a');
    });

    it('Home y End van a los extremos habilitados', () => {
        const tab = conTres();
        tab.selected = 'b';

        tab.onKeydown(tecla('Home'));
        expect(tab.selected).toBe('a');

        tab.onKeydown(tecla('End'));
        expect(tab.selected).toBe('b');
    });
});

describe('KoreStepper · paso inicial', () => {
    it('activa el primer paso en cuanto se registra', () => {
        const stepper = montarStepper({});

        stepper.registerStep({ id: 'uno' });

        expect(stepper.selected).toBe('uno');
        expect(stepper.getStepStatus('uno')).toBe('active');
    });

    it('los siguientes quedan pendientes, no activos', () => {
        const stepper = montarStepper({});

        stepper.registerStep({ id: 'uno' });
        stepper.registerStep({ id: 'dos' });

        expect(stepper.selected).toBe('uno');
        expect(stepper.getStepStatus('dos')).toBe('pending');
    });

    it('avanzar deja el anterior como completado', () => {
        const stepper = montarStepper({});
        stepper.registerStep({ id: 'uno' });
        stepper.registerStep({ id: 'dos' });

        stepper.next();

        expect(stepper.getStepStatus('uno')).toBe('complete');
        expect(stepper.getStepStatus('dos')).toBe('active');
    });

    it('respeta el paso que venga declarado', () => {
        const stepper = montarStepper({ selected: 'dos' });

        stepper.registerStep({ id: 'uno' });
        stepper.registerStep({ id: 'dos' });

        expect(stepper.selected).toBe('dos');
    });
});

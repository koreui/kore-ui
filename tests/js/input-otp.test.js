import { beforeEach, describe, expect, it, vi } from 'vitest';
import KoreInputOtp from '../../resources/js/form/input-otp.js';

/**
 * KoreInputOtp: el puente de vuelta desde el servidor.
 *
 * Era el único componente de formulario sin `$wire.$watch`: la sincronización
 * iba solo de cliente a servidor. El caso normal de un OTP —código incorrecto,
 * `$this->reset('codigo')`, vuelve a intentarlo— cambiaba la propiedad en el
 * servidor y dejaba los seis dígitos escritos en pantalla.
 */
function montar(largo = 6, { modelo = 'codigo', valorInicial = '' } = {}) {
    const casillas = Array.from({ length: largo }, () => ({ value: '', focus: vi.fn(), select: vi.fn() }));

    const oculto = {
        value: valorInicial,
        getAttribute: (nombre) => (nombre === 'wire:model.live' ? modelo : null),
        dispatchEvent: vi.fn(),
    };

    let observador = null;

    const otp = KoreInputOtp({ length: largo, numeric: false });
    otp.$refs = { hiddenInput: oculto };
    casillas.forEach((c, i) => { otp.$refs[`digit${i}`] = c; });
    otp.$wire = {
        $watch: (prop, fn) => { observador = { prop, fn }; },
    };

    otp.init();

    return { otp, casillas, oculto, servidorEscribe: (v) => observador?.fn(v), observador: () => observador };
}

describe('KoreInputOtp (form/input-otp.js)', () => {
    it('se suscribe a la propiedad del wire:model', () => {
        const { observador } = montar();
        expect(observador()?.prop).toBe('codigo');
    });

    it('reparte el valor inicial entre las casillas', () => {
        const { otp } = montar(4, { valorInicial: '1234' });
        expect(otp.digits.join('')).toBe('1234');
    });

    it('vacía las casillas cuando el servidor vacía la propiedad', () => {
        const { otp, casillas, servidorEscribe } = montar(4, { valorInicial: '1234' });
        casillas.forEach((c, i) => { c.value = otp.digits[i]; });

        servidorEscribe('');

        expect(otp.digits.join('')).toBe('');
        expect(casillas.map((c) => c.value).join('')).toBe('');
    });

    it('pinta un valor nuevo que venga del servidor', () => {
        const { otp, casillas, servidorEscribe } = montar(4);

        servidorEscribe('9876');

        expect(otp.digits.join('')).toBe('9876');
        expect(casillas.map((c) => c.value).join('')).toBe('9876');
    });

    it('no toca nada si el servidor manda lo que ya hay', () => {
        // Sin esta comprobación, cada respuesta del servidor repintaría las
        // casillas y se llevaría por delante la posición del cursor.
        const { otp, casillas, servidorEscribe } = montar(4, { valorInicial: '1234' });
        casillas.forEach((c, i) => { c.value = otp.digits[i]; c.value = otp.digits[i]; });
        const antes = casillas.map((c) => c.value).join('');

        servidorEscribe('1234');

        expect(casillas.map((c) => c.value).join('')).toBe(antes);
    });

    it('trata null como cadena vacía', () => {
        const { otp, servidorEscribe } = montar(4, { valorInicial: '1234' });

        servidorEscribe(null);

        expect(otp.digits.join('')).toBe('');
    });

    it('no revienta sin $wire', () => {
        const otp = KoreInputOtp({ length: 4, numeric: false });
        otp.$refs = { hiddenInput: { value: '', getAttribute: () => null, dispatchEvent: vi.fn() } };

        expect(() => otp.init()).not.toThrow();
    });
});

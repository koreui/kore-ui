import { describe, expect, it } from 'vitest';
import KorePassword from '../../resources/js/form/password.js';

// KorePassword(config) -> { value, rules, level, levelLabel, ... }.
// Los textos venían escritos aquí dentro, en inglés: eran los únicos de la
// librería que no se podían traducir sin recompilar el bundle. Ahora llegan del
// Blade, que los saca de `kore-ui.form.translations`.
const TEXTOS = {
    niveles: ['Weak', 'Fair', 'Good', 'Strong'],
    reglas: {
        length: 'At least 8 characters',
        uppercase: 'One uppercase letter',
        lowercase: 'One lowercase letter',
        number: 'One number',
        special: 'One special character',
    },
};

const etiquetas = (p) => p.rules.map(r => r.label);

describe('KorePassword (form/password.js)', () => {
    it('usa los textos que le llega del Blade', () => {
        const p = KorePassword({ minLength: 8, textos: TEXTOS });

        expect(etiquetas(p)).toEqual(Object.values(TEXTOS.reglas));

        p.value = 'Abcdefg1!';
        expect(p.levelLabel).toBe('Strong');
    });

    it('cae en español cuando no le llega nada', () => {
        const p = KorePassword();

        expect(etiquetas(p)).toContain('Una letra mayúscula');

        p.value = 'abc';
        expect(p.levelLabel).toBe('Débil');
    });

    it('interpola el mínimo en la regla de longitud', () => {
        expect(etiquetas(KorePassword({ minLength: 12 }))[0]).toBe('Al menos 12 caracteres');
        expect(etiquetas(KorePassword())[0]).toBe('Al menos 8 caracteres');
    });

    it('el mínimo que llega del Blade ya viene interpolado y se respeta', () => {
        const p = KorePassword({ minLength: 12, textos: { reglas: { length: 'Al menos 12 caracteres' } } });

        expect(etiquetas(p)[0]).toBe('Al menos 12 caracteres');
        // Y las que no vienen en el mapa siguen teniendo su texto.
        expect(etiquetas(p)[1]).toBe('Una letra mayúscula');
    });

    it('mide el nivel por reglas cumplidas, no por longitud', () => {
        const p = KorePassword({ minLength: 8 });

        p.value = '';
        expect(p.level).toBe(0);
        expect(p.levelLabel).toBe('');

        p.value = 'abcdefgh';          // longitud + minúscula
        expect(p.level).toBe(2);
        expect(p.levelLabel).toBe('Regular');

        p.value = 'Abcdefg1';          // + mayúscula + número
        expect(p.level).toBe(4);
        expect(p.levelLabel).toBe('Fuerte');
    });

    it('respeta el mínimo al evaluar la regla de longitud', () => {
        const p = KorePassword({ minLength: 12 });

        p.value = 'abcdefgh';
        expect(p.rules.find(r => r.id === 'length').passed).toBe(false);

        p.value = 'abcdefghijkl';
        expect(p.rules.find(r => r.id === 'length').passed).toBe(true);
    });
});

<?php

use KoreUi\Charts\Palette;

describe('el color viaja como token, no como valor', function () {
    it('devuelve una referencia CSS, no un color', function () {
        // Ésta es la razón de que el gráfico se repinte solo al cambiar de tema, sin JS.
        expect(Palette::token(1))->toBe('var(--kore-chart-1)');
        expect(Palette::token(8))->toBe('var(--kore-chart-8)');
    });

    it('acepta un token semántico si el usuario lo pide a propósito', function () {
        // Una serie que literalmente significa "errores" sí debería ir en rojo.
        expect(Palette::resolve(1, 'destructive'))->toBe('var(--kore-destructive)');
    });

    it('deja pasar un color literal del usuario', function () {
        expect(Palette::resolve(1, 'oklch(0.5 0.2 30)'))->toBe('oklch(0.5 0.2 30)');
    });

    it('usa el slot cuando no hay color explícito', function () {
        expect(Palette::resolve(3))->toBe('var(--kore-chart-3)');
        expect(Palette::resolve(3, ''))->toBe('var(--kore-chart-3)');
    });
});

describe('la paleta no se cicla', function () {
    it('asigna el slot por orden de registro de la marca', function () {
        // El color sigue a la ENTIDAD, no a su posición entre las visibles. Si se asignara
        // por índice de serie visible, ocultar la serie 2 repintaría la 3 con el color de
        // la 2 y el lector creería estar mirando otra cosa.
        expect(Palette::slotFor(1))->toBe(1);
        expect(Palette::slotFor(5))->toBe(5);
    });

    it('se niega a pintar una novena serie en vez de repetir un color', function () {
        // Repetir el color de la serie 1 en la novena es peor que no pintarla: el lector
        // deja de poder distinguirlas y el gráfico miente.
        expect(fn () => Palette::slotFor(9))->toThrow(InvalidArgumentException::class, 'Otros');
        expect(fn () => Palette::token(9))->toThrow(InvalidArgumentException::class);
        expect(fn () => Palette::token(0))->toThrow(InvalidArgumentException::class);
    });
});

describe('scatter: solo aguanta 5 series', function () {
    it('usa el subconjunto que sobrevive al daltonismo', function () {
        // En barras y líneas solo se tocan los vecinos. En un scatter, CUALQUIER par puede
        // quedar uno al lado del otro, así que hay que distinguir los ocho entre sí — y ahí
        // magenta y teal colapsan al mismo color bajo deuteranopia (ΔE 2.4, medido).
        expect(Palette::slotFor(1, scatter: true))->toBe(1);   // azul
        expect(Palette::slotFor(4, scatter: true))->toBe(6);   // rojo, no naranja
        expect(Palette::slotFor(5, scatter: true))->toBe(8);   // verde
    });

    it('se niega a pintar una sexta serie en un scatter', function () {
        expect(fn () => Palette::slotFor(6, scatter: true))
            ->toThrow(InvalidArgumentException::class, 'deuteranopia');
    });
});

<?php

use App\Support\Money;

describe('Money::from', function () {
    test('normaliza montos a centavos enteros', function (int|float|string $entrada, int $centavos) {
        expect(Money::from($entrada)->centavos())->toBe($centavos);
    })->with([
        'entero' => [500, 50000],
        'float con decimal' => [350.5, 35050],
        'string' => ['500.00', 50000],
        'string con 1 decimal' => ['350.5', 35050],
        'string con 3 decimales trunca' => ['1.005', 100], // trunca a 2, no redondea
        'cero' => [0, 0],
        'negativo' => [-50.25, -5025],
    ]);

    test('toDecimal formatea siempre con 2 decimales', function () {
        expect(Money::from(500)->toDecimal())->toBe('500.00')
            ->and(Money::from('350.5')->toDecimal())->toBe('350.50')
            ->and(Money::from('-1.25')->toDecimal())->toBe('-1.25')
            ->and(Money::fromCents(7)->toDecimal())->toBe('0.07');
    });
});

describe('operaciones aritméticas exactas', function () {
    test('suma exacta sin imprecisión de float', function () {
        expect(Money::from('0.1')->sumar('0.2')->toDecimal())->toBe('0.30');
    });

    test('resta y encadenamiento', function () {
        $r = Money::from('3000.00')->restar('500.00')->restar('300.00');

        expect($r->toDecimal())->toBe('2200.00');
    });

    test('multiplicarPor redondea al centavo (round-half-up)', function () {
        expect(Money::from('350.00')->multiplicarPor(0.4)->toDecimal())->toBe('140.00')
            ->and(Money::from('350.35')->multiplicarPor(0.5 * 0.75)->toDecimal())->toBe('131.38');
    });

    test('porcentajeDe calcula centavos × pct / 100 con redondeo', function () {
        expect(Money::from('175.00')->porcentajeDe(10)->toDecimal())->toBe('17.50')
            ->and(Money::from('350.35')->porcentajeDe(33)->toDecimal())->toBe('115.62');
    });

    test('esNegativo y esCero', function () {
        expect(Money::from('-1.00')->esNegativo())->toBeTrue()
            ->and(Money::fromCents(0)->esCero())->toBeTrue()
            ->and(Money::from('0.00')->esNegativo())->toBeFalse();
    });
});

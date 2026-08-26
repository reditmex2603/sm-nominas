<?php

use App\Services\FuzzyMatcher;

describe('FuzzyMatcher::normalizar', function () {
    test('convierte a minúsculas y recorta espacios', function () {
        expect(FuzzyMatcher::normalizar('  FESTIVAL Rock  '))->toBe('festival rock');
    });

    test('elimina acentos y diéresis', function (string $entrada, string $esperado) {
        expect(FuzzyMatcher::normalizar($entrada))->toBe($esperado);
    })->with([
        ['Árbol ÉTICO Útil', 'arbol etico util'],
        ['Cancún', 'cancun'],
        ['niño', 'nino'],
        ['Örebro', 'orebro'],
    ]);

    test('conserva dígitos y símbolos no alfabéticos', function () {
        expect(FuzzyMatcher::normalizar('100-200 km'))->toBe('100-200 km');
    });
});

describe('FuzzyMatcher::normDist', function () {
    test('normaliza espacios alrededor del guión', function () {
        expect(FuzzyMatcher::normDist('100 - 200 km'))->toBe('100-200 km');
    });

    test('convierte la k aislada en km', function () {
        expect(FuzzyMatcher::normDist('100-200 k'))->toBe('100-200 km');
    });

    test('no rompe palabras que contienen k', function () {
        expect(FuzzyMatcher::normDist('ciudad km 50'))->toBe('ciudad km 50');
    });
});

describe('FuzzyMatcher::match', function () {
    test('coincide cuando el input contiene al patrón', function () {
        expect(FuzzyMatcher::match('Festival Corona Capital 2026', 'corona capital'))->toBeTrue();
    });

    test('coincide cuando el patrón contiene al input', function () {
        expect(FuzzyMatcher::match('corona capital', 'Festival Corona Capital'))->toBeTrue();
    });

    test('ignora acentos y mayúsculas en ambos lados', function () {
        expect(FuzzyMatcher::match('Concierto Música Electrónica', 'musica electronica'))->toBeTrue();
    });

    test('no coincide cadenas sin relación', function () {
        expect(FuzzyMatcher::match('Festival Corona', 'Boda García'))->toBeFalse();
    });
});

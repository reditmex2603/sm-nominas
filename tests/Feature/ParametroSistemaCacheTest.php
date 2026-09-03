<?php

use App\Models\ParametroSistema;
use Illuminate\Support\Facades\Cache;

test('get devuelve el default cuando el parámetro no existe', function () {
    expect(ParametroSistema::get('clave_inexistente', 'fallback'))->toBe('fallback');
});

test('get cachea el valor y set lo invalida', function () {
    ParametroSistema::set('dias_bono_septimo', '6');
    expect(ParametroSistema::get('dias_bono_septimo'))->toBe('6');

    // La segunda lectura viene de caché (no de BD).
    $cacheKey = 'parametro:dias_bono_septimo';
    expect(Cache::has($cacheKey))->toBeTrue();

    // Al setear un nuevo valor, la caché se invalida y el siguiente get lo refleja.
    ParametroSistema::set('dias_bono_septimo', '5');
    expect(Cache::has($cacheKey))->toBeFalse()
        ->and(ParametroSistema::get('dias_bono_septimo'))->toBe('5');
});

test('clear elimina el parámetro y su caché', function () {
    ParametroSistema::set('pago_default_chico', '1500');
    expect(ParametroSistema::get('pago_default_chico'))->toBe('1500');

    ParametroSistema::clear('pago_default_chico');

    expect(ParametroSistema::get('pago_default_chico', 'nada'))->toBe('nada')
        ->and(Cache::has('parametro:pago_default_chico'))->toBeFalse();
});

test('get con valor no encontrado no cachea el default como si fuera real', function () {
    expect(ParametroSistema::get('bono_evento_tecnico_nivel1_mediano', 0))->toBe(0);

    // El default no debe persistir en caché (solo se cachean valores de BD).
    expect(Cache::has('parametro:bono_evento_tecnico_nivel1_mediano'))->toBeFalse();
});

<?php

use App\Models\Anticipo;
use App\Models\Colaborador;
use App\Models\Evento;
use App\Models\JornadaConsolidada;
use App\Models\ParametroSistema;
use App\Models\Prestamo;
use App\Models\PrestamoCuota;
use App\Models\RegistroNormalizado;
use App\Services\NominaCalculator;
use Illuminate\Support\Carbon;

/*
 * Semana de referencia: lunes 2026-08-03 → sábado 2026-08-08.
 * Sueldo diario base: $500. Parámetro de bono: Técnico nivel 1, MEDIANO = $350.
 */

defined('SEMANA_INICIO') or define('SEMANA_INICIO', '2026-08-03'); // lunes
defined('SEMANA_FIN') or define('SEMANA_FIN', '2026-08-08');    // sábado

beforeEach(function () {
    $this->calc = app(NominaCalculator::class);
    ParametroSistema::set('dias_bono_septimo', '6');
    ParametroSistema::set('bono_evento_tecnico_nivel1_mediano', '350');

    $this->colaborador = Colaborador::factory()->base('Técnico', 1)->create([
        'sueldo_diario' => 500.00,
    ]);
});

/** Crea una jornada validada de bodega (sin evento) en la fecha dada. */
function jornadaBodega(Colaborador $c, string $fecha): JornadaConsolidada
{
    return JornadaConsolidada::factory()->create([
        'colaborador_id' => $c->id,
        'fecha' => $fecha,
        'tipo_pago' => 'JORNADA_COMPLETA',
        'validado' => true,
        'detalle' => 'Bodega: Carga de equipo',
    ]);
}

test('semana completa (lunes a sábado) genera bono del séptimo día', function () {
    foreach (['2026-08-03', '2026-08-04', '2026-08-05', '2026-08-06', '2026-08-07', '2026-08-08'] as $fecha) {
        jornadaBodega($this->colaborador, $fecha);
    }

    $r = $this->calc->calcularBase($this->colaborador, Carbon::parse(SEMANA_INICIO), Carbon::parse(SEMANA_FIN));

    expect($r['dias'])->toBe(6.0)
        ->and($r['total_base'])->toBe(3000.0)
        ->and($r['_bono_septimo'])->toBe(500.0)
        ->and($r['bonos_evento'])->toBe(500.0) // incluye el séptimo día
        ->and($r['_bonos_evento_puro'])->toBe(0.0)
        ->and($r['total_final'])->toBe(3500.0);
});

test('semana incompleta no genera bono del séptimo día', function () {
    foreach (['2026-08-03', '2026-08-04', '2026-08-05', '2026-08-06', '2026-08-07'] as $fecha) {
        jornadaBodega($this->colaborador, $fecha);
    }

    $r = $this->calc->calcularBase($this->colaborador, Carbon::parse(SEMANA_INICIO), Carbon::parse(SEMANA_FIN));

    expect($r['dias'])->toBe(5.0)
        ->and($r['total_base'])->toBe(2500.0)
        ->and($r['_bono_septimo'])->toBe(0.0)
        ->and($r['total_final'])->toBe(2500.0);
});

test('jornadas sin validar, SIN_PAGO o ERROR_EVENTO no cuentan como días', function () {
    jornadaBodega($this->colaborador, '2026-08-03');
    jornadaBodega($this->colaborador, '2026-08-04');
    jornadaBodega($this->colaborador, '2026-08-05');
    jornadaBodega($this->colaborador, '2026-08-06');
    JornadaConsolidada::factory()->sinValidar()->create([
        'colaborador_id' => $this->colaborador->id, 'fecha' => '2026-08-07',
    ]);
    JornadaConsolidada::factory()->sinPago()->create([
        'colaborador_id' => $this->colaborador->id, 'fecha' => '2026-08-08',
    ]);

    $r = $this->calc->calcularBase($this->colaborador, Carbon::parse(SEMANA_INICIO), Carbon::parse(SEMANA_FIN));

    expect($r['dias'])->toBe(4.0)->and($r['total_base'])->toBe(2000.0);
});

test('el extra de evento se pondera por las etapas registradas ese día', function () {
    $evento = Evento::factory()->mediano()->create(['nombre' => 'Festival Prueba']);
    jornadaDiaConEvento($this->colaborador, $evento, '2026-08-04');

    // Solo participó en Show (50% del evento)
    RegistroNormalizado::factory()->evento($evento->nombre, 'Show')->create([
        'colaborador_id' => $this->colaborador->id, 'fecha' => '2026-08-04',
    ]);

    $r = $this->calc->calcularBase($this->colaborador, Carbon::parse(SEMANA_INICIO), Carbon::parse(SEMANA_FIN));

    expect($r['dias'])->toBe(1.0)
        ->and($r['_bonos_evento_puro'])->toBe(175.0) // 350 × 1.0 × 0.50
        ->and($r['total_final'])->toBe(675.0);       // 500 + 175
});

test('las tres etapas del evento pagan el 100% del extra de categoría', function () {
    $evento = Evento::factory()->mediano()->create(['nombre' => 'Festival Prueba']);
    jornadaDiaConEvento($this->colaborador, $evento, '2026-08-04');

    foreach (['Montaje', 'Show', 'Desmontaje'] as $etapa) {
        RegistroNormalizado::factory()->evento($evento->nombre, $etapa)->create([
            'colaborador_id' => $this->colaborador->id, 'fecha' => '2026-08-04',
        ]);
    }

    $r = $this->calc->calcularBase($this->colaborador, Carbon::parse(SEMANA_INICIO), Carbon::parse(SEMANA_FIN));

    expect($r['_bonos_evento_puro'])->toBe(350.0);
});

test('un evento CHICO nunca genera bono aunque el día sea jornada + evento', function () {
    $evento = Evento::factory()->chico()->create(['nombre' => 'Reunión Chica']);
    jornadaDiaConEvento($this->colaborador, $evento, '2026-08-04');

    foreach (['Montaje', 'Show', 'Desmontaje'] as $etapa) {
        RegistroNormalizado::factory()->evento($evento->nombre, $etapa)->create([
            'colaborador_id' => $this->colaborador->id, 'fecha' => '2026-08-04',
        ]);
    }

    $r = $this->calc->calcularBase($this->colaborador, Carbon::parse(SEMANA_INICIO), Carbon::parse(SEMANA_FIN));

    expect($r['dias'])->toBe(1.0)
        ->and($r['_bonos_evento_puro'])->toBe(0.0)
        ->and($r['total_final'])->toBe(500.0);
});

test('en traslape el sueldo del día no se reduce y el bono paga el % capturado', function () {
    $evento = Evento::factory()->mediano()->create(['nombre' => 'Festival Prueba']);

    JornadaConsolidada::factory()->traslape(40, "Evento: {$evento->nombre} - Show")->create([
        'colaborador_id' => $this->colaborador->id,
        'fecha' => '2026-08-04',
        'validado' => true,
    ]);

    foreach (['Montaje', 'Show', 'Desmontaje'] as $etapa) {
        RegistroNormalizado::factory()->evento($evento->nombre, $etapa)->create([
            'colaborador_id' => $this->colaborador->id, 'fecha' => '2026-08-04',
        ]);
    }

    $r = $this->calc->calcularBase($this->colaborador, Carbon::parse(SEMANA_INICIO), Carbon::parse(SEMANA_FIN));

    expect($r['dias'])->toBe(1.0)                    // sueldo completo
        ->and($r['_bonos_evento_puro'])->toBe(140.0) // 350 × 0.40 × 100%
        ->and($r['total_final'])->toBe(640.0);
});

test('la compensación activa suma un % sobre el bono ya ponderado', function () {
    $this->colaborador->update(['compensacion_pct' => 10]);

    $evento = Evento::factory()->mediano()->create(['nombre' => 'Festival Prueba']);
    $jornada = jornadaDiaConEvento($this->colaborador, $evento, '2026-08-04');
    $jornada->update(['compensacion_activa' => true]);

    foreach (['Montaje', 'Show', 'Desmontaje'] as $etapa) {
        RegistroNormalizado::factory()->evento($evento->nombre, $etapa)->create([
            'colaborador_id' => $this->colaborador->id, 'fecha' => '2026-08-04',
        ]);
    }

    $r = $this->calc->calcularBase($this->colaborador, Carbon::parse(SEMANA_INICIO), Carbon::parse(SEMANA_FIN));

    expect($r['_bonos_evento_puro'])->toBe(385.0) // 350 + 10% de compensación
        ->and($r['total_final'])->toBe(885.0);
});

test('anticipos y cuotas pendientes en el rango descuentan del total', function () {
    foreach (['2026-08-03', '2026-08-04', '2026-08-05', '2026-08-06', '2026-08-07', '2026-08-08'] as $fecha) {
        jornadaBodega($this->colaborador, $fecha);
    }

    Anticipo::factory()->create([
        'colaborador_id' => $this->colaborador->id, 'monto' => 500, 'fecha' => '2026-08-05',
    ]);
    // Fuera de rango: no debe descontarse
    Anticipo::factory()->create([
        'colaborador_id' => $this->colaborador->id, 'monto' => 999, 'fecha' => '2026-08-20',
    ]);

    $prestamo = Prestamo::factory()->create(['colaborador_id' => $this->colaborador->id]);
    PrestamoCuota::factory()->create([
        'prestamo_id' => $prestamo->id, 'monto' => 300, 'fecha_programada' => '2026-08-06',
    ]);
    // Ya pagada: no debe descontarse
    PrestamoCuota::factory()->pagada()->create([
        'prestamo_id' => $prestamo->id, 'numero_plazo' => 2, 'monto' => 400, 'fecha_programada' => '2026-08-06',
    ]);

    $r = $this->calc->calcularBase($this->colaborador, Carbon::parse(SEMANA_INICIO), Carbon::parse(SEMANA_FIN));

    expect($r['anticipos'])->toBe(500.0)
        ->and($r['prestamos'])->toBe(300.0)
        ->and($r['total_final'])->toBe(2700.0); // 3000 + 500 (7°) − 500 − 300
});

/** Jornada validada tipo JORNADA_COMPLETA + EVENTO con el detalle del evento. */
function jornadaDiaConEvento(Colaborador $c, Evento $e, string $fecha): JornadaConsolidada
{
    return JornadaConsolidada::factory()->conEvento("Evento: {$e->nombre} - Show")->create([
        'colaborador_id' => $c->id,
        'fecha' => $fecha,
        'validado' => true,
    ]);
}

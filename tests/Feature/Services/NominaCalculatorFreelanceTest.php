<?php

use App\Models\Anticipo;
use App\Models\Colaborador;
use App\Models\Evento;
use App\Models\JornadaConsolidada;
use App\Models\RegistroNormalizado;
use App\Services\NominaCalculator;

/*
 * Freelance: pago por evento ponderado por etapas (Montaje 25% + Show 50% + Desmontaje 25%),
 * solo en días con jornada validada; más días adicionales y menos anticipos ligados al evento.
 */

beforeEach(function () {
    $this->calc = app(NominaCalculator::class);
    $this->colaborador = Colaborador::factory()->freelance()->create(['extra_dia_adicional' => 300.00]);
    $this->evento = Evento::factory()->create([
        'nombre' => 'Festival Prueba',
        'pago_por_evento_completo' => 2500.00,
    ]);
});

/** Registro de evento en fecha con su jornada validada (para que contabilice). */
function registroConJornadaValidada(Colaborador $c, Evento $e, string $fecha, string $etapa): void
{
    RegistroNormalizado::factory()->evento($e->nombre, $etapa)->create([
        'colaborador_id' => $c->id, 'fecha' => $fecha,
    ]);
    JornadaConsolidada::factory()->conEvento("Evento: {$e->nombre} - {$etapa}")->create([
        'colaborador_id' => $c->id, 'fecha' => $fecha, 'validado' => true,
    ]);
}

test('con las tres etapas en días validados cobra el 100% del evento', function () {
    registroConJornadaValidada($this->colaborador, $this->evento, '2026-08-03', 'Montaje');
    registroConJornadaValidada($this->colaborador, $this->evento, '2026-08-04', 'Show');
    registroConJornadaValidada($this->colaborador, $this->evento, '2026-08-05', 'Desmontaje');

    $r = $this->calc->calcularFreelance($this->colaborador, $this->evento);

    expect($r['_porcentaje'])->toBe(100.0)
        ->and($r['total_base'])->toBe(2500.0)
        ->and($r['total_final'])->toBe(2500.0);
});

test('con solo la etapa Show cobra el 50% del evento', function () {
    registroConJornadaValidada($this->colaborador, $this->evento, '2026-08-04', 'Show');

    $r = $this->calc->calcularFreelance($this->colaborador, $this->evento);

    expect($r['_porcentaje'])->toBe(50.0)
        ->and($r['total_base'])->toBe(1250.0)
        ->and($r['total_final'])->toBe(1250.0);
});

test('los registros en días sin jornada validada no contabilizan', function () {
    RegistroNormalizado::factory()->evento($this->evento->nombre, 'Show')->create([
        'colaborador_id' => $this->colaborador->id, 'fecha' => '2026-08-04',
    ]);
    // Jornada existe pero SIN validar
    JornadaConsolidada::factory()->sinValidar()->create([
        'colaborador_id' => $this->colaborador->id, 'fecha' => '2026-08-04',
    ]);

    $r = $this->calc->calcularFreelance($this->colaborador, $this->evento);

    expect($r['_porcentaje'])->toBe(0.0)
        ->and($r['total_final'])->toBe(0.0);
});

test('los días adicionales suman el extra por día pactado', function () {
    registroConJornadaValidada($this->colaborador, $this->evento, '2026-08-04', 'Show');

    $r = $this->calc->calcularFreelance($this->colaborador, $this->evento, diasAdicionales: 2);

    expect($r['bonos_evento'])->toBe(600.0)   // 2 × 300
        ->and($r['total_final'])->toBe(1850.0); // 1250 + 600
});

test('solo los anticipos cuyo concepto coincide con el evento descuentan', function () {
    registroConJornadaValidada($this->colaborador, $this->evento, '2026-08-04', 'Show');

    Anticipo::factory()->create([
        'colaborador_id' => $this->colaborador->id,
        'concepto' => 'Anticipo Festival Prueba',
        'monto' => 800,
    ]);
    // Concepto sin relación con el evento: NO debe descontarse
    Anticipo::factory()->create([
        'colaborador_id' => $this->colaborador->id,
        'concepto' => 'Préstamo personal',
        'monto' => 999,
    ]);

    $r = $this->calc->calcularFreelance($this->colaborador, $this->evento);

    expect($r['anticipos'])->toBe(800.0)
        ->and($r['total_final'])->toBe(450.0); // 1250 − 800
});

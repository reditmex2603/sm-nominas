<?php

use App\Models\Colaborador;
use App\Models\HistoricoNomina;
use App\Models\JornadaConsolidada;
use App\Models\Prestamo;
use App\Models\PrestamoCuota;
use App\Models\RegistroNormalizado;
use App\Models\User;

/*
 * Flujo HTTP crítico de nómina: calcular → guardar (PENDIENTE) → pagar (PAGADO),
 * con sus bloqueos de seguridad (sin validar, ya pagada, permisos).
 */

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->colaborador = Colaborador::factory()->base()->create(['sueldo_diario' => 500.00]);

    foreach (['2026-08-03', '2026-08-04'] as $fecha) {
        JornadaConsolidada::factory()->create([
            'colaborador_id' => $this->colaborador->id,
            'fecha' => $fecha,
            'tipo_pago' => 'JORNADA_COMPLETA',
            'validado' => true,
        ]);
    }
});

function paramsNomina(Colaborador $c): array
{
    return [
        'tipo' => 'COLABORADOR BASE',
        'colaborador_id' => $c->id,
        'inicio' => '2026-08-03',
        'fin' => '2026-08-08',
    ];
}

test('calcular devuelve el desglose en JSON sin persistir nada', function () {
    $this->actingAs($this->admin)
        ->getJson(route('nomina.calcular', paramsNomina($this->colaborador)))
        ->assertOk()
        ->assertJsonPath('dias', 2)
        ->assertJsonPath('total_base', 1000)
        ->assertJsonPath('total_final', 1000);

    expect(HistoricoNomina::count())->toBe(0);
});

test('guardar crea la nómina como PENDIENTE y liga las cuotas del periodo', function () {
    $prestamo = Prestamo::factory()->create(['colaborador_id' => $this->colaborador->id]);
    $cuota = PrestamoCuota::factory()->create([
        'prestamo_id' => $prestamo->id, 'monto' => 300, 'fecha_programada' => '2026-08-04',
    ]);

    $this->actingAs($this->admin)
        ->post(route('nomina.guardar'), paramsNomina($this->colaborador))
        ->assertSessionHasNoErrors();

    $nomina = HistoricoNomina::sole();

    expect($nomina->estado->value)->toBe('PENDIENTE')
        ->and((float) $nomina->total_final)->toBe(700.0) // 1000 − 300 de cuota
        ->and((float) $nomina->prestamos)->toBe(300.0)
        ->and($cuota->fresh()->historico_nomina_id)->toBe($nomina->id)
        ->and($cuota->fresh()->estado->value)->toBe('PENDIENTE'); // se liquida al pagar, no antes
});

test('guardar rechaza el periodo si hay jornadas sin validar', function () {
    JornadaConsolidada::factory()->sinValidar()->create([
        'colaborador_id' => $this->colaborador->id, 'fecha' => '2026-08-05',
    ]);

    $this->actingAs($this->admin)
        ->post(route('nomina.guardar'), paramsNomina($this->colaborador))
        ->assertSessionHasErrors('jornadas_sin_validar');

    expect(HistoricoNomina::count())->toBe(0);
});

test('una nómina PAGADO no puede modificarse ni pagarse dos veces', function () {
    $this->actingAs($this->admin)->post(route('nomina.guardar'), paramsNomina($this->colaborador));
    $nomina = HistoricoNomina::sole();

    $this->actingAs($this->admin)
        ->patch(route('nomina.pagar', $nomina))
        ->assertSessionHasNoErrors();

    expect($nomina->fresh()->estado->value)->toBe('PAGADO');

    // Re-guardar sobre una pagada: rechazado
    $this->actingAs($this->admin)
        ->post(route('nomina.guardar'), paramsNomina($this->colaborador));

    expect(HistoricoNomina::count())->toBe(1)
        ->and($nomina->fresh()->estado->value)->toBe('PAGADO');

    // Pagar dos veces: rechazado
    $this->actingAs($this->admin)
        ->patch(route('nomina.pagar', $nomina))
        ->assertSessionHas('error');
});

test('pagar liquida las cuotas de préstamo ligadas a la nómina', function () {
    $prestamo = Prestamo::factory()->create(['colaborador_id' => $this->colaborador->id]);
    $cuota = PrestamoCuota::factory()->create([
        'prestamo_id' => $prestamo->id, 'monto' => 300, 'fecha_programada' => '2026-08-04',
    ]);

    $this->actingAs($this->admin)->post(route('nomina.guardar'), paramsNomina($this->colaborador));
    $nomina = HistoricoNomina::sole();

    $this->actingAs($this->admin)->patch(route('nomina.pagar', $nomina));

    expect($nomina->fresh()->estado->value)->toBe('PAGADO')
        ->and($cuota->fresh()->estado->value)->toBe('PAGADA')
        ->and($cuota->fresh()->fecha_pago)->not->toBeNull();
});

test('eliminar una nómina pendiente libera sus cuotas para un cálculo futuro', function () {
    $prestamo = Prestamo::factory()->create(['colaborador_id' => $this->colaborador->id]);
    $cuota = PrestamoCuota::factory()->create([
        'prestamo_id' => $prestamo->id, 'monto' => 300, 'fecha_programada' => '2026-08-04',
    ]);

    $this->actingAs($this->admin)->post(route('nomina.guardar'), paramsNomina($this->colaborador));
    $nomina = HistoricoNomina::sole();

    $this->actingAs($this->admin)
        ->delete(route('nomina.eliminar', $nomina))
        ->assertSessionHasNoErrors();

    expect(HistoricoNomina::count())->toBe(0)
        ->and($cuota->fresh()->historico_nomina_id)->toBeNull()
        ->and($cuota->fresh()->estado->value)->toBe('PENDIENTE');
});

test('un usuario sin permiso de nómina recibe 403 en todo el flujo', function () {
    $capturista = User::factory()->conPermisos([])->create();

    $this->actingAs($capturista)->getJson(route('nomina.calcular', paramsNomina($this->colaborador)))->assertForbidden();
    $this->actingAs($capturista)->post(route('nomina.guardar'), paramsNomina($this->colaborador))->assertForbidden();
});

test('jornadas/generar consolida los registros en jornadas', function () {
    RegistroNormalizado::factory()->bodega()->create([
        'colaborador_id' => $this->colaborador->id, 'fecha' => '2026-08-06',
    ]);

    $this->actingAs($this->admin)
        ->post(route('jornadas.generar'))
        ->assertSessionHasNoErrors();

    expect(
        JornadaConsolidada::where('colaborador_id', $this->colaborador->id)
            ->where('fecha', '2026-08-06')
            ->exists()
    )->toBeTrue();
});

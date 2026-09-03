<?php

use App\Models\Auditoria;
use App\Models\Colaborador;
use App\Models\HistoricoNomina;
use App\Models\Prestamo;
use App\Models\PrestamoCuota;
use App\Models\User;

/*
 * Auditoría de operaciones financieras: cada mutación sensible (pagar nómina,
 * eliminar nómina, pagar/revertir cuota, eliminar préstamo) deja un registro.
 */

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->colaborador = Colaborador::factory()->base()->create(['sueldo_diario' => 500.00]);
});

function nominaPendienteEnBD(): HistoricoNomina
{
    return HistoricoNomina::factory()->create(['estado' => 'PENDIENTE']);
}

test('pagar una nómina registra auditoría con el usuario y el monto', function () {
    $nomina = nominaPendienteEnBD();

    $this->actingAs($this->admin)
        ->patch(route('nomina.pagar', $nomina));

    $auditoria = Auditoria::where('evento', 'nomina.pagada')->sole();

    expect($auditoria->user_id)->toBe($this->admin->id)
        ->and($auditoria->modelo)->toBe(HistoricoNomina::class)
        ->and($auditoria->modelo_id)->toBe($nomina->id)
        ->and((float) $auditoria->detalles['total_final'])->toBe((float) $nomina->total_final);
});

test('eliminar una nómina pendiente registra auditoría', function () {
    $nomina = nominaPendienteEnBD();

    $this->actingAs($this->admin)
        ->delete(route('nomina.eliminar', $nomina));

    expect(Auditoria::where('evento', 'nomina.eliminada')->where('modelo_id', $nomina->id)->exists())->toBeTrue();
});

test('pagar una cuota de préstamo registra auditoría', function () {
    $prestamo = Prestamo::factory()->create(['colaborador_id' => $this->colaborador->id]);
    $cuota = PrestamoCuota::factory()->create(['prestamo_id' => $prestamo->id]);

    $this->actingAs($this->admin)
        ->patch(route('prestamos.cuotas.pagar', $cuota));

    $auditoria = Auditoria::where('evento', 'cuota.pagada')->sole();

    expect($auditoria->modelo_id)->toBe($cuota->id)
        ->and($auditoria->detalles['numero_plazo'])->toBe($cuota->numero_plazo);
});

test('revertir una cuota registra auditoría', function () {
    $prestamo = Prestamo::factory()->create(['colaborador_id' => $this->colaborador->id]);
    $cuota = PrestamoCuota::factory()->pagada()->create(['prestamo_id' => $prestamo->id]);

    $this->actingAs($this->admin)
        ->patch(route('prestamos.cuotas.revertir', $cuota));

    expect(Auditoria::where('evento', 'cuota.revertida')->where('modelo_id', $cuota->id)->exists())->toBeTrue();
});

test('un pago manual de cuota con usuario autenticado queda atribuido', function () {
    $prestamo = Prestamo::factory()->create(['colaborador_id' => $this->colaborador->id]);
    $cuota = PrestamoCuota::factory()->create(['prestamo_id' => $prestamo->id]);

    $this->actingAs($this->admin)->patch(route('prestamos.cuotas.pagar', $cuota));

    expect(Auditoria::sole()->user_id)->toBe($this->admin->id);
});

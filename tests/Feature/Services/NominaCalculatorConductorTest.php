<?php

use App\Models\Colaborador;
use App\Models\JornadaConsolidada;
use App\Models\ParametroSistema;
use App\Models\TransporteDistancia;
use App\Models\TransporteTarifa;
use App\Models\TransporteVehiculo;
use App\Services\NominaCalculator;
use Illuminate\Support\Carbon;

/*
 * Conductor: cobra la tarifa de cada ruta detectada en el detalle de la jornada
 * ("Transporte: Manejó un(a) {vehículo} {distancia}, de {origen} a {destino}").
 * Conductor Base: además cobra sueldo diario por jornada y bono de séptimo día.
 */

defined('SEMANA_INICIO') or define('SEMANA_INICIO', '2026-08-03'); // lunes
defined('SEMANA_FIN') or define('SEMANA_FIN', '2026-08-08');    // sábado

beforeEach(function () {
    $this->calc = app(NominaCalculator::class);
    ParametroSistema::set('dias_bono_septimo', '6');

    $this->vehiculo = TransporteVehiculo::factory()->create(['nombre' => 'Camión']);
    $this->distancia = TransporteDistancia::factory()->create(['nombre' => '100-200km']);
    TransporteTarifa::factory()->create([
        'vehiculo_id' => $this->vehiculo->id,
        'distancia_id' => $this->distancia->id,
        'tarifa' => 1200.00,
    ]);
});

function jornadaRuta(Colaborador $c, string $fecha, string $tipoPago = 'JORNADA_COMPLETA', ?int $traslapePct = null): JornadaConsolidada
{
    return JornadaConsolidada::factory()->create([
        'colaborador_id' => $c->id,
        'fecha' => $fecha,
        'tipo_pago' => $tipoPago,
        'traslape_pct' => $traslapePct,
        'validado' => true,
        'actividades' => ['Transporte'],
        'detalle' => 'Transporte: Manejó un(a) Camión 100-200km, de Bodega a Sede',
    ]);
}

test('cada ruta detectada cobra su tarifa de vehículo + distancia', function () {
    $conductor = Colaborador::factory()->conductor()->create();
    jornadaRuta($conductor, '2026-08-04');
    jornadaRuta($conductor, '2026-08-05');

    $r = $this->calc->calcularConductor($conductor, Carbon::parse(SEMANA_INICIO), Carbon::parse(SEMANA_FIN));

    expect($r['dias'])->toBe(2)
        ->and($r['total_base'])->toBe(2400.0)
        ->and($r['total_final'])->toBe(2400.0)
        ->and($r['_rutas'][0]['vehiculo'])->toBe('Camión')
        ->and($r['_rutas'][0]['distancia'])->toBe('100-200km')
        ->and($r['_rutas'][0]['monto'])->toBe(1200.0);
});

test('un detalle sin patrón de manejo se marca no detectado y paga 0', function () {
    $conductor = Colaborador::factory()->conductor()->create();

    JornadaConsolidada::factory()->create([
        'colaborador_id' => $conductor->id,
        'fecha' => '2026-08-04',
        'tipo_pago' => 'JORNADA_COMPLETA',
        'validado' => true,
        'actividades' => ['Transporte'],
        'detalle' => 'Transporte: actividad sin formato reconocible',
    ]);

    $r = $this->calc->calcularConductor($conductor, Carbon::parse(SEMANA_INICIO), Carbon::parse(SEMANA_FIN));

    expect($r['total_base'])->toBe(0.0)
        ->and($r['_rutas'][0]['vehiculo'])->toBe('No detectado')
        ->and($r['_rutas'][0]['monto'])->toBe(0.0);
});

test('en traslape la tarifa de la ruta paga el % capturado', function () {
    $conductor = Colaborador::factory()->conductor()->create();
    jornadaRuta($conductor, '2026-08-04', 'TRASLAPE', 40);

    $r = $this->calc->calcularConductor($conductor, Carbon::parse(SEMANA_INICIO), Carbon::parse(SEMANA_FIN));

    expect($r['total_base'])->toBe(480.0); // 1200 × 0.40
});

test('conductor base cobra sueldo por día + rutas + bono del séptimo día', function () {
    $conductorBase = Colaborador::factory()->conductorBase()->create(['sueldo_diario' => 500.00]);

    // Semana completa L–S; el jueves además manejó una ruta tarifada
    foreach (['2026-08-03', '2026-08-05', '2026-08-06', '2026-08-07', '2026-08-08'] as $fecha) {
        JornadaConsolidada::factory()->create([
            'colaborador_id' => $conductorBase->id,
            'fecha' => $fecha,
            'tipo_pago' => 'JORNADA_COMPLETA',
            'validado' => true,
            'actividades' => ['Bodega'],
            'detalle' => 'Bodega: Carga de equipo',
        ]);
    }
    jornadaRuta($conductorBase, '2026-08-04');

    $r = $this->calc->calcularConductorBase($conductorBase, Carbon::parse(SEMANA_INICIO), Carbon::parse(SEMANA_FIN));

    expect($r['dias'])->toBe(6)
        ->and($r['total_base'])->toBe(3000.0)   // 6 × 500
        ->and($r['_bono_septimo'])->toBe(500.0)
        ->and($r['_total_rutas'])->toBe(1200.0)
        ->and($r['total_final'])->toBe(4700.0); // 3000 + 500 + 1200
});

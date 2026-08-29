<?php

namespace App\Services\Calculadores;

use App\Enums\TipoColaborador;
use App\Models\Colaborador;
use App\Models\TransporteDistancia;
use App\Models\TransporteVehiculo;
use Illuminate\Support\Carbon;

/**
 * Nómina Conductor Base: recibe sueldo diario por cada jornada (Bodega o Ruta) + pago de
 * sus rutas de transporte (como Conductor) + bono de séptimo día (como Base).
 */
class CalculadorConductorBase extends CalculadorConductor
{
    public function calcular(Colaborador $col, Carbon $inicio, Carbon $fin, float $compensacion = 0): array
    {
        $jornadas = $this->jornadasValidadas($col->id, $inicio, $fin);
        $vehiculos = TransporteVehiculo::all();
        $distancias = TransporteDistancia::all();

        // Cada jornada válida (Bodega o Ruta) cuenta un día completo: recibe el sueldo diario
        // aunque ese día solo haya registrado rutas de transporte.
        $diasTotales = $jornadas->count();

        $rutas = [];
        $totalRutas = 0.0;

        foreach ($jornadas as $j) {
            $tarifa = $this->detectarTarifa($j->detalle ?? '', $vehiculos, $distancias);

            // Traslape (2 eventos/rutas el mismo día): se paga el % que definió el admin de la
            // tarifa, no la ruta completa (igual que Conductor puro).
            $fraccion = $this->fraccionPago($j);
            $tarifa['monto'] = round($tarifa['monto'] * $fraccion, 2);

            $totalRutas += $tarifa['monto'];
            $rutas[] = array_merge($tarifa, ['fecha' => $j->fecha->format('Y-m-d'), 'detalle' => $j->detalle, 'extras' => $j->extras]);
        }

        $totalBase = (float) $col->sueldo_diario * $diasTotales;

        // Bono séptimo día: igual que Base (agrupa por semana lun-sáb)
        $bonoSeptimoDia = $this->bonoSeptimoDia($col, $jornadas);

        $existente = $this->nomina($col->id, null, $inicio, $fin);

        $anticipos = $this->anticiposEnRango($col->id, $inicio, $fin);

        $prestamosInfo = $this->prestamosEnRango($col->id, $inicio, $fin, $existente?->id);
        $prestamos = $prestamosInfo['total'];

        $totalFinal = $totalBase + $bonoSeptimoDia + $totalRutas + $compensacion - $anticipos - $prestamos;

        return [
            'tipo' => TipoColaborador::ConductorBase->value,
            'tipo_colaborador' => TipoColaborador::ConductorBase->value,
            'colaborador_id' => $col->id,
            'periodo_inicio' => $inicio->format('Y-m-d'),
            'periodo_fin' => $fin->format('Y-m-d'),
            'evento_id' => null,
            'dias' => $diasTotales,
            'sueldo_diario' => (float) $col->sueldo_diario,
            'total_base' => round($totalBase, 2),
            'bonos_evento' => round($bonoSeptimoDia, 2),
            'compensaciones' => $compensacion,
            'anticipos' => round($anticipos, 2),
            'prestamos' => round($prestamos, 2),
            'total_final' => round($totalFinal, 2),
            '_bono_septimo' => round($bonoSeptimoDia, 2),
            '_sueldo_diario' => (float) $col->sueldo_diario,
            '_jornadas' => $jornadas->map(fn ($j) => [
                'fecha' => $j->fecha->format('Y-m-d'),
                'tipo_pago' => $j->tipo_pago->value,
                'traslape_pct' => $j->traslape_pct,
                'detalle' => $j->detalle,
                'extras' => $j->extras,
                'dia_fraccion' => 1.0,
                'bono_evento' => 0.0,
                'eventos_dia' => [],
            ])->values()->all(),
            '_rutas' => $rutas,
            '_total_rutas' => round($totalRutas, 2),
            '_prestamo_detalle' => $prestamosInfo['detalle'],
            'estado' => $existente?->estado,
            'nomina_id' => $existente?->id,
        ];
    }
}

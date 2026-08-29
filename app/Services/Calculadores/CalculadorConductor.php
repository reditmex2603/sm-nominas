<?php

namespace App\Services\Calculadores;

use App\Enums\TipoColaborador;
use App\Enums\TipoPago;
use App\Models\Colaborador;
use App\Models\JornadaConsolidada;
use App\Models\TransporteDistancia;
use App\Models\TransporteTarifa;
use App\Models\TransporteVehiculo;
use App\Services\FuzzyMatcher;
use App\Support\Money;
use Illuminate\Support\Carbon;

/**
 * Nómina Conductor: pago por ruta detectada en el detalle de cada jornada validada,
 * con tarifa según vehículo + distancia (o standby), ponderada por traslape.
 */
class CalculadorConductor extends AbstractCalculadorNomina
{
    public function calcular(Colaborador $col, Carbon $inicio, Carbon $fin, float $compensacion = 0): array
    {
        $jornadas = $this->jornadasValidadas($col->id, $inicio, $fin);
        $vehiculos = TransporteVehiculo::all();
        $distancias = TransporteDistancia::all();

        $rutas = [];
        $totalRutas = Money::fromCents(0);

        foreach ($jornadas as $j) {
            $tarifa = $this->detectarTarifa($j->detalle ?? '', $vehiculos, $distancias);

            // Traslape (2 eventos/rutas el mismo día): se paga el % que definió el admin de la
            // tarifa, no la ruta completa.
            $fraccion = $this->fraccionPago($j);
            $montoRuta = Money::from($tarifa['monto'])->multiplicarPor($fraccion);
            $tarifa['monto'] = $montoRuta->toFloat();

            $totalRutas = $totalRutas->sumar($montoRuta);
            $rutas[] = array_merge($tarifa, ['fecha' => $j->fecha->format('Y-m-d'), 'detalle' => $j->detalle, 'extras' => $j->extras]);
        }

        $existente = $this->nomina($col->id, null, $inicio, $fin);

        $anticipos = Money::from($this->anticiposEnRango($col->id, $inicio, $fin));

        $prestamosInfo = $this->prestamosEnRango($col->id, $inicio, $fin, $existente?->id);
        $prestamos = Money::from($prestamosInfo['total']);

        $totalFinal = $totalRutas->sumar($compensacion)->restar($anticipos)->restar($prestamos);

        return [
            'tipo' => TipoColaborador::Conductor->value,
            'tipo_colaborador' => TipoColaborador::Conductor->value,
            'colaborador_id' => $col->id,
            'periodo_inicio' => $inicio->format('Y-m-d'),
            'periodo_fin' => $fin->format('Y-m-d'),
            'evento_id' => null,
            'dias' => count($jornadas),
            'sueldo_diario' => 0,
            'total_base' => $totalRutas->toFloat(),
            'bonos_evento' => 0,
            'compensaciones' => $compensacion,
            'anticipos' => $anticipos->toFloat(),
            'prestamos' => $prestamos->toFloat(),
            'total_final' => $totalFinal->toFloat(),
            '_rutas' => $rutas,
            '_total_rutas' => $totalRutas->toFloat(),
            '_prestamo_detalle' => $prestamosInfo['detalle'],
            'estado' => $existente?->estado,
            'nomina_id' => $existente?->id,
        ];
    }

    /**
     * Fracción de pago (0.0–1.0) de la jornada según tipo_pago. TRASLAPE usa el porcentaje
     * (1-99) que el admin capturó en `traslape_pct`, ya no un 40%/50% fijo. Usado para la
     * tarifa de ruta (Conductor); Base no la usa para su sueldo (§ traslape nunca reduce el
     * sueldo del día).
     */
    protected function fraccionPago(JornadaConsolidada $j): float
    {
        return match ($j->tipo_pago) {
            TipoPago::JornadaCompleta, TipoPago::JornadaCompletaEvento => 1.0,
            TipoPago::Traslape => ((int) ($j->traslape_pct ?? 0)) / 100,
            default => 0.0,
        };
    }

    protected function detectarTarifa(string $detalle, $vehiculos, $distancias): array
    {
        $noDetectado = ['vehiculo' => 'No detectado', 'distancia' => 'No detectada', 'monto' => 0.0];

        if (! preg_match('/Manej[oó] un\(a\)\s+(.+?),\s*de\s+/iu', $detalle, $m)) {
            return $noDetectado;
        }

        $resto = $m[1]; // "{vehiculo} {distancia}"

        // Buscar distancia
        $distanciaEncontrada = null;
        $vehiculoTexto = $resto;

        foreach ($distancias as $dist) {
            $normDist = FuzzyMatcher::normDist($dist->nombre);
            $normResto = FuzzyMatcher::normDist($resto);

            if (str_contains($normResto, $normDist)) {
                $distanciaEncontrada = $dist;
                $pos = mb_stripos($normResto, $normDist);
                $vehiculoTexto = trim(mb_substr($resto, 0, $pos));
                break;
            }
        }

        // Sin distancia → intentar columna STANDBY
        if (! $distanciaEncontrada) {
            $distanciaEncontrada = $distancias->firstWhere('es_standby', true);
        }

        if (! $distanciaEncontrada) {
            return array_merge($noDetectado, ['vehiculo' => $vehiculoTexto ?: 'No detectado']);
        }

        // Buscar vehículo
        $vehiculoEncontrado = null;
        foreach ($vehiculos as $v) {
            if (FuzzyMatcher::match($vehiculoTexto, $v->nombre)) {
                $vehiculoEncontrado = $v;
                break;
            }
        }

        if (! $vehiculoEncontrado) {
            return [
                'vehiculo' => $vehiculoTexto ?: 'No detectado',
                'distancia' => $distanciaEncontrada->nombre,
                'monto' => 0.0,
            ];
        }

        $tarifa = TransporteTarifa::where('vehiculo_id', $vehiculoEncontrado->id)
            ->where('distancia_id', $distanciaEncontrada->id)
            ->value('tarifa');

        return [
            'vehiculo' => $vehiculoEncontrado->nombre,
            'distancia' => $distanciaEncontrada->nombre,
            'monto' => (float) ($tarifa ?? 0),
        ];
    }
}

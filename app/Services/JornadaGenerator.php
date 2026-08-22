<?php

namespace App\Services;

use App\Models\Evento;
use App\Models\JornadaConsolidada;
use App\Models\RegistroNormalizado;
use Illuminate\Support\Collection;

class JornadaGenerator
{
    private Collection $eventos;

    /** @return string[] Lista de errores detectados */
    public function generar(): array
    {
        $errores = [];
        $this->eventos = Evento::all();

        $registros = RegistroNormalizado::with('colaborador')->get();

        // Agrupar por colaborador + fecha
        $grupos = $registros->groupBy(
            fn ($r) => $r->colaborador_id . '_' . $r->fecha->format('Y-m-d'),
        );

        foreach ($grupos as $grupoRegistros) {
            $primer = $grupoRegistros->first();
            $colaboradorId = $primer->colaborador_id;
            $fecha = $primer->fecha;
            $tipo = $primer->colaborador->tipo;

            // Preservar validado/tipoPago si ya fue revisado
            $existente = JornadaConsolidada::where('colaborador_id', $colaboradorId)
                ->where('fecha', $fecha)
                ->first();

            $preservarValidacion = $existente && $existente->validado;

            // Entrada y salida (solo BASE y CONDUCTOR BASE)
            // Entrada = mínima hora_entrada del día; salida = máxima hora_salida (o hora si no hay)
            if (in_array($tipo, ['COLABORADOR BASE', 'CONDUCTOR BASE'], true)) {
                $entrada = $grupoRegistros->pluck('hora')->sort()->first();
                $salida  = $grupoRegistros
                    ->map(fn ($r) => $r->hora_salida ?? $r->hora)
                    ->sort()
                    ->last();
            } else {
                $entrada = null;
                $salida  = null;
            }

            // Construir detalle y conjuntos
            $lineas = [];
            $extras = [];
            $evidencias = [];
            $comentarios = [];
            $errorJornada = null;

            foreach ($grupoRegistros as $reg) {
                [$linea, $err] = $this->construirLinea($reg, $tipo);

                if ($linea) {
                    $lineas[] = $linea;
                }
                if ($err) {
                    $errorJornada = $err;
                }
                if ($reg->extras) {
                    $extras[] = $reg->extras;
                }
                if ($reg->evidencia_path) {
                    $evidencias[] = $reg->evidencia_path;
                }
                if ($reg->comentarios) {
                    $comentarios[] = $reg->comentarios;
                }
            }

            $detalle = implode("\n", array_unique($lineas));
            $actividades = $grupoRegistros->pluck('tipo_actividad')->unique()->values()->toArray();

            $datosAsistencia = [
                'entrada'     => $entrada,
                'salida'      => $salida,
                'actividades' => $actividades,
                'detalle'     => $detalle ?: null,
                'extras'      => $extras ? implode(', ', array_unique($extras)) : null,
                'evidencias'  => $evidencias ? implode(', ', array_unique($evidencias)) : null,
                'comentarios' => $comentarios ? implode(', ', array_unique($comentarios)) : null,
            ];

            if ($preservarValidacion) {
                // Solo actualizar asistencia; NO tocar validado ni tipo_pago
                $existente->update($datosAsistencia);
            } else {
                JornadaConsolidada::updateOrCreate(
                    ['colaborador_id' => $colaboradorId, 'fecha' => $fecha],
                    array_merge($datosAsistencia, [
                        'validado'  => false,
                        'tipo_pago' => $this->proponerTipoPago($detalle, $errorJornada),
                    ]),
                );
            }

            if ($errorJornada) {
                $errores[] = "[{$fecha->format('d/m/Y')} collab #{$colaboradorId}] {$errorJornada}";
            }
        }

        return $errores;
    }

    /** @return array{0: string|null, 1: string|null} [línea de detalle, mensaje de error] */
    private function construirLinea(RegistroNormalizado $reg, string $tipo): array
    {
        $tipoActividad = $reg->tipo_actividad;
        $error = null;

        // Reglas de validación por tipo de colaborador
        if ($tipo === 'FREELANCE' && $tipoActividad !== 'Evento') {
            $error = 'Freelance actividad inválida';
        }
        if ($tipo === 'CONDUCTOR' && $tipoActividad !== 'Transporte') {
            $error = 'Conductor actividad inválida';
        }
        if ($tipo === 'COLABORADOR BASE' && $tipoActividad === 'Transporte') {
            $error = 'Base en transporte';
        }
        if ($tipo === 'CONDUCTOR BASE' && $tipoActividad === 'Evento') {
            $error = 'Conductor base en evento';
        }

        $linea = match ($tipoActividad) {
            'Bodega'     => "Bodega: {$reg->actividad}",
            'Evento'     => $this->lineaEvento($reg, $error),
            'Transporte' => "Transporte: Manejó un(a) {$reg->vehiculo} {$reg->distancia}, de {$reg->origen} a {$reg->destino}",
            default      => null,
        };

        return [$linea, $error];
    }

    private function lineaEvento(RegistroNormalizado $reg, ?string &$error): string
    {
        $nombreOficial = $this->resolverEvento($reg->evento_raw ?? '');

        if ($nombreOficial === null) {
            $error = 'Evento no identificado';

            return "Evento: NO IDENTIFICADO - {$reg->etapa}";
        }

        return "Evento: {$nombreOficial} - {$reg->etapa}";
    }

    private function resolverEvento(string $raw): ?string
    {
        if (trim($raw) === '') {
            return null;
        }

        foreach ($this->eventos as $evento) {
            if (FuzzyMatcher::match($raw, $evento->nombre)) {
                return $evento->nombre;
            }
        }

        return null;
    }

    private function proponerTipoPago(string $detalle, ?string $error): string
    {
        if ($error === 'Evento no identificado') {
            return 'ERROR_EVENTO';
        }

        if (trim($detalle) === '') {
            return 'SIN_PAGO';
        }

        // Un día puede tener registros de más de un evento (el colaborador participó
        // parcialmente en dos eventos distintos el mismo día) — extraer todos los nombres.
        preg_match_all('/^Evento: (.+?) - /m', $detalle, $matches);
        $nombresEventos = $matches[1] ?? [];

        if (in_array('NO IDENTIFICADO', $nombresEventos, true)) {
            return 'ERROR_EVENTO';
        }

        // Si cualquiera de los eventos del día no es CHICO, ya amerita el bono de evento,
        // aunque otro evento del mismo día sí lo sea.
        foreach ($nombresEventos as $nombre) {
            foreach ($this->eventos as $evento) {
                if (FuzzyMatcher::match($nombre, $evento->nombre) && $evento->tamano !== 'CHICO') {
                    return 'JORNADA_COMPLETA + EVENTO';
                }
            }
        }

        // Bodega, Transporte, o evento(s) CHICO sin ningún evento mayor → jornada completa sin bono
        if (!empty($nombresEventos) || str_contains($detalle, 'Bodega:') || str_contains($detalle, 'Transporte:')) {
            return 'JORNADA_COMPLETA';
        }

        return 'SIN_PAGO';
    }
}

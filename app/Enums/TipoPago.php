<?php

namespace App\Enums;

/** Clasificación de pago de una jornada (jornadas_consolidadas.tipo_pago). */
enum TipoPago: string
{
    case JornadaCompleta = 'JORNADA_COMPLETA';
    case JornadaCompletaEvento = 'JORNADA_COMPLETA + EVENTO';
    case Traslape = 'TRASLAPE';
    case SinPago = 'SIN_PAGO';
    case ErrorEvento = 'ERROR_EVENTO';

    /** Jornadas que no aportan días ni pagos (SIN_PAGO / ERROR_EVENTO). */
    public function esExcluible(): bool
    {
        return in_array($this, [self::SinPago, self::ErrorEvento], true);
    }
}

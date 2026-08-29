<?php

namespace App\Enums;

/** Etapas de un evento (registros_normalizados.etapa). */
enum EtapaEvento: string
{
    case Montaje = 'Montaje';
    case Show = 'Show';
    case Desmontaje = 'Desmontaje';

    /** Ponderación de pago de la etapa (ver NominaCalculator::PCT_ETAPA). */
    public function porcentaje(): float
    {
        return match ($this) {
            self::Montaje => 0.25,
            self::Show => 0.50,
            self::Desmontaje => 0.25,
        };
    }
}

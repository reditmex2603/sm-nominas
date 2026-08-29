<?php

namespace App\Enums;

/** Tamaño de un evento (eventos.tamano). */
enum TamanoEvento: string
{
    case Chico = 'CHICO';
    case Mediano = 'MEDIANO';
    case Grande = 'GRANDE';

    /** ¿Genera bono de evento para personal Base? CHICO nunca genera. */
    public function generaBonoEvento(): bool
    {
        return $this !== self::Chico;
    }
}

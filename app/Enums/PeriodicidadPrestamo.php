<?php

namespace App\Enums;

/** Periodicidad de un préstamo (prestamos.periodicidad). */
enum PeriodicidadPrestamo: string
{
    case Semanal = 'SEMANAL';
    case Quincenal = 'QUINCENAL';
    case Mensual = 'MENSUAL';
}

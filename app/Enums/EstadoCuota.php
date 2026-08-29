<?php

namespace App\Enums;

/** Estado de una cuota de préstamo (prestamo_cuotas.estado). */
enum EstadoCuota: string
{
    case Pendiente = 'PENDIENTE';
    case Pagada = 'PAGADA';
}

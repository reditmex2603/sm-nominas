<?php

namespace App\Enums;

/** Estado de una nómina (historico_nomina.estado). */
enum EstadoNomina: string
{
    case Pendiente = 'PENDIENTE';
    case Pagado = 'PAGADO';
}

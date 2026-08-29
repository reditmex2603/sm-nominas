<?php

namespace App\Enums;

/** Tipos de actividad registrada (registros_normalizados.tipo_actividad). */
enum TipoActividad: string
{
    case Bodega = 'Bodega';
    case Evento = 'Evento';
    case Transporte = 'Transporte';
}

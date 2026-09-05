<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivote evento_unidades con datos extra por asignación: el colaborador que conduce
 * la unidad durante el evento (cualquier colaborador asignado salvo Freelance).
 */
class EventoUnidad extends Pivot
{
    protected $table = 'evento_unidades';

    /** @return BelongsTo<Colaborador, $this> */
    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'conductor_colaborador_id')->withTrashed();
    }

    /** @return BelongsTo<TransporteUnidad, $this> */
    public function unidad(): BelongsTo
    {
        return $this->belongsTo(TransporteUnidad::class, 'transporte_unidad_id');
    }
}

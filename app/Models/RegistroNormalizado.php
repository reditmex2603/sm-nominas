<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroNormalizado extends Model
{
    protected $table = 'registros_normalizados';

    protected $fillable = [
        'colaborador_id',
        'tipo_actividad',
        'actividad',
        'evento_raw',
        'etapa',
        'vehiculo',
        'distancia',
        'transporte_unidad_id',
        'origen',
        'destino',
        'extras',
        'evidencia_path',
        'comentarios',
        'fecha',
        'hora',
        'hora_salida',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class)->withTrashed();
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(TransporteUnidad::class, 'transporte_unidad_id');
    }
}

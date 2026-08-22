<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JornadaConsolidada extends Model
{
    protected $table = 'jornadas_consolidadas';

    protected $fillable = [
        'colaborador_id',
        'fecha',
        'entrada',
        'salida',
        'actividades',
        'detalle',
        'extras',
        'evidencias',
        'comentarios',
        'validado',
        'tipo_pago',
        'traslape_pct',
        'fracciones_evento',
        'compensacion_activa',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'actividades' => 'array',
            'validado' => 'boolean',
            'traslape_pct' => 'integer',
            'fracciones_evento' => 'array',
            'compensacion_activa' => 'boolean',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class)->withTrashed();
    }
}

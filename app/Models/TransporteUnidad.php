<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransporteUnidad extends Model
{
    protected $table = 'transporte_unidades';

    protected $fillable = [
        'marca',
        'modelo',
        'numero_placas',
        'pertenencia',
        'transporte_vehiculo_id',
        'alias',
        'numero_serie',
        'placas_documento_path',
        'tarjeta_circulacion_documento_path',
        'poliza_seguro_documento_path',
        'numero_poliza_seguro',
        'vigencia_poliza_seguro',
        'vigencia_verificacion',
        'tipo_engomado',
        'color_engomado',
        'verificacion_documento_path',
        'tenencia_documento_path',
        'fotografia_path',
    ];

    protected function casts(): array
    {
        return [
            'vigencia_poliza_seguro' => 'date',
            'vigencia_verificacion' => 'date',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(TransporteVehiculo::class, 'transporte_vehiculo_id');
    }
}

<?php

namespace App\Models;

use Database\Factories\TransporteUnidadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $marca
 * @property string $modelo
 * @property string|null $numero_placas
 * @property string $pertenencia
 * @property int|null $transporte_vehiculo_id
 * @property string|null $alias
 * @property string|null $numero_serie
 * @property string|null $numero_poliza_seguro
 * @property Carbon|null $vigencia_poliza_seguro
 * @property Carbon|null $vigencia_verificacion
 * @property string|null $tipo_engomado
 * @property string|null $color_engomado
 * @property string|null $placas_documento_path
 * @property string|null $tarjeta_circulacion_documento_path
 * @property string|null $poliza_seguro_documento_path
 * @property string|null $verificacion_documento_path
 * @property string|null $tenencia_documento_path
 * @property string|null $fotografia_path
 */
class TransporteUnidad extends Model
{
    /** @use HasFactory<TransporteUnidadFactory> */
    use HasFactory;

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

    /** @return BelongsTo<TransporteVehiculo, $this> */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(TransporteVehiculo::class, 'transporte_vehiculo_id');
    }
}

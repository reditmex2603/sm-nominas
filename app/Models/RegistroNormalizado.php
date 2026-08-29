<?php

namespace App\Models;

use App\Enums\TipoActividad;
use Database\Factories\RegistroNormalizadoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $colaborador_id
 * @property TipoActividad $tipo_actividad
 * @property string|null $actividad
 * @property string|null $evento_raw
 * @property string|null $etapa
 * @property string|null $vehiculo
 * @property string|null $distancia
 * @property int|null $transporte_unidad_id
 * @property string|null $origen
 * @property string|null $destino
 * @property string|null $extras
 * @property string|null $evidencia_path
 * @property string|null $comentarios
 * @property Carbon $fecha
 * @property string $hora
 * @property string|null $hora_salida
 */
class RegistroNormalizado extends Model
{
    /** @use HasFactory<RegistroNormalizadoFactory> */
    use HasFactory;

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
            'tipo_actividad' => TipoActividad::class,
        ];
    }

    /** @return BelongsTo<Colaborador, $this> */
    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class)->withTrashed();
    }

    /** @return BelongsTo<TransporteUnidad, $this> */
    public function unidad(): BelongsTo
    {
        return $this->belongsTo(TransporteUnidad::class, 'transporte_unidad_id');
    }
}

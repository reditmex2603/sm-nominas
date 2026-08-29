<?php

namespace App\Models;

use App\Enums\TipoPago;
use Database\Factories\JornadaConsolidadaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $colaborador_id
 * @property Carbon $fecha
 * @property string|null $entrada
 * @property string|null $salida
 * @property array<int, string>|null $actividades
 * @property string|null $detalle
 * @property string|null $extras
 * @property string|null $evidencias
 * @property string|null $comentarios
 * @property bool $validado
 * @property TipoPago $tipo_pago
 * @property int|null $traslape_pct
 * @property array<int, int>|null $fracciones_evento
 * @property bool $compensacion_activa
 */
class JornadaConsolidada extends Model
{
    /** @use HasFactory<JornadaConsolidadaFactory> */
    use HasFactory;

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
            'tipo_pago' => TipoPago::class,
        ];
    }

    /** @return BelongsTo<Colaborador, $this> */
    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class)->withTrashed();
    }
}

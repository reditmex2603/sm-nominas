<?php

namespace App\Models;

use App\Enums\EstadoNomina;
use App\Enums\TipoColaborador;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $colaborador_id
 * @property TipoColaborador $tipo_colaborador
 * @property Carbon|null $periodo_inicio
 * @property Carbon|null $periodo_fin
 * @property string|null $dias
 * @property string|null $sueldo_diario
 * @property string $total_base
 * @property string $bonos_evento
 * @property string $compensaciones
 * @property string|null $comentario
 * @property string $anticipos
 * @property string $prestamos
 * @property string $total_final
 * @property EstadoNomina $estado
 * @property int|null $evento_id
 * @property Carbon $fecha_calculo
 * @property array<string, mixed>|null $desglose
 */
class HistoricoNomina extends Model
{
    use HasFactory;

    protected $table = 'historico_nomina';

    protected $fillable = [
        'colaborador_id',
        'tipo_colaborador',
        'periodo_inicio',
        'periodo_fin',
        'dias',
        'sueldo_diario',
        'total_base',
        'bonos_evento',
        'compensaciones',
        'comentario',
        'anticipos',
        'prestamos',
        'total_final',
        'estado',
        'evento_id',
        'fecha_calculo',
        'desglose',
    ];

    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'date',
            'periodo_fin' => 'date',
            'dias' => 'decimal:2',
            'sueldo_diario' => 'decimal:2',
            'total_base' => 'decimal:2',
            'bonos_evento' => 'decimal:2',
            'compensaciones' => 'decimal:2',
            'anticipos' => 'decimal:2',
            'total_final' => 'decimal:2',
            'fecha_calculo' => 'datetime',
            'desglose' => 'array',
            'tipo_colaborador' => TipoColaborador::class,
            'estado' => EstadoNomina::class,
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class)->withTrashed();
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }
}

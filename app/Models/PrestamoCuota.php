<?php

namespace App\Models;

use App\Enums\EstadoCuota;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $prestamo_id
 * @property int $numero_plazo
 * @property string $monto
 * @property Carbon $fecha_programada
 * @property EstadoCuota $estado
 * @property Carbon|null $fecha_pago
 * @property int|null $historico_nomina_id
 */
class PrestamoCuota extends Model
{
    use HasFactory;

    protected $table = 'prestamo_cuotas';

    protected $fillable = [
        'prestamo_id',
        'numero_plazo',
        'monto',
        'fecha_programada',
        'estado',
        'fecha_pago',
        'historico_nomina_id',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_programada' => 'date',
            'fecha_pago' => 'date',
            'estado' => EstadoCuota::class,
        ];
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function historicoNomina(): BelongsTo
    {
        return $this->belongsTo(HistoricoNomina::class);
    }
}

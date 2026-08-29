<?php

namespace App\Models;

use Database\Factories\AnticipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $colaborador_id
 * @property string|null $concepto
 * @property string $tipo
 * @property int|null $evento_id
 * @property string $monto
 * @property Carbon $fecha
 * @property string|null $entregado_por
 */
class Anticipo extends Model
{
    /** @use HasFactory<AnticipoFactory> */
    use HasFactory;

    protected $fillable = [
        'colaborador_id',
        'concepto',
        'tipo',
        'evento_id',
        'monto',
        'fecha',
        'entregado_por',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

    /** @return BelongsTo<Colaborador, $this> */
    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class)->withTrashed();
    }

    /** @return BelongsTo<Evento, $this> */
    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }
}

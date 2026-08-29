<?php

namespace App\Models;

use Database\Factories\AsignacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $evento_id
 * @property int $colaborador_id
 */
class Asignacion extends Model
{
    /** @use HasFactory<AsignacionFactory> */
    use HasFactory;

    protected $table = 'asignaciones';

    protected $fillable = [
        'evento_id',
        'colaborador_id',
    ];

    /** @return BelongsTo<Evento, $this> */
    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    /** @return BelongsTo<Colaborador, $this> */
    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class)->withTrashed();
    }
}

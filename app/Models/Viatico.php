<?php

namespace App\Models;

use Database\Factories\ViaticoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Viatico extends Model
{
    /** @use HasFactory<ViaticoFactory> */
    use HasFactory;

    protected $table = 'viaticos';

    protected $fillable = [
        'nombre',
        'apellidos',
        'tipo',
        'evento_id',
        'colaborador_id',
        'concepto',
        'monto',
        'fecha',
        'autoriza',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

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

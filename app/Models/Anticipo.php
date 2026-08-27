<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anticipo extends Model
{
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

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class)->withTrashed();
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }
}

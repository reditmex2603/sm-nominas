<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prestamo extends Model
{
    use HasFactory;

    protected $fillable = [
        'colaborador_id',
        'monto_total',
        'num_plazos',
        'periodicidad',
        'fecha_inicio',
        'concepto',
        'autoriza',
    ];

    protected function casts(): array
    {
        return [
            'monto_total' => 'decimal:2',
            'fecha_inicio' => 'date',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class)->withTrashed();
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(PrestamoCuota::class)->orderBy('numero_plazo');
    }
}

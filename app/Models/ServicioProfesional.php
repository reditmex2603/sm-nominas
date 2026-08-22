<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioProfesional extends Model
{
    protected $table = 'servicios_profesionales';

    protected $fillable = [
        'nombre',
        'apellidos',
        'tipo',
        'evento_id',
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

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }
}

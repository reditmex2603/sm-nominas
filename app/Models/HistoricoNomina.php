<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

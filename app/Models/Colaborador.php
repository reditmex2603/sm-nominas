<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Colaborador extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'colaboradores';

    protected $fillable = [
        'nombre',
        'apellidos',
        'tipo',
        'categoria',
        'nivel',
        'compensacion_pct',
        'sueldo_diario',
        'extra_dia_adicional',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'sueldo_diario' => 'decimal:2',
            'extra_dia_adicional' => 'decimal:2',
            'nivel' => 'integer',
            'compensacion_pct' => 'integer',
        ];
    }

    public function eventos(): BelongsToMany
    {
        return $this->belongsToMany(Evento::class, 'asignaciones');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroNormalizado::class);
    }

    public function jornadas(): HasMany
    {
        return $this->hasMany(JornadaConsolidada::class);
    }

    public function anticipos(): HasMany
    {
        return $this->hasMany(Anticipo::class);
    }

    public function nominas(): HasMany
    {
        return $this->hasMany(HistoricoNomina::class);
    }

    public function perfil(): HasOne
    {
        return $this->hasOne(ColaboradorPerfil::class);
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }
}

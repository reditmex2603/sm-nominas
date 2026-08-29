<?php

namespace App\Models;

use App\Enums\CategoriaColaborador;
use App\Enums\TipoColaborador;
use Database\Factories\ColaboradorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $nombre
 * @property string $apellidos
 * @property TipoColaborador $tipo
 * @property CategoriaColaborador|null $categoria
 * @property int|null $nivel
 * @property int $compensacion_pct
 * @property string|null $sueldo_diario
 * @property string|null $extra_dia_adicional
 * @property string $token
 */
class Colaborador extends Model
{
    /** @use HasFactory<ColaboradorFactory> */
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
            'tipo' => TipoColaborador::class,
            'categoria' => CategoriaColaborador::class,
        ];
    }

    /** @return BelongsToMany<Evento, $this> */
    public function eventos(): BelongsToMany
    {
        return $this->belongsToMany(Evento::class, 'asignaciones');
    }

    /** @return HasMany<Asignacion, $this> */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }

    /** @return HasMany<RegistroNormalizado, $this> */
    public function registros(): HasMany
    {
        return $this->hasMany(RegistroNormalizado::class);
    }

    /** @return HasMany<JornadaConsolidada, $this> */
    public function jornadas(): HasMany
    {
        return $this->hasMany(JornadaConsolidada::class);
    }

    /** @return HasMany<Anticipo, $this> */
    public function anticipos(): HasMany
    {
        return $this->hasMany(Anticipo::class);
    }

    /** @return HasMany<HistoricoNomina, $this> */
    public function nominas(): HasMany
    {
        return $this->hasMany(HistoricoNomina::class);
    }

    /** @return HasOne<ColaboradorPerfil, $this> */
    public function perfil(): HasOne
    {
        return $this->hasOne(ColaboradorPerfil::class);
    }

    /** @return HasMany<Prestamo, $this> */
    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }
}

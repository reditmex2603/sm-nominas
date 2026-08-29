<?php

namespace App\Models;

use App\Enums\TamanoEvento;
use Database\Factories\EventoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $nombre
 * @property string|null $lugar
 * @property Carbon|null $fecha_inicio
 * @property Carbon|null $fecha_fin
 * @property TamanoEvento $tamano
 * @property string|null $pago_por_evento_completo
 * @property array<string, mixed>|null $requisitos_cotizacion
 */
class Evento extends Model
{
    /** @use HasFactory<EventoFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'lugar',
        'fecha_inicio',
        'fecha_fin',
        'tamano',
        'pago_por_evento_completo',
        'requisitos_cotizacion',
        'nombre_contratante',
        'telefono_contratante',
        'contacto_nombre',
        'contacto_telefono',
        'enlace_ubicacion',
        'descripcion',
        'observaciones_tecnicas',
        'viatico_diario',
    ];

    protected function casts(): array
    {
        return [
            'pago_por_evento_completo' => 'decimal:2',
            'viatico_diario' => 'decimal:2',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'requisitos_cotizacion' => 'array',
            'tamano' => TamanoEvento::class,
        ];
    }

    /** @return BelongsToMany<Colaborador, $this> */
    public function colaboradores(): BelongsToMany
    {
        return $this->belongsToMany(Colaborador::class, 'asignaciones');
    }

    /** @return BelongsToMany<TransporteUnidad, $this> */
    public function unidadesTransporte(): BelongsToMany
    {
        return $this->belongsToMany(TransporteUnidad::class, 'evento_unidades');
    }

    /** @return HasMany<Asignacion, $this> */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }

    /** @return HasMany<HistoricoNomina, $this> */
    public function nominas(): HasMany
    {
        return $this->hasMany(HistoricoNomina::class);
    }

    /** @return HasMany<ServicioProfesional, $this> */
    public function serviciosProfesionales(): HasMany
    {
        return $this->hasMany(ServicioProfesional::class);
    }

    /** @return HasMany<Viatico, $this> */
    public function viaticos(): HasMany
    {
        return $this->hasMany(Viatico::class);
    }

    /**
     * Todos los eventos distintos mencionados en el detalle de una jornada (líneas
     * "Evento: Nombre - Etapa"). Un mismo día puede tener más de uno.
     *
     * @return Collection<int, Evento>
     */
    public static function extraerDeDetalle(string $detalle): Collection
    {
        preg_match_all('/^Evento: (.+?) - /m', $detalle, $m);

        return collect($m[1])
            ->unique()
            ->map(fn ($nombre) => static::where('nombre', $nombre)->first())
            ->filter()
            ->values();
    }
}

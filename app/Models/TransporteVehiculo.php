<?php

namespace App\Models;

use Database\Factories\TransporteVehiculoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransporteVehiculo extends Model
{
    /** @use HasFactory<TransporteVehiculoFactory> */
    use HasFactory;

    protected $table = 'transportes_vehiculos';

    protected $fillable = ['nombre', 'orden'];

    /** @return HasMany<TransporteTarifa, $this> */
    public function tarifas(): HasMany
    {
        return $this->hasMany(TransporteTarifa::class, 'vehiculo_id');
    }

    /** @return BelongsToMany<TransporteDistancia, $this> */
    public function distancias(): BelongsToMany
    {
        return $this->belongsToMany(
            TransporteDistancia::class,
            'transportes_tarifas',
            'vehiculo_id',
            'distancia_id'
        )->withPivot('tarifa');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TransporteVehiculo extends Model
{
    protected $table = 'transportes_vehiculos';

    protected $fillable = ['nombre', 'orden'];

    public function tarifas(): HasMany
    {
        return $this->hasMany(TransporteTarifa::class, 'vehiculo_id');
    }

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

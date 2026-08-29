<?php

namespace App\Models;

use Database\Factories\TransporteDistanciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransporteDistancia extends Model
{
    /** @use HasFactory<TransporteDistanciaFactory> */
    use HasFactory;

    protected $table = 'transportes_distancias';

    protected $fillable = ['nombre', 'es_standby', 'orden'];

    protected function casts(): array
    {
        return [
            'es_standby' => 'boolean',
        ];
    }

    /** @return HasMany<TransporteTarifa, $this> */
    public function tarifas(): HasMany
    {
        return $this->hasMany(TransporteTarifa::class, 'distancia_id');
    }
}

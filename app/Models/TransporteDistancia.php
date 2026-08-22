<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransporteDistancia extends Model
{
    protected $table = 'transportes_distancias';

    protected $fillable = ['nombre', 'es_standby', 'orden'];

    protected function casts(): array
    {
        return [
            'es_standby' => 'boolean',
        ];
    }

    public function tarifas(): HasMany
    {
        return $this->hasMany(TransporteTarifa::class, 'distancia_id');
    }
}

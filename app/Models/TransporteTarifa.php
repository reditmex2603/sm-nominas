<?php

namespace App\Models;

use Database\Factories\TransporteTarifaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransporteTarifa extends Model
{
    /** @use HasFactory<TransporteTarifaFactory> */
    use HasFactory;

    protected $table = 'transportes_tarifas';

    protected $fillable = ['vehiculo_id', 'distancia_id', 'tarifa'];

    protected function casts(): array
    {
        return [
            'tarifa' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<TransporteVehiculo, $this> */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(TransporteVehiculo::class, 'vehiculo_id');
    }

    /** @return BelongsTo<TransporteDistancia, $this> */
    public function distancia(): BelongsTo
    {
        return $this->belongsTo(TransporteDistancia::class, 'distancia_id');
    }
}

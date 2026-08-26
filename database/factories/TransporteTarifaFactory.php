<?php

namespace Database\Factories;

use App\Models\TransporteDistancia;
use App\Models\TransporteTarifa;
use App\Models\TransporteVehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransporteTarifa>
 */
class TransporteTarifaFactory extends Factory
{
    protected $model = TransporteTarifa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehiculo_id' => TransporteVehiculo::factory(),
            'distancia_id' => TransporteDistancia::factory(),
            'tarifa' => 1200.00,
        ];
    }
}

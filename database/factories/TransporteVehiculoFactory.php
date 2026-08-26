<?php

namespace Database\Factories;

use App\Models\TransporteVehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransporteVehiculo>
 */
class TransporteVehiculoFactory extends Factory
{
    protected $model = TransporteVehiculo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true),
            'orden' => 0,
        ];
    }
}

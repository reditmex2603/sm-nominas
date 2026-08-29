<?php

namespace Database\Factories;

use App\Models\TransporteUnidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransporteUnidad>
 */
class TransporteUnidadFactory extends Factory
{
    protected $model = TransporteUnidad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'marca' => fake()->company(),
            'modelo' => fake()->word(),
            'numero_placas' => fake()->bothify('???-####'),
            'pertenencia' => 'PROPIA',
        ];
    }
}

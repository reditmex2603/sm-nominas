<?php

namespace Database\Factories;

use App\Models\ServicioProfesional;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicioProfesional>
 */
class ServicioProfesionalFactory extends Factory
{
    protected $model = ServicioProfesional::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'tipo' => 'OTRO',
            'evento_id' => null,
            'concepto' => fake()->sentence(3),
            'monto' => 1500.00,
            'fecha' => fake()->date(),
            'autoriza' => fake()->name(),
        ];
    }
}

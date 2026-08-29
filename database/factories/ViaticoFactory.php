<?php

namespace Database\Factories;

use App\Models\Evento;
use App\Models\Viatico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Viatico>
 */
class ViaticoFactory extends Factory
{
    protected $model = Viatico::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'apellidos' => fake()->lastName(),
            'tipo' => 'ALIMENTOS',
            'evento_id' => Evento::factory(),
            'colaborador_id' => null,
            'concepto' => fake()->sentence(3),
            'monto' => 200.00,
            'fecha' => fake()->date(),
            'autoriza' => fake()->name(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\TransporteDistancia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransporteDistancia>
 */
class TransporteDistanciaFactory extends Factory
{
    protected $model = TransporteDistancia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->numerify('###-###km'),
            'es_standby' => false,
            'orden' => 0,
        ];
    }

    public function standby(): static
    {
        return $this->state(fn () => ['es_standby' => true]);
    }
}

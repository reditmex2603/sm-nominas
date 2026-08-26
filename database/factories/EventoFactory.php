<?php

namespace Database\Factories;

use App\Models\Evento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evento>
 */
class EventoFactory extends Factory
{
    protected $model = Evento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->company().' '.fake()->unique()->numerify('###'),
            'lugar' => fake()->city(),
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'tamano' => 'MEDIANO',
            'pago_por_evento_completo' => 2500.00,
        ];
    }

    public function chico(): static
    {
        return $this->state(fn () => ['tamano' => 'CHICO']);
    }

    public function mediano(): static
    {
        return $this->state(fn () => ['tamano' => 'MEDIANO']);
    }

    public function grande(): static
    {
        return $this->state(fn () => ['tamano' => 'GRANDE']);
    }
}

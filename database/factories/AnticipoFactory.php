<?php

namespace Database\Factories;

use App\Models\Anticipo;
use App\Models\Colaborador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Anticipo>
 */
class AnticipoFactory extends Factory
{
    protected $model = Anticipo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'colaborador_id' => Colaborador::factory(),
            'concepto' => fake()->sentence(3),
            'tipo' => 'SUELTO',
            'evento_id' => null,
            'monto' => 500.00,
            'fecha' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'entregado_por' => fake()->name(),
        ];
    }

    public function enFecha(string $fecha): static
    {
        return $this->state(fn () => ['fecha' => $fecha]);
    }
}

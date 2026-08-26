<?php

namespace Database\Factories;

use App\Models\Colaborador;
use App\Models\Prestamo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prestamo>
 */
class PrestamoFactory extends Factory
{
    protected $model = Prestamo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'colaborador_id' => Colaborador::factory(),
            'monto_total' => 3000.00,
            'num_plazos' => 3,
            'periodicidad' => 'SEMANAL',
            'fecha_inicio' => now()->format('Y-m-d'),
            'concepto' => fake()->sentence(3),
            'autoriza' => fake()->name(),
        ];
    }
}

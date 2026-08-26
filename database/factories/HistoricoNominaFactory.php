<?php

namespace Database\Factories;

use App\Models\Colaborador;
use App\Models\HistoricoNomina;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HistoricoNomina>
 */
class HistoricoNominaFactory extends Factory
{
    protected $model = HistoricoNomina::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'colaborador_id' => Colaborador::factory(),
            'tipo_colaborador' => 'COLABORADOR BASE',
            'periodo_inicio' => now()->startOfWeek()->format('Y-m-d'),
            'periodo_fin' => now()->endOfWeek()->format('Y-m-d'),
            'dias' => 6,
            'sueldo_diario' => 500.00,
            'total_base' => 3000.00,
            'bonos_evento' => 0,
            'compensaciones' => 0,
            'anticipos' => 0,
            'total_final' => 3000.00,
            'estado' => 'PENDIENTE',
            'evento_id' => null,
        ];
    }

    public function pagada(): static
    {
        return $this->state(fn () => ['estado' => 'PAGADO']);
    }
}

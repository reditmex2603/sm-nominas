<?php

namespace Database\Factories;

use App\Models\Asignacion;
use App\Models\Colaborador;
use App\Models\Evento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asignacion>
 */
class AsignacionFactory extends Factory
{
    protected $model = Asignacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'evento_id' => Evento::factory(),
            'colaborador_id' => Colaborador::factory(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Colaborador;
use App\Models\ColaboradorDatoBancario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ColaboradorDatoBancario>
 */
class ColaboradorDatoBancarioFactory extends Factory
{
    protected $model = ColaboradorDatoBancario::class;

    public function definition(): array
    {
        return [
            'colaborador_id' => Colaborador::factory(),
            'banco' => 'BBVA',
            'beneficiario' => fake()->name(),
            'clave_interbancaria' => '012345678901234567',
            'numero_tarjeta' => '1234567890123456',
            'alias' => fake()->word(),
            'comentario' => null,
        ];
    }
}

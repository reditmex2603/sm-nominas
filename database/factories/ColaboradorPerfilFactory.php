<?php

namespace Database\Factories;

use App\Models\Colaborador;
use App\Models\ColaboradorPerfil;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ColaboradorPerfil>
 */
class ColaboradorPerfilFactory extends Factory
{
    protected $model = ColaboradorPerfil::class;

    public function definition(): array
    {
        return [
            'colaborador_id' => Colaborador::factory(),
            'fecha_ingreso' => fake()->date(),
            'correo' => fake()->safeEmail(),
            'telefono' => '5512345678',
            'whatsapp' => '5512345678',
        ];
    }

    public function conDatosBancarios(): static
    {
        return $this->state(fn () => [
            'banco' => 'BBVA',
            'beneficiario' => fake()->name(),
            'clave_interbancaria' => '012345678901234567',
            'numero_seguro_social' => '12345678901',
        ]);
    }
}

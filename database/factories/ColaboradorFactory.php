<?php

namespace Database\Factories;

use App\Models\Colaborador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Colaborador>
 */
class ColaboradorFactory extends Factory
{
    protected $model = Colaborador::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'tipo' => 'COLABORADOR BASE',
            'categoria' => 'Técnico',
            'nivel' => 1,
            'compensacion_pct' => 0,
            'sueldo_diario' => 500.00,
            'extra_dia_adicional' => null,
        ];
    }

    /** Base con categoría y nivel configurables (para bonos de evento). */
    public function base(?string $categoria = 'Técnico', ?int $nivel = 1): static
    {
        return $this->state(fn () => [
            'tipo' => 'COLABORADOR BASE',
            'categoria' => $categoria,
            'nivel' => $nivel,
        ]);
    }

    public function freelance(): static
    {
        return $this->state(fn () => [
            'tipo' => 'FREELANCE',
            'categoria' => null,
            'nivel' => null,
            'sueldo_diario' => null,
            'extra_dia_adicional' => 300.00,
        ]);
    }

    public function conductor(): static
    {
        return $this->state(fn () => [
            'tipo' => 'CONDUCTOR',
            'categoria' => null,
            'nivel' => null,
            'sueldo_diario' => null,
        ]);
    }

    public function conductorBase(): static
    {
        return $this->state(fn () => [
            'tipo' => 'CONDUCTOR BASE',
            'categoria' => null,
            'nivel' => null,
        ]);
    }
}

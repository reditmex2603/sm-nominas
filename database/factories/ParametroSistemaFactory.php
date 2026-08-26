<?php

namespace Database\Factories;

use App\Models\ParametroSistema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParametroSistema>
 */
class ParametroSistemaFactory extends Factory
{
    protected $model = ParametroSistema::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clave' => fake()->unique()->slug(2),
            'valor' => '0',
            'descripcion' => null,
        ];
    }
}

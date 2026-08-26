<?php

namespace Database\Factories;

use App\Models\Colaborador;
use App\Models\RegistroNormalizado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistroNormalizado>
 */
class RegistroNormalizadoFactory extends Factory
{
    protected $model = RegistroNormalizado::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'colaborador_id' => Colaborador::factory(),
            'tipo_actividad' => 'Bodega',
            'actividad' => 'Carga de equipo',
            'fecha' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'hora' => '09:00:00',
        ];
    }

    public function bodega(?string $actividad = 'Carga de equipo'): static
    {
        return $this->state(fn () => [
            'tipo_actividad' => 'Bodega',
            'actividad' => $actividad,
        ]);
    }

    public function evento(string $eventoRaw, string $etapa = 'Show'): static
    {
        return $this->state(fn () => [
            'tipo_actividad' => 'Evento',
            'actividad' => null,
            'evento_raw' => $eventoRaw,
            'etapa' => $etapa,
        ]);
    }

    public function transporte(string $vehiculo = 'Camión', string $distancia = '100-200km'): static
    {
        return $this->state(fn () => [
            'tipo_actividad' => 'Transporte',
            'actividad' => null,
            'vehiculo' => $vehiculo,
            'distancia' => $distancia,
            'origen' => 'Bodega',
            'destino' => 'Sede',
        ]);
    }

    public function enFecha(string $fecha, string $hora = '09:00:00', ?string $horaSalida = null): static
    {
        return $this->state(fn () => [
            'fecha' => $fecha,
            'hora' => $hora,
            'hora_salida' => $horaSalida,
        ]);
    }
}

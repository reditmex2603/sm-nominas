<?php

namespace Database\Factories;

use App\Models\Colaborador;
use App\Models\JornadaConsolidada;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JornadaConsolidada>
 */
class JornadaConsolidadaFactory extends Factory
{
    protected $model = JornadaConsolidada::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'colaborador_id' => Colaborador::factory(),
            'fecha' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'entrada' => '09:00:00',
            'salida' => '18:00:00',
            'actividades' => ['Bodega'],
            'detalle' => 'Bodega: Carga de equipo',
            'validado' => true,
            'tipo_pago' => 'JORNADA_COMPLETA',
        ];
    }

    public function sinValidar(): static
    {
        return $this->state(fn () => ['validado' => false]);
    }

    public function sinPago(): static
    {
        return $this->state(fn () => ['tipo_pago' => 'SIN_PAGO']);
    }

    public function errorEvento(): static
    {
        return $this->state(fn () => ['tipo_pago' => 'ERROR_EVENTO']);
    }

    /** Día con evento pagable (bono de categoría según tamaño). */
    public function conEvento(string $detalle): static
    {
        return $this->state(fn () => [
            'tipo_pago' => 'JORNADA_COMPLETA + EVENTO',
            'detalle' => $detalle,
            'actividades' => ['Evento'],
        ]);
    }

    /** Traslape: paga traslape_pct% de la tarifa/bono del día. */
    public function traslape(int $pct, string $detalle): static
    {
        return $this->state(fn () => [
            'tipo_pago' => 'TRASLAPE',
            'traslape_pct' => $pct,
            'detalle' => $detalle,
            'actividades' => ['Evento'],
        ]);
    }

    public function enFecha(string $fecha): static
    {
        return $this->state(fn () => ['fecha' => $fecha]);
    }

    public function conCompensacion(): static
    {
        return $this->state(fn () => ['compensacion_activa' => true]);
    }
}

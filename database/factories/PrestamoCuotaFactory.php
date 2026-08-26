<?php

namespace Database\Factories;

use App\Models\Prestamo;
use App\Models\PrestamoCuota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrestamoCuota>
 */
class PrestamoCuotaFactory extends Factory
{
    protected $model = PrestamoCuota::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prestamo_id' => Prestamo::factory(),
            'numero_plazo' => 1,
            'monto' => 1000.00,
            'fecha_programada' => now()->format('Y-m-d'),
            'estado' => 'PENDIENTE',
            'fecha_pago' => null,
            'historico_nomina_id' => null,
        ];
    }

    public function pagada(?string $fechaPago = null): static
    {
        return $this->state(fn () => [
            'estado' => 'PAGADA',
            'fecha_pago' => $fechaPago ?? now()->format('Y-m-d'),
        ]);
    }

    public function programadaPara(string $fecha): static
    {
        return $this->state(fn () => ['fecha_programada' => $fecha]);
    }
}

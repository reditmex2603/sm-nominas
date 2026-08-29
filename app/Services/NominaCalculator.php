<?php

namespace App\Services;

use App\Enums\CategoriaColaborador;
use App\Models\Colaborador;
use App\Models\Evento;
use App\Services\Calculadores\CalculadorBase;
use App\Services\Calculadores\CalculadorConductor;
use App\Services\Calculadores\CalculadorConductorBase;
use App\Services\Calculadores\CalculadorFreelance;
use Illuminate\Support\Carbon;

/**
 * Orquestador de cálculo de nómina. Delega en la estrategia según el tipo de colaborador
 * (Base, Freelance, Conductor, Conductor Base). Mantiene la API pública histórica para
 * no romper los consumidores (NominaController, EventoController) ni los tests.
 */
class NominaCalculator
{
    public function __construct(
        private readonly CalculadorBase $base,
        private readonly CalculadorFreelance $freelance,
        private readonly CalculadorConductor $conductor,
        private readonly CalculadorConductorBase $conductorBase,
    ) {}

    public function calcularBase(Colaborador $col, Carbon $inicio, Carbon $fin, float $compensacion = 0): array
    {
        return $this->base->calcular($col, $inicio, $fin, $compensacion);
    }

    public function calcularFreelance(
        Colaborador $col,
        Evento $evento,
        int $diasAdicionales = 0,
        float $compensacion = 0,
    ): array {
        return $this->freelance->calcular($col, $evento, $diasAdicionales, $compensacion);
    }

    public function calcularConductor(Colaborador $col, Carbon $inicio, Carbon $fin, float $compensacion = 0): array
    {
        return $this->conductor->calcular($col, $inicio, $fin, $compensacion);
    }

    public function calcularConductorBase(Colaborador $col, Carbon $inicio, Carbon $fin, float $compensacion = 0): array
    {
        return $this->conductorBase->calcular($col, $inicio, $fin, $compensacion);
    }

    /**
     * Monto del extra de evento (Base) según categoría, nivel y tamaño de evento. CHICO = $0
     * siempre. Público porque también lo usa `EventoController` para la cotización de nómina
     * (mismos parámetros, una sola fuente de verdad).
     */
    public function extraCategoriaDelEvento(CategoriaColaborador|string|null $categoria, ?int $nivel, ?Evento $evento): float
    {
        return $this->base->extraCategoriaDelEvento($categoria, $nivel, $evento);
    }
}

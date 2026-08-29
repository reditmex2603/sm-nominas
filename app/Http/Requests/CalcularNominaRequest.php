<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalcularNominaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validación condicional según el tipo de colaborador:
     * - Base/Conductor/ConductorBase: periodo por fechas.
     * - Freelance: evento + días adicionales.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tipo = $this->input('tipo');

        if (in_array($tipo, ['COLABORADOR BASE', 'CONDUCTOR', 'CONDUCTOR BASE'], true)) {
            return [
                'tipo' => 'required|string',
                'colaborador_id' => 'required|exists:colaboradores,id',
                'inicio' => 'required|date',
                'fin' => 'required|date|after_or_equal:inicio',
                'compensacion' => 'nullable|numeric',
            ];
        }

        if ($tipo === 'FREELANCE') {
            return [
                'tipo' => 'required|string',
                'colaborador_id' => 'required|exists:colaboradores,id',
                'evento_id' => 'required|exists:eventos,id',
                'dias_adicionales' => 'nullable|integer|min:0',
                'compensacion' => 'nullable|numeric',
            ];
        }

        return [
            'tipo' => 'required|string',
        ];
    }
}

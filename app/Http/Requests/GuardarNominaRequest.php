<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarNominaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Igual que CalcularNominaRequest + comentario opcional.
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
                'comentario' => 'nullable|string|max:2000',
            ];
        }

        return [
            'tipo' => 'required|string',
            'colaborador_id' => 'required|exists:colaboradores,id',
            'evento_id' => 'required|exists:eventos,id',
            'dias_adicionales' => 'nullable|integer|min:0',
            'compensacion' => 'nullable|numeric',
            'comentario' => 'nullable|string|max:2000',
        ];
    }
}

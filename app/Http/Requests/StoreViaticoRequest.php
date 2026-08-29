<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreViaticoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Modo "Por colaborador": colaborador_id (debe estar asignado al evento elegido), sin
            // nombre libre. Modo "General": nombre libre, sin colaborador_id.
            'colaborador_id' => [
                'nullable',
                Rule::exists('asignaciones', 'colaborador_id')
                    ->where(fn ($q) => $q->where('evento_id', $this->input('evento_id'))),
            ],
            'nombre' => 'required_without:colaborador_id|nullable|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'tipo' => 'required|in:TRANSPORTE,HOSPEDAJE,ALIMENTOS,CASETAS_GASOLINA,OTRO',
            'evento_id' => 'required|exists:eventos,id',
            'concepto' => 'required|string|max:500',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
            'autoriza' => 'nullable|string|max:255',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnticipoRequest extends FormRequest
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
            'colaborador_id' => 'required|exists:colaboradores,id',
            'monto' => 'required|numeric|min:0.01',
            'concepto' => 'nullable|string|max:500',
            'tipo' => 'sometimes|in:EVENTO,SUELTO',
            'evento_id' => [
                'nullable',
                'required_if:tipo,EVENTO',
                Rule::exists('asignaciones', 'evento_id')
                    ->where('colaborador_id', $this->integer('colaborador_id')),
            ],
            'fecha' => 'nullable|date',
            'entregado_por' => 'nullable|string|max:255',
        ];
    }
}

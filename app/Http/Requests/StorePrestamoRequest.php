<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrestamoRequest extends FormRequest
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
            'monto_total' => 'required|numeric|min:0.01',
            'num_plazos' => 'required|integer|min:1|max:52',
            'periodicidad' => 'required|in:SEMANAL,QUINCENAL,MENSUAL',
            'fecha_inicio' => 'required|date',
            'concepto' => 'nullable|string|max:500',
            'autoriza' => 'nullable|string|max:255',
        ];
    }
}

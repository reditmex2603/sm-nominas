<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImprimirRangoHistoricoRequest extends FormRequest
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
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
            'tipo' => 'nullable|in:base,freelance,conductores,conductor_base',
            'colaborador_id' => 'nullable|integer|exists:colaboradores,id',
            'estado' => 'nullable|in:PENDIENTE,PAGADO',
        ];
    }
}

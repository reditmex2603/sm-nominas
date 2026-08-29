<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransporteUnidadRequest extends FormRequest
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
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'numero_placas' => 'nullable|string|max:50',
            'pertenencia' => 'required|in:PROPIA,RENTADA',
            'transporte_vehiculo_id' => 'nullable|exists:transportes_vehiculos,id',
        ];
    }
}

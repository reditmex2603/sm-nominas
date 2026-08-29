<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransporteRequest extends FormRequest
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
            'vehiculos' => 'required|array|min:1',
            'vehiculos.*.nombre' => 'required|string|max:255',
            'distancias' => 'required|array|min:1',
            'distancias.*.nombre' => 'required|string|max:255',
            'distancias.*.es_standby' => 'boolean',
            'tarifas' => 'required|array',
            'tarifas.*.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

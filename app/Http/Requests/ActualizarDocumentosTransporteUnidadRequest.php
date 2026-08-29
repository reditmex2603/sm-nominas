<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarDocumentosTransporteUnidadRequest extends FormRequest
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
            'alias' => 'nullable|string|max:255',
            'numero_serie' => 'nullable|string|max:50',
            'numero_poliza_seguro' => 'nullable|string|max:50',
            'vigencia_poliza_seguro' => 'nullable|date',
            'vigencia_verificacion' => 'nullable|date',
            'tipo_engomado' => 'nullable|string|max:50',
            'color_engomado' => 'nullable|string|max:50',
            'fotografia' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'placas_documento' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'tarjeta_circulacion_documento' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'poliza_seguro_documento' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'verificacion_documento' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'tenencia_documento' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}

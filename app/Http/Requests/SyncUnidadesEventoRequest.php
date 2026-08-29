<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncUnidadesEventoRequest extends FormRequest
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
            'unidad_ids' => 'present|array',
            'unidad_ids.*' => 'integer|exists:transporte_unidades,id',
        ];
    }
}

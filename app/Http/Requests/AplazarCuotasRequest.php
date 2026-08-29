<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AplazarCuotasRequest extends FormRequest
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
            'cuota_ids' => ['required', 'array', 'min:1'],
            'cuota_ids.*' => ['integer', 'exists:prestamo_cuotas,id'],
            'nueva_fecha' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}

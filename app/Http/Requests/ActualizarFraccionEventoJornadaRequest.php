<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarFraccionEventoJornadaRequest extends FormRequest
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
            'evento_id' => 'required|exists:eventos,id',
            'porcentaje' => 'required|integer|min:1|max:100',
        ];
    }
}

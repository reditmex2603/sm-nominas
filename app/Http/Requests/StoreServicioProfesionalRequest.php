<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicioProfesionalRequest extends FormRequest
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
            'nombre' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'tipo' => 'required|in:RIGGER,OPERADOR_AUDIO,OPERADOR_VIDEO,OPERADOR_LUZ,OTRO',
            'evento_id' => 'nullable|exists:eventos,id',
            'concepto' => 'required|string|max:500',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
            'autoriza' => 'nullable|string|max:255',
        ];
    }
}

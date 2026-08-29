<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ViaticosMatrizRequest extends FormRequest
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
            'def_diario' => 'nullable|numeric|min:0',
            'filas' => 'required|array',
            'filas.*.colaborador_id' => [
                'required',
                'integer',
                Rule::exists('asignaciones', 'colaborador_id')
                    ->where(fn ($q) => $q->where('evento_id', $this->input('evento_id'))),
            ],
            'filas.*.dias' => 'array',
            'filas.*.dias.*' => 'nullable|numeric|min:0',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreColaboradorRequest extends FormRequest
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
            'apellidos' => 'required|string|max:255',
            'tipo' => 'required|in:COLABORADOR BASE,FREELANCE,CONDUCTOR,CONDUCTOR BASE',
            'categoria' => 'required_if:tipo,COLABORADOR BASE|nullable|in:Encargado de área,Técnico,Stagehand SM',
            'nivel' => 'required_if:tipo,COLABORADOR BASE|nullable|integer|in:1,2',
            'compensacion_pct' => 'nullable|integer|min:0|max:100',
            'sueldo_diario' => 'required_if:tipo,CONDUCTOR BASE|nullable|numeric|min:0',
            'extra_dia_adicional' => 'nullable|numeric|min:0',
        ];
    }
}

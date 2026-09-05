<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarConductorUnidadEventoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Cualquier colaborador puede conducir una unidad asignada al evento, EXCEPTO un
     * colaborador tipo "Freelance".
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'conductor_colaborador_id' => [
                'nullable',
                'integer',
                Rule::exists('colaboradores', 'id')->whereNot('tipo', 'FREELANCE'),
            ],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Evento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarResponsableEventoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El responsable debe ser un colaborador asignado al evento.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Evento $evento */
        $evento = $this->route('evento');

        return [
            'responsable_colaborador_id' => [
                'nullable',
                'integer',
                Rule::exists('asignaciones', 'colaborador_id')->where('evento_id', $evento->id),
            ],
        ];
    }
}

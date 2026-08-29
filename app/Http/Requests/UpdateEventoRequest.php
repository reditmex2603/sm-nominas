<?php

namespace App\Http\Requests;

use App\Models\Evento;
use App\Rules\Telefono;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventoRequest extends FormRequest
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
        /** @var Evento $evento */
        $evento = $this->route('evento');

        return [
            'nombre' => 'sometimes|string|max:255|unique:eventos,nombre,'.$evento->id,
            'lugar' => 'nullable|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tamano' => 'sometimes|in:CHICO,MEDIANO,GRANDE',
            'pago_por_evento_completo' => 'sometimes|numeric|min:0',
            'nombre_contratante' => 'nullable|string|max:255',
            'telefono_contratante' => ['sometimes', 'nullable', new Telefono],
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_telefono' => ['sometimes', 'nullable', new Telefono],
            'descripcion' => 'nullable|string|max:5000',
            'observaciones_tecnicas' => 'nullable|string|max:5000',
            'enlace_ubicacion' => 'nullable|url|max:1000',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Rules\Telefono;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventoRequest extends FormRequest
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
            'nombre' => 'required|string|max:255|unique:eventos',
            'lugar' => 'nullable|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tamano' => 'required|in:CHICO,MEDIANO,GRANDE',
            'nombre_contratante' => 'nullable|string|max:255',
            'telefono_contratante' => ['nullable', new Telefono],
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_telefono' => ['nullable', new Telefono],
            'descripcion' => 'nullable|string|max:5000',
            'observaciones_tecnicas' => 'nullable|string|max:5000',
            'enlace_ubicacion' => 'nullable|url|max:1000',
        ];
    }
}

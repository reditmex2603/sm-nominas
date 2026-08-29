<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubirLogoMarcaRequest extends FormRequest
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
            // Sin SVG: los SVG pueden embebir scripts que se ejecutan en el
            // dominio del sitio cuando el usuario navega la imagen directamente.
            'archivo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}

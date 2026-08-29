<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarColoresMarcaRequest extends FormRequest
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
            'color_primario' => ['nullable', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'color_sidebar' => ['nullable', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarRequisitosEventoRequest extends FormRequest
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
            'base' => 'required|array',
            'base.*' => 'required|array',
            'base.*.*' => 'required|integer|min:0|max:99',
            'freelance' => 'required|integer|min:0|max:99',
        ];
    }
}

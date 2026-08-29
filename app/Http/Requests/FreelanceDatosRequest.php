<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FreelanceDatosRequest extends FormRequest
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
            'colaborador_id' => 'required|exists:colaboradores,id',
        ];
    }
}

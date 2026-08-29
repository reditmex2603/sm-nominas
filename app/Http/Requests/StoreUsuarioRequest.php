<?php

namespace App\Http\Requests;

use App\Support\Modulos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUsuarioRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', Password::defaults()],
            'rol' => 'required|in:supervisor,capturista',
            'permisos' => 'array',
            'permisos.*' => Rule::in(Modulos::claves()),
        ];
    }
}

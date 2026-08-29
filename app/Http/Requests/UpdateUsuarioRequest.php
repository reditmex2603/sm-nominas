<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\Modulos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUsuarioRequest extends FormRequest
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
        /** @var User $usuario */
        $usuario = $this->route('usuario');

        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password' => ['nullable', 'string', Password::defaults()],
            'rol' => 'required|in:supervisor,capturista',
            'permisos' => 'array',
            'permisos.*' => Rule::in(Modulos::claves()),
        ];
    }
}

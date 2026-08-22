<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida que un valor corresponda a un teléfono (México). Ignora espacios, guiones,
 * paréntesis y el prefijo "+52", y exige que queden entre 10 y 13 dígitos.
 */
class Telefono implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if (strlen($digits) < 10 || strlen($digits) > 13) {
            $fail('El campo :attribute debe ser un teléfono válido (10 a 13 dígitos).');
        }
    }
}

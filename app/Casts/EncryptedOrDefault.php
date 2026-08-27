<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;

/**
 * Cast que acepta texto plano o cifrado al leer (migración segura), y siempre
 * cifra al guardar. Una vez que todos los registros existentes se hayan migrado
 * con `perfiles:cifrar-datos`, se comporta exactamente como AsEncrypted.
 */
class EncryptedOrDefault implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value;
        }
    }

    public function set($model, string $key, $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString($value);
    }
}
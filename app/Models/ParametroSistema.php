<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParametroSistema extends Model
{
    protected $table = 'parametros_sistema';

    protected $fillable = ['clave', 'valor', 'descripcion'];

    public static function get(string $clave, mixed $default = null): mixed
    {
        $param = static::where('clave', $clave)->first();

        return $param ? $param->valor : $default;
    }

    public static function set(string $clave, mixed $valor, string $descripcion = ''): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor, 'descripcion' => $descripcion]);
    }

    public static function clear(string $clave): void
    {
        static::where('clave', $clave)->delete();
    }
}

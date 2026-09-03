<?php

namespace App\Models;

use Database\Factories\ParametroSistemaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ParametroSistema extends Model
{
    /** @use HasFactory<ParametroSistemaFactory> */
    use HasFactory;

    protected $table = 'parametros_sistema';

    protected $fillable = ['clave', 'valor', 'descripcion'];

    /** Prefijo de las claves de caché (evita colisiones con otras cachés). */
    private const CACHE_PREFIX = 'parametro:';

    /**
     * Lee un parámetro con caché. Los parámetros cambian rara vez (los edita solo el admin),
     * por lo que se cachean indefinidamente y se invalidan en set()/clear(). Solo se cachea el
     * valor cuando existe en BD (no el default), para que has() refleje la realidad.
     */
    public static function get(string $clave, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX.$clave;

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $valor = static::where('clave', $clave)->value('valor');

        if ($valor !== null) {
            Cache::forever($cacheKey, $valor);

            return $valor;
        }

        return $default;
    }

    public static function set(string $clave, mixed $valor, string $descripcion = ''): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor, 'descripcion' => $descripcion]);

        Cache::forget(self::CACHE_PREFIX.$clave);
    }

    public static function clear(string $clave): void
    {
        static::where('clave', $clave)->delete();

        Cache::forget(self::CACHE_PREFIX.$clave);
    }
}

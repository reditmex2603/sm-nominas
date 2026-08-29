<?php

namespace App\Services;

class FuzzyMatcher
{
    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');

        $mapa = [
            '/[áàäâã]/u' => 'a',
            '/[éèëê]/u' => 'e',
            '/[íìïî]/u' => 'i',
            '/[óòöôõ]/u' => 'o',
            '/[úùüû]/u' => 'u',
            '/[ñ]/u' => 'n',
        ];

        return preg_replace(array_keys($mapa), array_values($mapa), $texto);
    }

    /** Normalización específica para distancias de transporte (100-200k → 100-200km). */
    public static function normDist(string $txt): string
    {
        $txt = self::normalizar($txt);
        $txt = preg_replace('/\s*-\s*/', '-', $txt);   // espacios alrededor de guión
        $txt = preg_replace('/\bk\b/', 'km', $txt);    // "k" aislada → "km"

        return $txt;
    }

    public static function match(string $input, string $patron): bool
    {
        $a = self::normalizar($input);
        $b = self::normalizar($patron);

        return str_contains($a, $b) || str_contains($b, $a);
    }
}

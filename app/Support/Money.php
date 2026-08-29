<?php

namespace App\Support;

/**
 * Dinero representado como centavos enteros (int) para evitar la imprecisión de float
 * en operaciones monetarias. Inmutable: cada operación devuelve una nueva instancia.
 *
 * Los valores se normalizan SIEMPRE a 2 decimales (centavos). Se acepta entrada como
 * int/float/string decimal o "centavos".
 */
final class Money
{
    public function __construct(private readonly int $centavos) {}

    /** Crea desde un monto en centavos (int). */
    public static function fromCents(int $centavos): self
    {
        return new self($centavos);
    }

    /**
     * Crea desde un monto decimal (ej. 500, 500.5, "500.00", "350.50").
     * Se convierte vía string para no perder precisión con floats intermedios.
     */
    public static function from(int|float|string $monto): self
    {
        $monto = (string) $monto;

        // Normalizar a "N" o "N.XX" con hasta 2 decimales (trunca, no redondea).
        $monto = trim($monto);
        $negativo = str_starts_with($monto, '-');
        $monto = ltrim($monto, '-');
        $monto = str_replace(',', '.', $monto);

        if (str_contains($monto, '.')) {
            [$enteros, $decimales] = explode('.', $monto, 2);
            $decimales = str_pad(substr($decimales, 0, 2), 2, '0');
        } else {
            $enteros = $monto;
            $decimales = '00';
        }

        $enteros = $enteros === '' ? '0' : $enteros;
        $centavos = ((int) $enteros * 100) + (int) $decimales;

        return new self($negativo ? -$centavos : $centavos);
    }

    public function centavos(): int
    {
        return $this->centavos;
    }

    /** Valor decimal con 2 decimales, sin separador de miles (ej. "500.00"). */
    public function toDecimal(): string
    {
        $abs = abs($this->centavos);
        $signo = $this->centavos < 0 ? '-' : '';
        $enteros = intdiv($abs, 100);
        $decimales = str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);

        return $signo.$enteros.'.'.$decimales;
    }

    /** Valor float (para serialización JSON y compatibilidad con frontend). */
    public function toFloat(): float
    {
        return $this->centavos / 100;
    }

    public function sumar(Money|int|float|string $otro): self
    {
        return new self($this->centavos + $this->convertir($otro));
    }

    public function restar(Money|int|float|string $otro): self
    {
        return new self($this->centavos - $this->convertir($otro));
    }

    /** Convierte una entrada (incluida otra instancia de Money) a centavos. */
    private function convertir(Money|int|float|string $valor): int
    {
        if ($valor instanceof Money) {
            return $valor->centavos();
        }

        return self::from($valor)->centavos();
    }

    /**
     * Multiplica por un factor (cantidad de días, porcentaje como 0.50, etc.) y
     * redondea al centavo más cercano (round-half-up).
     */
    public function multiplicarPor(float $factor): self
    {
        return new self((int) round($this->centavos * $factor));
    }

    /**
     * Multiplica por un porcentaje entero (0-100): centavos × pct / 100, redondeo al centavo.
     */
    public function porcentajeDe(int $porcentaje): self
    {
        return new self((int) round($this->centavos * $porcentaje / 100));
    }

    /** Multiplica por un factor y redondea al centavo, devolviendo el valor absoluto (monto). */
    public function multiplicarPorMonto(float $factor): self
    {
        return $this->multiplicarPor($factor);
    }

    public function esNegativo(): bool
    {
        return $this->centavos < 0;
    }

    public function esCero(): bool
    {
        return $this->centavos === 0;
    }

    public function equals(Money $otro): bool
    {
        return $this->centavos === $otro->centavos;
    }
}

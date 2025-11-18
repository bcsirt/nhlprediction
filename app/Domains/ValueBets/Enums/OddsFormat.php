<?php

declare(strict_types=1);

namespace App\Domains\ValueBets\Enums;

enum OddsFormat: string
{
    case DECIMAL = 'decimal';
    case AMERICAN = 'american';
    case FRACTIONAL = 'fractional';
    case IMPLIED = 'implied';

    /**
     * Obtenir le libellé.
     */
    public function label(): string
    {
        return match($this) {
            self::DECIMAL => 'Décimales',
            self::AMERICAN => 'Américaines',
            self::FRACTIONAL => 'Fractionnaires',
            self::IMPLIED => 'Probabilité implicite',
        };
    }

    /**
     * Exemple de format.
     */
    public function example(): string
    {
        return match($this) {
            self::DECIMAL => '2.50',
            self::AMERICAN => '+150 / -200',
            self::FRACTIONAL => '3/2',
            self::IMPLIED => '40%',
        };
    }

    /**
     * Convertir des cotes décimales vers ce format.
     */
    public function fromDecimal(float $decimal): string
    {
        return match($this) {
            self::DECIMAL => number_format($decimal, 2),
            self::AMERICAN => self::decimalToAmerican($decimal),
            self::FRACTIONAL => self::decimalToFractional($decimal),
            self::IMPLIED => round((1 / $decimal) * 100, 1) . '%',
        };
    }

    /**
     * Convertir en cotes décimales depuis ce format.
     */
    public static function toDecimal(string $value, self $format): float
    {
        return match($format) {
            self::DECIMAL => (float) $value,
            self::AMERICAN => self::americanToDecimal((int) $value),
            self::FRACTIONAL => self::fractionalToDecimal($value),
            self::IMPLIED => 1 / ((float) str_replace('%', '', $value) / 100),
        };
    }

    private static function decimalToAmerican(float $decimal): string
    {
        if ($decimal >= 2.0) {
            return '+' . round(($decimal - 1) * 100);
        }
        return (string) round(-100 / ($decimal - 1));
    }

    private static function americanToDecimal(int $american): float
    {
        if ($american > 0) {
            return 1 + ($american / 100);
        }
        return 1 + (100 / abs($american));
    }

    private static function decimalToFractional(float $decimal): string
    {
        $fraction = $decimal - 1;

        // Trouver le dénominateur commun
        for ($den = 1; $den <= 100; $den++) {
            $num = $fraction * $den;
            if (abs($num - round($num)) < 0.001) {
                $num = (int) round($num);
                $gcd = self::gcd($num, $den);
                return ($num / $gcd) . '/' . ($den / $gcd);
            }
        }

        return round($fraction, 2) . '/1';
    }

    private static function fractionalToDecimal(string $fractional): float
    {
        $parts = explode('/', $fractional);
        if (count($parts) !== 2) {
            return 2.0;
        }
        return 1 + ((float) $parts[0] / (float) $parts[1]);
    }

    private static function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : self::gcd($b, $a % $b);
    }
}

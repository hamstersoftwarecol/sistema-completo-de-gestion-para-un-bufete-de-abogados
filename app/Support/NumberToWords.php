<?php

namespace App\Support;

/**
 * Convierte cantidades a letras en español (para recibos).
 */
class NumberToWords
{
    private const UNITS = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ',
        'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE',
        'VEINTIÚN', 'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];

    private const TENS = ['', '', '', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];

    private const HUNDREDS = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS',
        'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    public static function convert(float $amount, string $currency = 'PESOS', string $suffix = 'M/CTE'): string
    {
        $integer = (int) floor(round($amount, 2));
        $cents = (int) round(($amount - $integer) * 100);

        $words = $integer === 0 ? 'CERO' : self::integerToWords($integer);

        // "UN MILLÓN DE PESOS", "DOS MILLONES DE PESOS"
        if ($integer >= 1000000 && $integer % 1000000 === 0) {
            $words .= ' DE';
        }

        $result = trim($words.' '.$currency);

        if ($cents > 0) {
            $result .= ' CON '.str_pad((string) $cents, 2, '0', STR_PAD_LEFT).'/100';
        }

        return trim($result.' '.$suffix);
    }

    public static function integerToWords(int $number): string
    {
        if ($number === 0) {
            return 'CERO';
        }

        $parts = [];

        $millions = intdiv($number, 1000000);
        $thousands = intdiv($number % 1000000, 1000);
        $rest = $number % 1000;

        if ($millions > 0) {
            $parts[] = $millions === 1 ? 'UN MILLÓN' : self::integerToWords($millions).' MILLONES';
        }

        if ($thousands > 0) {
            $parts[] = $thousands === 1 ? 'MIL' : self::hundreds($thousands).' MIL';
        }

        if ($rest > 0) {
            $parts[] = self::hundreds($rest);
        }

        return implode(' ', $parts);
    }

    private static function hundreds(int $n): string
    {
        if ($n === 100) {
            return 'CIEN';
        }

        $h = intdiv($n, 100);
        $r = $n % 100;
        $out = self::HUNDREDS[$h];

        if ($r > 0) {
            $out = trim($out.' '.self::tens($r));
        }

        return $out;
    }

    private static function tens(int $n): string
    {
        if ($n < 30) {
            return self::UNITS[$n];
        }

        $t = intdiv($n, 10);
        $u = $n % 10;

        return self::TENS[$t].($u > 0 ? ' Y '.self::UNITS[$u] : '');
    }
}

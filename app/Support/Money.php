<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Conversões de valores monetários (BRL) de/para centavos.
 */
final class Money
{
    /**
     * Converte textos como "1.234,56", "- 2.547,67", "R$ -12,00", "-12.5" em centavos.
     */
    public static function parse(string|int|float|null $value, string $decimalSeparator = ','): int
    {
        if (is_int($value)) {
            return $value * 100;
        }

        if (is_float($value)) {
            return (int) round($value * 100);
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            throw new InvalidArgumentException('Valor vazio.');
        }

        $negative = str_contains($raw, '-') || (str_starts_with($raw, '(') && str_ends_with($raw, ')'));

        // Mantém apenas dígitos e separadores.
        $clean = preg_replace('/[^0-9.,]/', '', $raw);

        if ($decimalSeparator === ',') {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }

        if ($clean === '' || ! is_numeric($clean)) {
            throw new InvalidArgumentException("Valor inválido: {$raw}");
        }

        $cents = (int) round(((float) $clean) * 100);

        return $negative ? -$cents : $cents;
    }

    /**
     * Formata centavos como "R$ 1.234,56" (ou "-R$ 1.234,56").
     */
    public static function format(int $cents, bool $withSymbol = true): string
    {
        $formatted = number_format(abs($cents) / 100, 2, ',', '.');
        $prefix = $cents < 0 ? '-' : '';

        return $prefix.($withSymbol ? 'R$ ' : '').$formatted;
    }
}

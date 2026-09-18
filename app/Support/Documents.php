<?php

namespace App\Support;

class Documents
{
    /**
     * Strip every non-digit from a document and return the bare value.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';

        return $digits !== '' ? $digits : null;
    }

    /**
     * Validate a CPF or CNPJ. When a type is given, only that format is accepted.
     */
    public static function isValid(?string $value, ?string $type = null): bool
    {
        $digits = self::normalize($value);

        if ($digits === null) {
            return false;
        }

        return match ($type) {
            'pf' => self::isValidCpf($digits),
            'pj' => self::isValidCnpj($digits),
            default => self::isValidCpf($digits) || self::isValidCnpj($digits),
        };
    }

    /**
     * Validate a CPF (11 digits with valid check digits).
     */
    public static function isValidCpf(string $cpf): bool
    {
        $cpf = self::normalize($cpf) ?? '';

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;

            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * (($t + 1) - $i);
            }

            $digit = ((10 * $sum) % 11) % 10;

            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate a CNPJ (14 digits with valid check digits).
     */
    public static function isValidCnpj(string $cnpj): bool
    {
        $cnpj = self::normalize($cnpj) ?? '';

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        foreach ([
            [[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], 12],
            [[6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], 13],
        ] as [$weights, $digitPosition]) {
            $sum = 0;

            foreach ($weights as $position => $weight) {
                $sum += (int) $cnpj[$position] * $weight;
            }

            $remainder = $sum % 11;
            $digit = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $cnpj[$digitPosition] !== $digit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate a random, valid CPF (digits only).
     */
    public static function generateCpf(): string
    {
        $cpf = [];

        for ($i = 0; $i < 9; $i++) {
            $cpf[] = random_int(0, 9);
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;

            for ($i = 0; $i < $t; $i++) {
                $sum += $cpf[$i] * (($t + 1) - $i);
            }

            $cpf[] = ((10 * $sum) % 11) % 10;
        }

        return implode('', $cpf);
    }

    /**
     * Generate a random, valid CNPJ (digits only).
     */
    public static function generateCnpj(): string
    {
        $cnpj = [];

        for ($i = 0; $i < 12; $i++) {
            $cnpj[] = random_int(0, 9);
        }

        foreach ([
            [[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], 12],
            [[6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], 13],
        ] as [$weights, $digitPosition]) {
            $sum = 0;

            foreach ($weights as $position => $weight) {
                $sum += $cnpj[$position] * $weight;
            }

            $remainder = $sum % 11;
            $cnpj[] = $remainder < 2 ? 0 : 11 - $remainder;
        }

        return implode('', $cnpj);
    }

    /**
     * Format a normalized document as CPF or CNPJ.
     */
    public static function format(?string $value): ?string
    {
        $digits = self::normalize($value);

        if ($digits === null) {
            return null;
        }

        if (strlen($digits) === 14) {
            return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $digits);
        }

        return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $digits);
    }
}
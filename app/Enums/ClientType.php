<?php

namespace App\Enums;

enum ClientType: string
{
    case PF = 'pf';
    case PJ = 'pj';

    /**
     * Full display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PF => 'Pessoa Física',
            self::PJ => 'Pessoa Jurídica',
        };
    }

    /**
     * Short badge label.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::PF => 'PF',
            self::PJ => 'PJ',
        };
    }
}
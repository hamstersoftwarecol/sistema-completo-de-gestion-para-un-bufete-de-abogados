<?php

namespace App\Enums;

enum ClientType: string
{
    use HasOptions;

    case Person = 'persona';
    case Company = 'empresa';

    public function label(): string
    {
        return match ($this) {
            self::Person => 'Persona natural',
            self::Company => 'Empresa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Person => 'sky',
            self::Company => 'violet',
        };
    }
}

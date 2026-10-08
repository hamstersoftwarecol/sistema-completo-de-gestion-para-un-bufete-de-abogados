<?php

namespace App\Enums;

enum Role: string
{
    case Superadmin = 'superadmin';
    case Senior = 'senior';
    case Junior = 'junior';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadministrador',
            self::Senior => 'Abogado Senior',
            self::Junior => 'Abogado Junior',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Superadmin => 'rose',
            self::Senior => 'indigo',
            self::Junior => 'sky',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}

<?php

namespace App\Enums;

enum HearingStatus: string
{
    use HasOptions;

    case Scheduled = 'programada';
    case Held = 'realizada';
    case Postponed = 'aplazada';
    case Cancelled = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Programada',
            self::Held => 'Realizada',
            self::Postponed => 'Aplazada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'blue',
            self::Held => 'green',
            self::Postponed => 'amber',
            self::Cancelled => 'gray',
        };
    }
}

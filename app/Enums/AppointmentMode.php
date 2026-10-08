<?php

namespace App\Enums;

enum AppointmentMode: string
{
    use HasOptions;

    case InPerson = 'presencial';
    case Video = 'videollamada';
    case Phone = 'telefonica';

    public function label(): string
    {
        return match ($this) {
            self::InPerson => 'Presencial',
            self::Video => 'Videollamada',
            self::Phone => 'Telefónica',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InPerson => 'indigo',
            self::Video => 'violet',
            self::Phone => 'teal',
        };
    }
}

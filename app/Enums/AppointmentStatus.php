<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    use HasOptions;

    case Pending = 'pendiente';
    case Confirmed = 'confirmada';
    case Completed = 'completada';
    case Cancelled = 'cancelada';
    case NoShow = 'no_asistio';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Confirmed => 'Confirmada',
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
            self::NoShow => 'No asistió',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Confirmed => 'blue',
            self::Completed => 'green',
            self::Cancelled => 'gray',
            self::NoShow => 'red',
        };
    }
}

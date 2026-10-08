<?php

namespace App\Enums;

enum Priority: string
{
    use HasOptions;

    case Low = 'baja';
    case Medium = 'media';
    case High = 'alta';
    case Urgent = 'urgente';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baja',
            self::Medium => 'Media',
            self::High => 'Alta',
            self::Urgent => 'Urgente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Medium => 'blue',
            self::High => 'amber',
            self::Urgent => 'red',
        };
    }
}

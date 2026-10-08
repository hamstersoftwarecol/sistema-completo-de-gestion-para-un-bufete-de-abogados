<?php

namespace App\Enums;

enum CommunicationType: string
{
    use HasOptions;

    case Call = 'llamada';
    case Email = 'email';
    case WhatsApp = 'whatsapp';
    case Meeting = 'reunion';
    case Other = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Llamada',
            self::Email => 'Correo electrónico',
            self::WhatsApp => 'WhatsApp',
            self::Meeting => 'Reunión',
            self::Other => 'Otro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Call => 'green',
            self::Email => 'blue',
            self::WhatsApp => 'emerald',
            self::Meeting => 'violet',
            self::Other => 'gray',
        };
    }
}

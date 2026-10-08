<?php

namespace App\Enums;

enum PaymentMethod: string
{
    use HasOptions;

    case Cash = 'efectivo';
    case Transfer = 'transferencia';
    case Card = 'tarjeta';
    case Check = 'cheque';
    case Deposit = 'consignacion';
    case Other = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Transfer => 'Transferencia bancaria',
            self::Card => 'Tarjeta débito/crédito',
            self::Check => 'Cheque',
            self::Deposit => 'Consignación',
            self::Other => 'Otro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Cash => 'green',
            self::Transfer => 'blue',
            self::Card => 'violet',
            self::Check => 'amber',
            self::Deposit => 'teal',
            self::Other => 'gray',
        };
    }
}

<?php

namespace App\Enums;

enum ExpenseStatus: string
{
    use HasOptions;

    case Pending = 'pendiente';
    case Approved = 'aprobado';
    case Rejected = 'rechazado';
    case Reimbursed = 'reembolsado';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente de aprobación',
            self::Approved => 'Aprobado',
            self::Rejected => 'Rechazado',
            self::Reimbursed => 'Reembolsado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'blue',
            self::Rejected => 'red',
            self::Reimbursed => 'green',
        };
    }
}

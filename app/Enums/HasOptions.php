<?php

namespace App\Enums;

trait HasOptions
{
    /**
     * Opciones [valor => etiqueta] para selects.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

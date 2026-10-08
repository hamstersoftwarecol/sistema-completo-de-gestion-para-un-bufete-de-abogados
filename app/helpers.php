<?php

use App\Models\Setting;
use Illuminate\Support\Carbon;

if (! function_exists('setting')) {
    /**
     * Obtiene un valor de configuración del bufete (tabla settings).
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('money')) {
    /**
     * Formatea un importe con el símbolo y formato de moneda configurados.
     */
    function money(float|int|string|null $amount, bool $withSymbol = true): string
    {
        $decimals = (int) setting('currency_decimals', 0);
        [$dec, $thousands] = setting('number_format', 'es') === 'en' ? ['.', ','] : [',', '.'];
        $formatted = number_format((float) $amount, $decimals, $dec, $thousands);

        return $withSymbol ? trim(setting('currency_symbol', '$').' '.$formatted) : $formatted;
    }
}

if (! function_exists('fdate')) {
    function fdate(mixed $date, string $format = 'd/m/Y'): string
    {
        if (blank($date)) {
            return '—';
        }

        return Carbon::parse($date)->format($format);
    }
}

if (! function_exists('fdatetime')) {
    function fdatetime(mixed $date): string
    {
        return fdate($date, 'd/m/Y h:i a');
    }
}

if (! function_exists('format_bytes')) {
    function format_bytes(int|float $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}

if (! function_exists('theme_css_variables')) {
    /**
     * Variables CSS (canales RGB) del color primario elegido en Apariencia.
     */
    function theme_css_variables(): string
    {
        $themes = config('bufete.themes');
        $theme = $themes[setting('theme_color', 'indigo')] ?? $themes['indigo'];

        $vars = collect($theme['shades'])->map(function (string $hex, int $shade) {
            [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

            return "--primary-{$shade}: {$r} {$g} {$b};";
        })->implode(' ');

        return ":root { {$vars} }";
    }
}

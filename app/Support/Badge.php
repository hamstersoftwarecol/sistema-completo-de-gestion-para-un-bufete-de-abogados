<?php

namespace App\Support;

/**
 * Clases Tailwind (literales, para que el compilador las detecte) por color.
 */
class Badge
{
    public const COLORS = [
        'gray' => 'bg-gray-100 text-gray-700 ring-gray-500/20 dark:bg-gray-500/10 dark:text-gray-300 dark:ring-gray-400/30',
        'red' => 'bg-red-100 text-red-800 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-400/30',
        'orange' => 'bg-orange-100 text-orange-800 ring-orange-600/20 dark:bg-orange-500/10 dark:text-orange-300 dark:ring-orange-400/30',
        'amber' => 'bg-amber-100 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/30',
        'yellow' => 'bg-yellow-100 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-300 dark:ring-yellow-400/30',
        'lime' => 'bg-lime-100 text-lime-800 ring-lime-600/20 dark:bg-lime-500/10 dark:text-lime-300 dark:ring-lime-400/30',
        'green' => 'bg-green-100 text-green-800 ring-green-600/20 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-400/30',
        'emerald' => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/30',
        'teal' => 'bg-teal-100 text-teal-800 ring-teal-600/20 dark:bg-teal-500/10 dark:text-teal-300 dark:ring-teal-400/30',
        'cyan' => 'bg-cyan-100 text-cyan-800 ring-cyan-600/20 dark:bg-cyan-500/10 dark:text-cyan-300 dark:ring-cyan-400/30',
        'sky' => 'bg-sky-100 text-sky-800 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-400/30',
        'blue' => 'bg-blue-100 text-blue-800 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-400/30',
        'indigo' => 'bg-indigo-100 text-indigo-800 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-400/30',
        'violet' => 'bg-violet-100 text-violet-800 ring-violet-600/20 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-400/30',
        'purple' => 'bg-purple-100 text-purple-800 ring-purple-600/20 dark:bg-purple-500/10 dark:text-purple-300 dark:ring-purple-400/30',
        'fuchsia' => 'bg-fuchsia-100 text-fuchsia-800 ring-fuchsia-600/20 dark:bg-fuchsia-500/10 dark:text-fuchsia-300 dark:ring-fuchsia-400/30',
        'pink' => 'bg-pink-100 text-pink-800 ring-pink-600/20 dark:bg-pink-500/10 dark:text-pink-300 dark:ring-pink-400/30',
        'rose' => 'bg-rose-100 text-rose-800 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-400/30',
    ];

    /** Color sólido (puntos, barras del calendario, gráficos). */
    public const HEX = [
        'gray' => '#6b7280', 'red' => '#ef4444', 'orange' => '#f97316', 'amber' => '#f59e0b', 'yellow' => '#eab308',
        'lime' => '#84cc16', 'green' => '#22c55e', 'emerald' => '#10b981', 'teal' => '#14b8a6', 'cyan' => '#06b6d4',
        'sky' => '#0ea5e9', 'blue' => '#3b82f6', 'indigo' => '#6366f1', 'violet' => '#8b5cf6', 'purple' => '#a855f7',
        'fuchsia' => '#d946ef', 'pink' => '#ec4899', 'rose' => '#f43f5e',
    ];

    public static function classes(?string $color): string
    {
        return self::COLORS[$color] ?? self::COLORS['gray'];
    }

    public static function hex(?string $color): string
    {
        return self::HEX[$color] ?? self::HEX['gray'];
    }

    public static function names(): array
    {
        return array_keys(self::COLORS);
    }
}

@props(['label', 'value', 'icon' => 'chart', 'color' => 'primary', 'href' => null, 'hint' => null])
@php
    $colors = [
        'primary' => 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
        'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
        'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
        'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400',
        'gray' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-300',
    ];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'card flex items-center gap-4 p-4 transition '.($href ? 'hover:ring-primary-300 dark:hover:ring-primary-700' : '')]) }}>
    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $colors[$color] ?? $colors['primary'] }}">
        <x-icon :name="$icon" class="h-6 w-6" />
    </div>
    <div class="min-w-0">
        <p class="truncate text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
        <p class="mt-0.5 truncate text-xl font-bold text-gray-900 dark:text-white">{{ $value }}</p>
        @if ($hint)<p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>@endif
    </div>
</{{ $tag }}>

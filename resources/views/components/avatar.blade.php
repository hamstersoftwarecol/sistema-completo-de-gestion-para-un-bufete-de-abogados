@props(['name' => '?', 'size' => 'h-9 w-9 text-sm'])
@php
    $palette = ['bg-rose-500', 'bg-amber-500', 'bg-emerald-500', 'bg-sky-500', 'bg-violet-500', 'bg-indigo-500', 'bg-teal-500', 'bg-pink-500'];
    $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $color = $palette[abs(crc32($name)) % count($palette)];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-full font-semibold text-white {$size} {$color}"]) }}>{{ $initials ?: '?' }}</span>

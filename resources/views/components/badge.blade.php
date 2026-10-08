@props(['color' => 'gray'])
<span {{ $attributes->merge(['class' => 'badge '.\App\Support\Badge::classes($color)]) }}>{{ $slot }}</span>

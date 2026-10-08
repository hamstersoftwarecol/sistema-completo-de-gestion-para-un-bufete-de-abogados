@props(['href', 'icon', 'active' => false, 'badge' => null, 'badgeColor' => 'bg-rose-500'])
<a href="{{ $href }}" @class(['nav-item', 'nav-item-active' => $active])>
    <x-icon :name="$icon" class="h-5 w-5 shrink-0 opacity-90" />
    <span class="flex-1 truncate">{{ $slot }}</span>
    @if ($badge)
        <span class="ml-auto inline-flex min-w-[1.25rem] items-center justify-center rounded-full {{ $badgeColor }} px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $badge }}</span>
    @endif
</a>

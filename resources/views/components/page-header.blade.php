@props(['title', 'subtitle' => null, 'back' => null])
<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex min-w-0 items-center gap-3">
        @if ($back)
            <a href="{{ $back }}" class="btn btn-ghost btn-sm -ml-2 shrink-0" title="Volver"><x-icon name="arrow-left" class="h-5 w-5" /></a>
        @endif
        <div class="min-w-0">
            <h1 class="truncate text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">{{ $title }}</h1>
            @if ($subtitle)<p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>@endif
        </div>
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>

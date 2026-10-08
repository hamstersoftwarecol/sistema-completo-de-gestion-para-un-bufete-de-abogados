@props(['icon' => 'folder', 'title' => 'Sin registros', 'message' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-10 text-center']) }}>
    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700/50 dark:text-gray-500">
        <x-icon :name="$icon" class="h-6 w-6" />
    </div>
    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $title }}</p>
    @if ($message)<p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ $message }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-4">{{ $slot }}</div>@endif
</div>

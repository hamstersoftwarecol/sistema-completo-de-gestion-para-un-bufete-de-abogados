@props(['name', 'title', 'maxWidth' => 'lg', 'show' => false])
<x-modal :name="$name" :max-width="$maxWidth" :show="$show" focusable>
    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h2>
        <button type="button" x-on:click="$dispatch('close')" class="btn btn-ghost btn-sm -mr-2" aria-label="Cerrar"><x-icon name="x" class="h-5 w-5" /></button>
    </div>
    <div class="px-6 py-5">
        {{ $slot }}
    </div>
</x-modal>

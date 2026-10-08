<x-app-layout title="Notificaciones">
    <x-slot name="header">
        <x-page-header title="Notificaciones">
            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-secondary"><x-icon name="check" class="h-4 w-4" /> Marcar todas como leídas</button></form>
            <form method="POST" action="{{ route('notifications.clear') }}" data-confirm="¿Eliminar las notificaciones ya leídas?">@csrf @method('DELETE')<button class="btn btn-secondary"><x-icon name="trash" class="h-4 w-4" /> Limpiar leídas</button></form>
        </x-page-header>
    </x-slot>

    <div class="card divide-y divide-gray-100 dark:divide-gray-700">
        @forelse ($notifications as $n)
            <a href="{{ route('notifications.open', $n->id) }}" @class(['flex items-start gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30', 'bg-primary-50/40 dark:bg-primary-500/5' => ! $n->read_at])>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ \App\Support\Badge::classes($n->data['color'] ?? 'indigo') }}"><x-icon :name="$n->data['icon'] ?? 'bell'" class="h-5 w-5" /></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold">{{ $n->data['title'] ?? 'Notificación' }} @unless($n->read_at)<span class="ml-1 inline-block h-2 w-2 rounded-full bg-primary-500"></span>@endunless</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $n->data['message'] ?? '' }}</p>
                </div>
                <span class="shrink-0 text-xs text-gray-400">{{ $n->created_at->diffForHumans() }}</span>
            </a>
        @empty
            <x-empty icon="bell" title="No tiene notificaciones" message="Aquí verá asignaciones de casos, audiencias programadas, recordatorios y aprobaciones de gastos." />
        @endforelse
    </div>
    <div class="mt-4">{{ $notifications->links() }}</div>
</x-app-layout>

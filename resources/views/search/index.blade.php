<x-app-layout title="Búsqueda">
    <x-slot name="header">
        <x-page-header title="Resultados de búsqueda" :subtitle="$q ? '«'.$q.'»' : 'Escriba al menos 2 caracteres'" />
    </x-slot>

    @php $total = collect($results)->sum(fn ($r) => $r->count()); @endphp
    @if ($q && $total === 0)
        <div class="card"><x-empty icon="search" title="Sin resultados" message="Pruebe con otro nombre, número de documento o número de caso." /></div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @if ($results['clients']->isNotEmpty())
            <x-card title="Clientes ({{ $results['clients']->count() }})" icon="users" :padding="false">
                @foreach ($results['clients'] as $c)
                    <a href="{{ route('clients.show', $c) }}" class="flex items-center gap-3 border-b border-gray-100 px-5 py-3 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/30">
                        <x-avatar :name="$c->name" />
                        <div><p class="text-sm font-semibold">{{ $c->name }}</p><p class="text-xs text-gray-500">{{ $c->document_label }} · {{ $c->cases_count }} caso(s)</p></div>
                    </a>
                @endforeach
            </x-card>
        @endif
        @if ($results['cases']->isNotEmpty())
            <x-card title="Casos ({{ $results['cases']->count() }})" icon="briefcase" :padding="false">
                @foreach ($results['cases'] as $c)
                    <a href="{{ route('cases.show', $c) }}" class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/30">
                        <div class="min-w-0"><p class="text-sm font-semibold text-primary-600">{{ $c->case_number }}</p><p class="truncate text-xs text-gray-500">{{ $c->title }} · {{ $c->client?->name }}</p></div>
                        <x-badge :color="$c->status?->color">{{ $c->status?->name }}</x-badge>
                    </a>
                @endforeach
            </x-card>
        @endif
        @if ($results['documents']->isNotEmpty())
            <x-card title="Documentos ({{ $results['documents']->count() }})" icon="document" :padding="false">
                @foreach ($results['documents'] as $d)
                    <a href="{{ route('documents.show', $d) }}" target="_blank" class="flex items-center gap-3 border-b border-gray-100 px-5 py-3 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/30">
                        <x-icon name="document" class="h-5 w-5 text-gray-400" />
                        <div><p class="text-sm font-semibold">{{ $d->title }}</p><p class="text-xs text-gray-500">Caso {{ $d->legalCase?->case_number }} · {{ $d->original_name }}</p></div>
                    </a>
                @endforeach
            </x-card>
        @endif
        @if ($results['hearings']->isNotEmpty())
            <x-card title="Audiencias ({{ $results['hearings']->count() }})" icon="scale" :padding="false">
                @foreach ($results['hearings'] as $h)
                    <a href="{{ route('hearings.edit', $h) }}" class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/30">
                        <div><p class="text-sm font-semibold">{{ $h->title }}</p><p class="text-xs text-gray-500">{{ fdatetime($h->scheduled_at) }} · Caso {{ $h->legalCase?->case_number }}</p></div>
                        <x-badge :color="$h->status->color()">{{ $h->status->label() }}</x-badge>
                    </a>
                @endforeach
            </x-card>
        @endif
    </div>
</x-app-layout>

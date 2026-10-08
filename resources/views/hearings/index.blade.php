<x-app-layout title="Audiencias">
    <x-slot name="header">
        <x-page-header title="Audiencias" subtitle="Programador de audiencias judiciales">
            <a href="{{ route('calendar.index') }}" class="btn btn-secondary"><x-icon name="calendar" class="h-4 w-4" /> Calendario</a>
            <a href="{{ route('hearings.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Programar audiencia</a>
        </x-page-header>
    </x-slot>

    <div class="card">
        <div class="flex gap-6 overflow-x-auto border-b border-gray-200 px-5 dark:border-gray-700">
            @foreach (['upcoming' => 'Próximas', 'past' => 'Anteriores', 'all' => 'Todas'] as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['range' => $key, 'page' => null]) }}" @class(['tab', 'tab-active' => $range === $key])>{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" class="flex flex-col gap-3 border-b border-gray-100 p-4 dark:border-gray-700 sm:flex-row">
            <input type="hidden" name="range" value="{{ $range }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por audiencia, caso o cliente…" class="form-control flex-1">
            <select name="status" class="form-control sm:w-44" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                @foreach (\App\Enums\HearingStatus::options() as $v => $l)<option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>@endforeach
            </select>
            @can('admin')
                <select name="lawyer" class="form-control sm:w-56" onchange="this.form.submit()">
                    <option value="">Todos los abogados</option>
                    @foreach ($lawyers as $l)<option value="{{ $l->id }}" @selected(request('lawyer') == $l->id)>{{ $l->name }}</option>@endforeach
                </select>
            @endcan
            <button class="btn btn-secondary"><x-icon name="funnel" class="h-4 w-4" /> Filtrar</button>
        </form>

        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Fecha y hora</th><th>Audiencia</th><th>Caso / cliente</th><th>Juzgado / lugar</th><th>Asignada a</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse ($hearings as $h)
                    @php $isToday = $h->scheduled_at->isToday(); @endphp
                    <tr @class(['bg-rose-50/50 dark:bg-rose-500/5' => $isToday])>
                        <td class="whitespace-nowrap">
                            <p class="font-semibold">{{ $h->scheduled_at->format('d/m/Y') }} @if($isToday)<x-badge color="rose">Hoy</x-badge>@endif</p>
                            <p class="text-xs text-gray-500">{{ $h->scheduled_at->format('h:i a') }} · {{ $h->duration_minutes }} min</p>
                        </td>
                        <td><p class="font-medium">{{ $h->title }}</p><p class="text-xs text-gray-500">{{ $h->hearing_type }}</p></td>
                        <td>
                            <a href="{{ route('cases.show', $h->legal_case_id) }}#audiencias" class="link text-xs">{{ $h->legalCase?->case_number }}</a>
                            <p class="text-xs text-gray-500">{{ $h->legalCase?->client?->name }}</p>
                        </td>
                        <td class="max-w-[16rem] text-xs"><p class="truncate">{{ $h->court?->name ?? '—' }}</p><p class="truncate text-gray-500">{{ $h->location }}</p></td>
                        <td class="whitespace-nowrap">{{ $h->user?->name ?? '—' }}</td>
                        <td><x-badge :color="$h->status->color()">{{ $h->status->label() }}</x-badge></td>
                        <td class="whitespace-nowrap text-right">
                            @can('update', $h)<a href="{{ route('hearings.edit', $h) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4" /></a>@endcan
                            @can('delete', $h)<x-delete-button :action="route('hearings.destroy', $h)" confirm="¿Eliminar la audiencia «{{ $h->title }}»?" />@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty icon="scale" title="No hay audiencias en esta vista"><a href="{{ route('hearings.create') }}" class="btn btn-primary">Programar audiencia</a></x-empty></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($hearings->hasPages())<div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $hearings->links() }}</div>@endif
    </div>
</x-app-layout>

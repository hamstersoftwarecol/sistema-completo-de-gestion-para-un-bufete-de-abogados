<x-app-layout title="Casos">
    <x-slot name="header">
        <x-page-header title="Casos" :subtitle="auth()->user()->isSuperadmin() ? 'Todos los expedientes del bufete' : 'Expedientes en los que usted es responsable o asistente'">
            <a href="{{ route('cases.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Nuevo caso</a>
        </x-page-header>
    </x-slot>

    @php $scope = request('scope', 'open'); @endphp

    <div class="card">
        <div class="flex gap-6 overflow-x-auto border-b border-gray-200 px-5 dark:border-gray-700">
            @foreach (['open' => 'Activos', 'closed' => 'Cerrados', 'all' => 'Todos'] as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['scope' => $key, 'page' => null]) }}" @class(['tab', 'tab-active' => $scope === $key])>{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-gray-100 p-4 dark:border-gray-700 md:grid-cols-6">
            <input type="hidden" name="scope" value="{{ $scope }}">
            <div class="relative md:col-span-2">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Número, título, cliente, parte, juez…" class="form-control pl-9">
            </div>
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">Estado</option>
                @foreach ($statuses as $s)<option value="{{ $s->id }}" @selected(request('status') == $s->id)>{{ $s->name }}</option>@endforeach
            </select>
            <select name="type" class="form-control" onchange="this.form.submit()">
                <option value="">Tipo de caso</option>
                @foreach ($types as $t)<option value="{{ $t->id }}" @selected(request('type') == $t->id)>{{ $t->name }}</option>@endforeach
            </select>
            <select name="priority" class="form-control" onchange="this.form.submit()">
                <option value="">Prioridad</option>
                @foreach (\App\Enums\Priority::options() as $v => $l)<option value="{{ $v }}" @selected(request('priority') === $v)>{{ $l }}</option>@endforeach
            </select>
            @can('admin')
                <select name="lawyer" class="form-control" onchange="this.form.submit()">
                    <option value="">Abogado</option>
                    @foreach ($lawyers as $l)<option value="{{ $l->id }}" @selected(request('lawyer') == $l->id)>{{ $l->name }}</option>@endforeach
                </select>
            @else
                <button class="btn btn-secondary"><x-icon name="funnel" class="h-4 w-4" /> Filtrar</button>
            @endcan
        </form>

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                <tr>
                    <th>Caso</th>
                    <th>Cliente</th>
                    <th class="hidden lg:table-cell">Tipo / Juzgado</th>
                    <th class="hidden 2xl:table-cell">Equipo</th>
                    <th>Estado</th>
                    <th class="text-right">Saldo</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($cases as $case)
                    <tr>
                        <td class="max-w-xs">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('cases.show', $case) }}" class="link whitespace-nowrap">{{ $case->case_number }}</a>
                                @if (in_array($case->priority?->value, ['alta', 'urgente']))
                                    <x-badge :color="$case->priority->color()">{{ $case->priority->label() }}</x-badge>
                                @endif
                            </div>
                            <p class="truncate text-xs text-gray-500" title="{{ $case->title }}">{{ $case->title }}</p>
                        </td>
                        <td class="min-w-[11rem]"><a href="{{ route('clients.show', $case->client_id) }}" class="hover:text-primary-600">{{ $case->client?->name }}</a></td>
                        <td class="hidden text-xs lg:table-cell">
                            <p class="font-medium text-gray-700 dark:text-gray-300">{{ $case->type?->name ?? '—' }}</p>
                            <p class="max-w-[14rem] truncate text-gray-500">{{ $case->court?->name ?? 'Sin juzgado' }}</p>
                        </td>
                        <td class="hidden text-xs 2xl:table-cell">
                            <p class="whitespace-nowrap">{{ $case->lawyer?->name ?? '—' }}</p>
                            @if ($case->assistant)<p class="whitespace-nowrap text-gray-500">+ {{ $case->assistant->name }}</p>@endif
                        </td>
                        <td>
                            <x-badge :color="$case->status?->color">{{ $case->status?->name ?? '—' }}</x-badge>
                            @if ($case->upcoming_hearings_count)
                                <p class="mt-1 flex items-center gap-1 text-xs text-rose-600 dark:text-rose-400"><x-icon name="scale" class="h-3.5 w-3.5" />{{ $case->upcoming_hearings_count }} audiencia(s)</p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap text-right font-semibold">{{ money(max(0, $case->fee_amount - $case->payments_sum_amount)) }}</td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('cases.show', $case) }}" class="btn btn-ghost btn-sm" title="Expediente"><x-icon name="folder" class="h-4 w-4" /></a>
                            <a href="{{ route('cases.print', $case) }}" target="_blank" class="btn btn-ghost btn-sm" title="Informe imprimible"><x-icon name="printer" class="h-4 w-4" /></a>
                            @can('update', $case)
                                <a href="{{ route('cases.edit', $case) }}" class="btn btn-ghost btn-sm" title="Editar"><x-icon name="pencil" class="h-4 w-4" /></a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty icon="briefcase" title="No se encontraron casos" message="Ajuste los filtros o registre un nuevo caso."><a href="{{ route('cases.create') }}" class="btn btn-primary">Nuevo caso</a></x-empty></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($cases->hasPages())
            <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $cases->links() }}</div>
        @endif
    </div>
</x-app-layout>

<x-app-layout title="Citas">
    <x-slot name="header">
        <x-page-header title="Citas" subtitle="Reserva de citas con clientes y prospectos">
            <a href="{{ route('calendar.index') }}" class="btn btn-secondary"><x-icon name="calendar" class="h-4 w-4" /> Calendario</a>
            <a href="{{ route('appointments.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Reservar cita</a>
        </x-page-header>
    </x-slot>

    <div class="card">
        <div class="flex gap-6 overflow-x-auto border-b border-gray-200 px-5 dark:border-gray-700">
            @foreach (['today' => 'Hoy', 'upcoming' => 'Próximas', 'past' => 'Anteriores', 'all' => 'Todas'] as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['range' => $key, 'page' => null]) }}" @class(['tab', 'tab-active' => $range === $key])>{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" class="flex flex-col gap-3 border-b border-gray-100 p-4 dark:border-gray-700 sm:flex-row">
            <input type="hidden" name="range" value="{{ $range }}">
            <select name="status" class="form-control sm:w-48" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                @foreach (\App\Enums\AppointmentStatus::options() as $v => $l)<option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>@endforeach
            </select>
            @can('admin')
                <select name="lawyer" class="form-control sm:w-56" onchange="this.form.submit()">
                    <option value="">Todos los abogados</option>
                    @foreach ($lawyers as $l)<option value="{{ $l->id }}" @selected(request('lawyer') == $l->id)>{{ $l->name }}</option>@endforeach
                </select>
            @endcan
        </form>

        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Fecha</th><th>Asunto</th><th>Cliente / contacto</th><th>Abogado</th><th>Modalidad</th><th class="text-right">Tarifa</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse ($appointments as $a)
                    <tr>
                        <td class="whitespace-nowrap"><p class="font-semibold">{{ $a->starts_at->format('d/m/Y') }}</p><p class="text-xs text-gray-500">{{ $a->starts_at->format('h:i a') }} · {{ $a->duration_minutes }} min</p></td>
                        <td><p class="font-medium">{{ $a->title }}</p>@if($a->legalCase)<a href="{{ route('cases.show', $a->legal_case_id) }}" class="text-xs link">{{ $a->legalCase->case_number }}</a>@endif</td>
                        <td>
                            @if ($a->client)
                                <a href="{{ route('clients.show', $a->client) }}" class="hover:text-primary-600">{{ $a->client->name }}</a>
                            @else
                                {{ $a->contact_name }} <x-badge color="violet">Prospecto</x-badge>
                                @if ($a->contact_phone)<p class="text-xs text-gray-500">{{ $a->contact_phone }}</p>@endif
                            @endif
                        </td>
                        <td class="whitespace-nowrap">{{ $a->user?->name }}</td>
                        <td><x-badge :color="$a->mode->color()">{{ $a->mode->label() }}</x-badge></td>
                        <td class="whitespace-nowrap text-right">
                            @if ($a->fee > 0)
                                {{ money($a->fee) }}
                                @php $paid = (float) $a->payments_sum_amount; @endphp
                                <p class="text-xs {{ $paid >= $a->fee ? 'text-emerald-600' : 'text-amber-600' }}">{{ $paid >= $a->fee ? 'Pagada' : 'Pendiente' }}</p>
                            @else
                                <span class="text-gray-400">Sin costo</span>
                            @endif
                        </td>
                        <td>
                            @can('update', $a)
                                <form method="POST" action="{{ route('appointments.status', $a) }}">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" class="rounded-md border-0 py-0.5 pl-2 pr-7 text-xs font-medium ring-1 ring-inset {{ \App\Support\Badge::classes($a->status->color()) }}">
                                        @foreach (\App\Enums\AppointmentStatus::options() as $v => $l)<option value="{{ $v }}" @selected($a->status->value === $v)>{{ $l }}</option>@endforeach
                                    </select>
                                </form>
                            @else
                                <x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge>
                            @endcan
                        </td>
                        <td class="whitespace-nowrap text-right">
                            @if ($a->fee > 0 && $a->client_id && (float) $a->payments_sum_amount < $a->fee)
                                <a href="{{ route('payments.create', ['appointment_id' => $a->id]) }}" class="btn btn-ghost btn-sm text-emerald-600" title="Registrar pago"><x-icon name="banknotes" class="h-4 w-4" /></a>
                            @endif
                            @can('update', $a)<a href="{{ route('appointments.edit', $a) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4" /></a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty icon="clock" title="No hay citas en esta vista"><a href="{{ route('appointments.create') }}" class="btn btn-primary">Reservar cita</a></x-empty></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($appointments->hasPages())<div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $appointments->links() }}</div>@endif
    </div>
</x-app-layout>

<x-app-layout title="Clientes">
    <x-slot name="header">
        <x-page-header title="Clientes" subtitle="Personas y empresas representadas por el bufete">
            <a href="{{ route('clients.create') }}" class="btn btn-primary"><x-icon name="user-plus" class="h-4 w-4" /> Nuevo cliente</a>
        </x-page-header>
    </x-slot>

    <div class="card">
        <form method="GET" class="flex flex-col gap-3 border-b border-gray-100 p-4 dark:border-gray-700 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Nombre, documento, correo o teléfono…" class="form-control pl-9">
            </div>
            <select name="type" class="form-control sm:w-44" onchange="this.form.submit()">
                <option value="">Todos los tipos</option>
                @foreach (\App\Enums\ClientType::options() as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @can('admin')
                <select name="lawyer" class="form-control sm:w-56" onchange="this.form.submit()">
                    <option value="">Todos los abogados</option>
                    @foreach ($lawyers as $l)
                        <option value="{{ $l->id }}" @selected(request('lawyer') == $l->id)>{{ $l->name }}</option>
                    @endforeach
                </select>
            @endcan
            <button class="btn btn-secondary"><x-icon name="funnel" class="h-4 w-4" /> Filtrar</button>
        </form>

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Documento</th>
                    <th>Contacto</th>
                    <th class="text-center">Casos</th>
                    <th class="text-right">Pagado</th>
                    <th>Abogado</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($clients as $client)
                    <tr>
                        <td>
                            <a href="{{ route('clients.show', $client) }}" class="flex items-center gap-3">
                                <x-avatar :name="$client->name" />
                                <span>
                                    <span class="block font-semibold text-gray-900 hover:text-primary-600 dark:text-gray-100">{{ $client->name }}</span>
                                    <x-badge :color="$client->type->color()">{{ $client->type->label() }}</x-badge>
                                </span>
                            </a>
                        </td>
                        <td class="whitespace-nowrap">{{ $client->document_label ?: '—' }}</td>
                        <td class="text-xs">
                            @if ($client->phone)<a href="tel:{{ $client->phone }}" class="flex items-center gap-1 hover:text-primary-600"><x-icon name="phone" class="h-3.5 w-3.5" />{{ $client->phone }}</a>@endif
                            @if ($client->email)<a href="mailto:{{ $client->email }}" class="flex items-center gap-1 hover:text-primary-600"><x-icon name="envelope" class="h-3.5 w-3.5" />{{ $client->email }}</a>@endif
                        </td>
                        <td class="text-center font-semibold">{{ $client->cases_count }}</td>
                        <td class="whitespace-nowrap text-right">{{ money($client->payments_sum_amount) }}</td>
                        <td class="whitespace-nowrap">{{ $client->lawyer?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('clients.show', $client) }}" class="btn btn-ghost btn-sm" title="Vista 360"><x-icon name="eye" class="h-4 w-4" /></a>
                            @can('update', $client)
                                <a href="{{ route('clients.edit', $client) }}" class="btn btn-ghost btn-sm" title="Editar"><x-icon name="pencil" class="h-4 w-4" /></a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty icon="users" title="No se encontraron clientes" message="Registre su primer cliente para empezar a gestionar sus casos."><a href="{{ route('clients.create') }}" class="btn btn-primary">Nuevo cliente</a></x-empty></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($clients->hasPages())
            <div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $clients->links() }}</div>
        @endif
    </div>
</x-app-layout>

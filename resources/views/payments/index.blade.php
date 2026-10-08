<x-app-layout title="Pagos y recibos">
    <x-slot name="header">
        <x-page-header title="Pagos y recibos" subtitle="Seguimiento de honorarios y consultas cobradas">
            <a href="{{ route('payments.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Registrar pago</a>
        </x-page-header>
    </x-slot>

    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-gray-100 p-4 dark:border-gray-700 md:grid-cols-6">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Recibo, concepto o cliente…" class="form-control md:col-span-2">
            <select name="method" class="form-control">
                <option value="">Medio de pago</option>
                @foreach (\App\Enums\PaymentMethod::options() as $v => $l)<option value="{{ $v }}" @selected(request('method') === $v)>{{ $l }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control" title="Desde">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control" title="Hasta">
            <button class="btn btn-secondary"><x-icon name="funnel" class="h-4 w-4" /> Filtrar</button>
        </form>
        <div class="flex items-center justify-between bg-emerald-50 px-5 py-3 text-sm dark:bg-emerald-500/10">
            <span class="text-emerald-800 dark:text-emerald-200">{{ $payments->total() }} pago(s) en la selección</span>
            <span class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ money($total) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Recibo</th><th>Fecha</th><th>Cliente</th><th>Concepto</th><th>Medio</th><th class="text-right">Valor</th><th></th></tr></thead>
                <tbody>
                @forelse ($payments as $p)
                    <tr>
                        <td class="whitespace-nowrap"><a href="{{ route('payments.show', $p) }}" class="link">{{ $p->receipt_number }}</a></td>
                        <td class="whitespace-nowrap">{{ fdate($p->paid_at) }}</td>
                        <td><a href="{{ route('clients.show', $p->client_id) }}" class="hover:text-primary-600">{{ $p->client?->name }}</a>@if($p->legalCase)<p class="text-xs text-gray-500">{{ $p->legalCase->case_number }}</p>@endif</td>
                        <td class="max-w-xs"><p class="truncate">{{ $p->concept }}</p><p class="text-xs text-gray-500">Recibió: {{ $p->user?->name }}</p></td>
                        <td><x-badge :color="$p->method->color()">{{ $p->method->label() }}</x-badge></td>
                        <td class="whitespace-nowrap text-right font-semibold">{{ money($p->amount) }}</td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('payments.show', $p) }}" class="btn btn-ghost btn-sm" title="Recibo imprimible"><x-icon name="printer" class="h-4 w-4" /></a>
                            @can('update', $p)<a href="{{ route('payments.edit', $p) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4" /></a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty icon="banknotes" title="No hay pagos registrados"><a href="{{ route('payments.create') }}" class="btn btn-primary">Registrar pago</a></x-empty></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($payments->hasPages())<div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $payments->links() }}</div>@endif
    </div>
</x-app-layout>

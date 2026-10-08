<x-app-layout title="Gastos">
    <x-slot name="header">
        <x-page-header title="Gastos del caso" subtitle="Flujo de aprobación y reembolso: pendiente → aprobado / rechazado → reembolsado">
            <a href="{{ route('expenses.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Registrar gasto</a>
        </x-page-header>
    </x-slot>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach (\App\Enums\ExpenseStatus::cases() as $st)
            @php $row = $totals[$st->value] ?? null; @endphp
            <a href="{{ request()->fullUrlWithQuery(['status' => request('status') === $st->value ? null : $st->value, 'page' => null]) }}"
               @class(['card p-4 transition hover:ring-primary-300', 'ring-2 !ring-primary-500' => request('status') === $st->value])>
                <x-badge :color="$st->color()">{{ $st->label() }}</x-badge>
                <p class="mt-2 text-xl font-bold">{{ money($row->total ?? 0) }}</p>
                <p class="text-xs text-gray-500">{{ $row->n ?? 0 }} gasto(s)</p>
            </a>
        @endforeach
    </div>

    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-gray-100 p-4 dark:border-gray-700 md:grid-cols-5">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Descripción o caso…" class="form-control md:col-span-2">
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                @foreach (\App\Enums\ExpenseStatus::options() as $v => $l)<option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>@endforeach
            </select>
            <select name="category" class="form-control" onchange="this.form.submit()">
                <option value="">Todas las categorías</option>
                @foreach (config('bufete.expense_categories') as $k => $l)<option value="{{ $k }}" @selected(request('category') === $k)>{{ $l }}</option>@endforeach
            </select>
            @can('admin')
                <select name="lawyer" class="form-control" onchange="this.form.submit()">
                    <option value="">Registrado por…</option>
                    @foreach ($lawyers as $l)<option value="{{ $l->id }}" @selected(request('lawyer') == $l->id)>{{ $l->name }}</option>@endforeach
                </select>
            @else
                <button class="btn btn-secondary"><x-icon name="funnel" class="h-4 w-4" /> Filtrar</button>
            @endcan
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Fecha</th><th>Descripción</th><th>Caso</th><th>Registró</th><th>Estado</th><th class="text-right">Valor</th><th></th></tr></thead>
                <tbody>
                @forelse ($expenses as $e)
                    <tr>
                        <td class="whitespace-nowrap">{{ fdate($e->expense_date) }}</td>
                        <td>
                            <a href="{{ route('expenses.show', $e) }}" class="font-medium hover:text-primary-600">{{ $e->description }}</a>
                            <p class="text-xs text-gray-500">{{ $e->category_label }} @if($e->receipt_path)· <x-icon name="paperclip" class="inline h-3 w-3" /> soporte @endif</p>
                        </td>
                        <td class="text-xs"><a href="{{ route('cases.show', $e->legal_case_id) }}" class="link">{{ $e->legalCase?->case_number }}</a><p class="text-gray-500">{{ $e->legalCase?->client?->name }}</p></td>
                        <td class="whitespace-nowrap">{{ $e->user?->name }}</td>
                        <td><x-badge :color="$e->status->color()">{{ $e->status->label() }}</x-badge></td>
                        <td class="whitespace-nowrap text-right font-semibold">{{ money($e->amount) }}</td>
                        <td class="whitespace-nowrap text-right">
                            @can('approve', $e)
                                @if ($e->isPending())
                                    <form method="POST" action="{{ route('expenses.approve', $e) }}" class="inline">@csrf
                                        <button class="btn btn-ghost btn-sm text-emerald-600" title="Aprobar"><x-icon name="check-circle" class="h-4 w-4" /></button>
                                    </form>
                                @endif
                            @endcan
                            <a href="{{ route('expenses.show', $e) }}" class="btn btn-ghost btn-sm" title="Ver"><x-icon name="eye" class="h-4 w-4" /></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty icon="receipt" title="No hay gastos en esta vista" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($expenses->hasPages())<div class="border-t border-gray-100 p-4 dark:border-gray-700">{{ $expenses->links() }}</div>@endif
    </div>
</x-app-layout>

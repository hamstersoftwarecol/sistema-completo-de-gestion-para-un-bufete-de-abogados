<x-app-layout title="Gasto">
    <x-slot name="header">
        <x-page-header :title="$expense->description" :subtitle="'Caso '.$expense->legalCase?->case_number.' · '.$expense->legalCase?->client?->name" :back="route('expenses.index')">
            @if ($expense->isPending())
                @can('update', $expense)<a href="{{ route('expenses.edit', $expense) }}" class="btn btn-secondary"><x-icon name="pencil" class="h-4 w-4" /> Editar</a>@endcan
            @endif
            @if ($expense->status !== \App\Enums\ExpenseStatus::Reimbursed)
                @can('delete', $expense)<x-delete-button :action="route('expenses.destroy', $expense)" label="Eliminar" size="" confirm="¿Eliminar este gasto?" />@endcan
            @endif
        </x-page-header>
    </x-slot>

    @php
        $steps = [
            ['Registrado', $expense->created_at, $expense->user?->name, true, 'blue'],
            [$expense->status === \App\Enums\ExpenseStatus::Rejected ? 'Rechazado' : 'Aprobado', $expense->reviewed_at, $expense->reviewer?->name, (bool) $expense->reviewed_at, $expense->status === \App\Enums\ExpenseStatus::Rejected ? 'red' : 'blue'],
            ['Reembolsado', $expense->reimbursed_at, $expense->reimburser?->name, (bool) $expense->reimbursed_at, 'green'],
        ];
        if ($expense->status === \App\Enums\ExpenseStatus::Rejected) { array_pop($steps); }
    @endphp

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-card title="Detalle" icon="receipt">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-3xl font-bold">{{ money($expense->amount) }}</p>
                        <p class="text-sm text-gray-500">{{ $expense->category_label }} · {{ fdate($expense->expense_date) }} · {{ $expense->billable ? 'Facturable al cliente' : 'No facturable' }}</p>
                    </div>
                    <x-badge :color="$expense->status->color()" class="!text-sm">{{ $expense->status->label() }}</x-badge>
                </div>

                <ol class="mt-6 flex flex-col gap-4 sm:flex-row">
                    @foreach ($steps as $i => [$label, $date, $who, $done, $color])
                        <li class="flex flex-1 items-start gap-3">
                            <span @class(['flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold', \App\Support\Badge::classes($color) => $done, 'bg-gray-100 text-gray-400 dark:bg-gray-700' => ! $done])>
                                @if ($done)<x-icon name="check" class="h-4 w-4" />@else{{ $i + 1 }}@endif
                            </span>
                            <div>
                                <p class="text-sm font-semibold">{{ $label }}</p>
                                <p class="text-xs text-gray-500">{{ $done ? ($date?->format('d/m/Y h:i a').' · '.$who) : 'Pendiente' }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                @if ($expense->review_notes)
                    <div class="mt-5 rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-700/40"><span class="font-semibold">Observaciones de la revisión:</span> {{ $expense->review_notes }}</div>
                @endif
            </x-card>

            <x-card title="Soporte" icon="paperclip">
                @if ($expense->receipt_path)
                    @php $isPdf = str_ends_with(strtolower($expense->receipt_path), '.pdf'); @endphp
                    @if ($isPdf)
                        <iframe src="{{ route('expenses.receipt', $expense) }}" class="h-[500px] w-full rounded-lg border border-gray-200 dark:border-gray-700"></iframe>
                    @else
                        <img src="{{ route('expenses.receipt', $expense) }}" alt="Soporte" class="max-h-[500px] rounded-lg border border-gray-200 dark:border-gray-700">
                    @endif
                    <a href="{{ route('expenses.receipt', $expense) }}" target="_blank" class="mt-3 inline-block text-sm link">Abrir {{ $expense->receipt_original_name }}</a>
                @else
                    <x-empty icon="paperclip" title="Sin soporte adjunto" message="Se recomienda adjuntar la factura o recibo para agilizar la aprobación." />
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            @if ($expense->isPending() && auth()->user()->can('approve', $expense))
                <x-card title="Revisión" icon="shield">
                    <form method="POST" action="{{ route('expenses.approve', $expense) }}" class="space-y-3">
                        @csrf
                        <textarea name="review_notes" rows="2" class="form-control" placeholder="Observaciones (opcional)"></textarea>
                        <button class="btn btn-success w-full"><x-icon name="check-circle" class="h-4 w-4" /> Aprobar gasto</button>
                    </form>
                    <form method="POST" action="{{ route('expenses.reject', $expense) }}" class="mt-4 space-y-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                        @csrf
                        <x-field name="review_notes">
                            <textarea name="review_notes" rows="2" required class="form-control" placeholder="Motivo del rechazo (obligatorio)"></textarea>
                        </x-field>
                        <button class="btn btn-danger w-full"><x-icon name="x-circle" class="h-4 w-4" /> Rechazar</button>
                    </form>
                </x-card>
            @elseif ($expense->isPending())
                <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                    <x-icon name="clock" class="mb-1 h-5 w-5" /> Esperando aprobación del abogado responsable del caso ({{ $expense->legalCase?->lawyer?->name }}) o del administrador.
                </div>
            @endif

            @if ($expense->status === \App\Enums\ExpenseStatus::Approved)
                @can('reimburse', $expense)
                    <x-card title="Reembolso" icon="banknotes">
                        <p class="mb-3 text-sm text-gray-500">Marque el gasto como reembolsado cuando se haya devuelto el dinero a {{ $expense->user?->name }}.</p>
                        <form method="POST" action="{{ route('expenses.reimburse', $expense) }}" data-confirm="¿Confirmar el reembolso de {{ money($expense->amount) }}?">
                            @csrf
                            <button class="btn btn-success w-full"><x-icon name="banknotes" class="h-4 w-4" /> Marcar como reembolsado</button>
                        </form>
                    </x-card>
                @else
                    <div class="rounded-xl bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-500/10 dark:text-blue-200">Aprobado. El administrador registrará el reembolso.</div>
                @endcan
            @endif

            <x-card title="Información" icon="info">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-gray-500">Registró</dt><dd class="font-medium">{{ $expense->user?->name }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gray-500">Caso</dt><dd><a href="{{ route('cases.show', $expense->legal_case_id) }}" class="link">{{ $expense->legalCase?->case_number }}</a></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-gray-500">Responsable del caso</dt><dd class="font-medium">{{ $expense->legalCase?->lawyer?->name ?? '—' }}</dd></div>
                </dl>
            </x-card>
        </div>
    </div>
</x-app-layout>

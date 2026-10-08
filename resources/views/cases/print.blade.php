<x-print-layout :title="'Informe del caso '.$case->case_number">
    <x-slot name="headerRight">
        <p class="text-lg font-bold uppercase tracking-wide text-primary-700">Informe del caso</p>
        <p class="text-sm font-semibold">{{ $case->case_number }}</p>
        <p class="text-xs text-gray-600">{{ now()->format('d/m/Y') }}</p>
    </x-slot>

    @php
        $paid = (float) $case->payments->sum('amount');
        $section = 'mt-6 mb-2 border-b border-gray-300 pb-1 text-sm font-bold uppercase tracking-wide text-primary-700';
    @endphp

    <h1 class="mt-6 text-xl font-bold">{{ $case->title }}</h1>

    <table class="mt-4 w-full text-sm">
        <tbody>
        @foreach ([
            ['Cliente', $case->client->name.($case->client->document_label ? ' — '.$case->client->document_label : ''), 'Estado', $case->status?->name],
            ['Tipo de caso', $case->type?->name, 'Prioridad', $case->priority?->label()],
            ['Juzgado', $case->court?->full_name, 'Juez', $case->judge],
            ['Responsable', $case->lawyer?->name, 'Asistente', $case->assistant?->name],
            ['Radicación', fdate($case->filing_date), 'Cierre', $case->closed_at ? fdate($case->closed_at) : '—'],
        ] as [$l1, $v1, $l2, $v2])
            <tr class="border-b border-gray-100">
                <td class="w-32 py-1.5 pr-2 font-semibold text-gray-600">{{ $l1 }}</td><td class="py-1.5 pr-4">{{ $v1 ?: '—' }}</td>
                <td class="w-28 py-1.5 pr-2 font-semibold text-gray-600">{{ $l2 }}</td><td class="py-1.5">{{ $v2 ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if ($case->description)
        <h2 class="{{ $section }}">Descripción</h2>
        <p class="whitespace-pre-line text-sm">{{ $case->description }}</p>
    @endif

    <h2 class="{{ $section }}">Partes</h2>
    @if ($case->parties->isEmpty())<p class="text-sm text-gray-500">Sin partes registradas.</p>@else
        <table class="w-full text-sm"><thead><tr class="text-left text-xs uppercase text-gray-500"><th class="py-1">Nombre</th><th>Calidad</th><th>Documento</th><th>Apoderado</th></tr></thead><tbody>
        @foreach ($case->parties as $p)<tr class="border-t border-gray-100"><td class="py-1">{{ $p->name }}</td><td>{{ $p->role_label }}</td><td>{{ $p->document_number }}</td><td>{{ $p->lawyer_name }}</td></tr>@endforeach
        </tbody></table>
    @endif

    <h2 class="{{ $section }}">Audiencias</h2>
    @if ($case->hearings->isEmpty())<p class="text-sm text-gray-500">Sin audiencias.</p>@else
        <table class="w-full text-sm"><thead><tr class="text-left text-xs uppercase text-gray-500"><th class="py-1">Fecha</th><th>Audiencia</th><th>Lugar</th><th>Estado</th></tr></thead><tbody>
        @foreach ($case->hearings as $h)<tr class="border-t border-gray-100 align-top"><td class="whitespace-nowrap py-1 pr-2">{{ fdatetime($h->scheduled_at) }}</td><td>{{ $h->title }}@if($h->outcome)<div class="text-xs text-gray-500">{{ $h->outcome }}</div>@endif</td><td class="text-xs">{{ $h->location }}</td><td>{{ $h->status->label() }}</td></tr>@endforeach
        </tbody></table>
    @endif

    <h2 class="{{ $section }}">Resumen financiero</h2>
    <div class="grid grid-cols-4 gap-3 text-center text-sm">
        <div class="rounded border border-gray-200 p-2"><p class="text-xs text-gray-500">Honorarios</p><p class="font-bold">{{ money($case->fee_amount) }}</p></div>
        <div class="rounded border border-gray-200 p-2"><p class="text-xs text-gray-500">Pagado</p><p class="font-bold">{{ money($paid) }}</p></div>
        <div class="rounded border border-gray-200 p-2"><p class="text-xs text-gray-500">Saldo</p><p class="font-bold">{{ money(max(0, $case->fee_amount - $paid)) }}</p></div>
        <div class="rounded border border-gray-200 p-2"><p class="text-xs text-gray-500">Gastos aprobados</p><p class="font-bold">{{ money($case->expenses->whereIn('status.value', ['aprobado', 'reembolsado'])->sum('amount')) }}</p></div>
    </div>
    @if ($case->payments->isNotEmpty())
        <table class="mt-3 w-full text-sm"><thead><tr class="text-left text-xs uppercase text-gray-500"><th class="py-1">Recibo</th><th>Fecha</th><th>Concepto</th><th class="text-right">Valor</th></tr></thead><tbody>
        @foreach ($case->payments as $p)<tr class="border-t border-gray-100"><td class="py-1">{{ $p->receipt_number }}</td><td>{{ fdate($p->paid_at) }}</td><td>{{ $p->concept }}</td><td class="text-right">{{ money($p->amount) }}</td></tr>@endforeach
        </tbody></table>
    @endif

    <h2 class="{{ $section }}">Documentos ({{ $case->documents->count() }})</h2>
    <p class="text-sm">{{ $case->documents->pluck('title')->implode(' · ') ?: 'Sin documentos.' }}</p>

    @if ($case->notes->isNotEmpty())
        <h2 class="{{ $section }}">Notas</h2>
        @foreach ($case->notes as $n)
            <div class="mb-2 text-sm"><span class="text-xs font-semibold text-gray-500">{{ $n->created_at->format('d/m/Y') }} — {{ $n->user?->name }}:</span> {{ $n->body }}</div>
        @endforeach
    @endif

    <div class="mt-16 grid grid-cols-2 gap-16 text-center text-xs">
        <div class="border-t border-gray-400 pt-2">{{ $case->lawyer?->name }}<br>{{ $case->lawyer?->professional_id }}</div>
        <div class="border-t border-gray-400 pt-2">Recibido por el cliente</div>
    </div>
</x-print-layout>

<x-print-layout :title="'Recibo '.$payment->receipt_number">
    <x-slot name="headerRight">
        <p class="text-lg font-bold uppercase tracking-wide text-primary-700">Recibo de caja</p>
        <p class="text-2xl font-bold">N.º {{ $payment->receipt_number }}</p>
        <p class="text-sm text-gray-600">Fecha: {{ fdate($payment->paid_at) }}</p>
    </x-slot>

    <div class="no-print mt-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('payments.index') }}" class="btn btn-secondary btn-sm">Ir a pagos</a>
        @can('update', $payment)<a href="{{ route('payments.edit', $payment) }}" class="btn btn-secondary btn-sm">Editar</a>@endcan
        <a href="{{ route('clients.show', $payment->client) }}" class="btn btn-secondary btn-sm">Ver cliente</a>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-6 text-sm">
        <div>
            <p class="text-xs font-semibold uppercase text-gray-500">Recibimos de</p>
            <p class="text-base font-semibold">{{ $payment->client->name }}</p>
            <p>{{ $payment->client->document_label }}</p>
            <p class="text-gray-600">{{ $payment->client->address }} {{ $payment->client->city }}</p>
            <p class="text-gray-600">{{ $payment->client->phone }} {{ $payment->client->email ? '· '.$payment->client->email : '' }}</p>
        </div>
        <div class="rounded-lg border-2 border-primary-600 p-4 text-center">
            <p class="text-xs font-semibold uppercase text-gray-500">Valor recibido</p>
            <p class="text-3xl font-bold text-primary-700">{{ money($payment->amount) }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-lg bg-gray-50 p-4 text-sm">
        <p><span class="font-semibold">La suma de:</span> {{ $amountInWords }}</p>
    </div>

    <table class="mt-6 w-full text-sm">
        <tbody>
            <tr class="border-b border-gray-200"><td class="w-48 py-2 font-semibold text-gray-600">Concepto</td><td class="py-2">{{ $payment->concept }}</td></tr>
            @if ($payment->legalCase)
                <tr class="border-b border-gray-200"><td class="py-2 font-semibold text-gray-600">Caso</td><td class="py-2">{{ $payment->legalCase->case_number }} — {{ $payment->legalCase->title }}</td></tr>
            @endif
            @if ($payment->appointment)
                <tr class="border-b border-gray-200"><td class="py-2 font-semibold text-gray-600">Consulta</td><td class="py-2">{{ $payment->appointment->title }} ({{ $payment->appointment->starts_at->format('d/m/Y') }})</td></tr>
            @endif
            <tr class="border-b border-gray-200"><td class="py-2 font-semibold text-gray-600">Medio de pago</td><td class="py-2">{{ $payment->method->label() }} {{ $payment->reference ? '· Ref. '.$payment->reference : '' }}</td></tr>
            @if ($payment->notes)<tr class="border-b border-gray-200"><td class="py-2 font-semibold text-gray-600">Observaciones</td><td class="py-2">{{ $payment->notes }}</td></tr>@endif
        </tbody>
    </table>

    @if ($caseTotals)
        <div class="mt-6 grid grid-cols-3 gap-3 text-center text-sm">
            <div class="rounded border border-gray-200 p-2"><p class="text-xs text-gray-500">Honorarios pactados</p><p class="font-bold">{{ money($caseTotals['fee']) }}</p></div>
            <div class="rounded border border-gray-200 p-2"><p class="text-xs text-gray-500">Total abonado a la fecha</p><p class="font-bold">{{ money($caseTotals['paid']) }}</p></div>
            <div class="rounded border border-gray-200 p-2"><p class="text-xs text-gray-500">Saldo pendiente</p><p class="font-bold">{{ money($caseTotals['balance']) }}</p></div>
        </div>
    @endif

    <div class="mt-20 grid grid-cols-2 gap-16 text-center text-xs">
        <div class="border-t border-gray-400 pt-2">Recibido por: {{ $payment->user?->name }}<br>{{ setting('firm_name') }}</div>
        <div class="border-t border-gray-400 pt-2">Firma del cliente<br>{{ $payment->client->name }}</div>
    </div>

    @if (setting('receipt_footer'))
        <p class="mt-8 text-center text-xs italic text-gray-500">{{ setting('receipt_footer') }}</p>
    @endif
</x-print-layout>

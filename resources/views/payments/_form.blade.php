@php
    $casesJson = $cases->map(fn ($c) => [
        'id' => $c->id, 'client_id' => $c->client_id, 'label' => $c->case_number.' — '.\Illuminate\Support\Str::limit($c->title, 50),
        'balance' => max(0, (float) $c->fee_amount - (float) $c->payments_sum_amount),
    ])->values();
    $appointmentsJson = $appointments->map(fn ($a) => [
        'id' => $a->id, 'client_id' => $a->client_id, 'label' => $a->starts_at->format('d/m/Y').' — '.$a->title, 'fee' => (float) $a->fee,
    ])->values();
@endphp
<div class="grid grid-cols-1 gap-6 xl:grid-cols-3"
     x-data="{
        client: '{{ old('client_id', $payment->client_id) }}',
        caseId: '{{ old('legal_case_id', $payment->legal_case_id) }}',
        appointmentId: '{{ old('appointment_id', $payment->appointment_id) }}',
        cases: @js($casesJson),
        appointments: @js($appointmentsJson),
        get clientCases() { return this.cases.filter(c => String(c.client_id) === String(this.client)) },
        get clientAppointments() { return this.appointments.filter(a => String(a.client_id) === String(this.client)) },
        get selectedCase() { return this.cases.find(c => String(c.id) === String(this.caseId)) },
     }">
    <x-card title="Datos del pago" icon="banknotes" class="xl:col-span-2">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-6">
            <x-field label="Cliente" name="client_id" required class="md:col-span-6">
                <select name="client_id" x-model="client" @change="caseId = ''; appointmentId = ''" required class="form-control">
                    <option value="">Seleccione…</option>
                    @foreach ($clients as $c)<option value="{{ $c->id }}" @selected(old('client_id', $payment->client_id) == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Caso (honorarios)" name="legal_case_id" class="md:col-span-3">
                <select name="legal_case_id" x-model="caseId" class="form-control">
                    <option value="">— Sin caso —</option>
                    <template x-for="c in clientCases" :key="c.id"><option :value="c.id" x-text="c.label" :selected="String(c.id) === String(caseId)"></option></template>
                </select>
                <p class="form-hint" x-show="selectedCase" x-cloak>Saldo pendiente: <strong x-text="'{{ setting('currency_symbol', '$') }} ' + Number(selectedCase?.balance || 0).toLocaleString('es-CO')"></strong></p>
            </x-field>
            <x-field label="Cita / consulta" name="appointment_id" class="md:col-span-3">
                <select name="appointment_id" x-model="appointmentId" class="form-control">
                    <option value="">— Ninguna —</option>
                    <template x-for="a in clientAppointments" :key="a.id"><option :value="a.id" x-text="a.label" :selected="String(a.id) === String(appointmentId)"></option></template>
                </select>
            </x-field>
            <x-field label="Concepto" name="concept" required class="md:col-span-6">
                <input name="concept" value="{{ old('concept', $payment->concept) }}" required class="form-control" placeholder="Ej. Abono a honorarios — cuota 2">
            </x-field>
            <x-field label="Valor" name="amount" required class="md:col-span-2">
                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">{{ setting('currency_symbol', '$') }}</span>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $payment->amount ? (float) $payment->amount : '') }}" required class="form-control pl-8 text-lg font-semibold">
                </div>
            </x-field>
            <x-field label="Fecha de pago" name="paid_at" required class="md:col-span-2">
                <input type="date" name="paid_at" value="{{ old('paid_at', $payment->paid_at?->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" required class="form-control">
            </x-field>
            <x-field label="Medio de pago" name="method" required class="md:col-span-2">
                <select name="method" class="form-control">
                    @foreach (\App\Enums\PaymentMethod::options() as $v => $l)<option value="{{ $v }}" @selected(old('method', $payment->method?->value) === $v)>{{ $l }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Referencia / N.º de transacción" name="reference" class="md:col-span-3">
                <input name="reference" value="{{ old('reference', $payment->reference) }}" class="form-control">
            </x-field>
            <x-field label="Observaciones" name="notes" class="md:col-span-3">
                <input name="notes" value="{{ old('notes', $payment->notes) }}" class="form-control">
            </x-field>
        </div>
    </x-card>
    <div class="rounded-xl bg-primary-50 p-5 text-sm text-primary-900 dark:bg-primary-500/10 dark:text-primary-100">
        <x-icon name="receipt" class="mb-2 h-8 w-8 text-primary-500" />
        <p class="font-semibold">Recibo automático</p>
        <p class="mt-1">Al guardar se genera un número de recibo consecutivo ({{ setting('receipt_prefix', 'REC') }}-{{ now()->year }}-00001) y podrá imprimirlo o guardarlo en PDF con el logotipo del bufete y el valor en letras.</p>
    </div>
</div>

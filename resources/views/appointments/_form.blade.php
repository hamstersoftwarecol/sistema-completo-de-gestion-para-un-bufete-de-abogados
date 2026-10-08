<input type="hidden" name="redirect" value="{{ request('redirect', old('redirect')) }}">
<div class="grid grid-cols-1 gap-6 xl:grid-cols-3"
     x-data="{ prospect: {{ old('client_id', $appointment->client_id) ? 'false' : ($appointment->exists && $appointment->contact_name ? 'true' : 'false') }}, mode: '{{ old('mode', $appointment->mode?->value ?? 'presencial') }}' }">
    <x-card title="Datos de la cita" icon="clock" class="xl:col-span-2">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-6">
            <div class="md:col-span-6">
                <div class="inline-flex rounded-lg bg-gray-100 p-1 text-sm dark:bg-gray-700/60">
                    <button type="button" @click="prospect = false" class="rounded-md px-3 py-1 font-medium" :class="!prospect ? 'bg-white shadow dark:bg-gray-800' : 'text-gray-500'">Cliente registrado</button>
                    <button type="button" @click="prospect = true" class="rounded-md px-3 py-1 font-medium" :class="prospect ? 'bg-white shadow dark:bg-gray-800' : 'text-gray-500'">Prospecto / nueva consulta</button>
                </div>
            </div>
            <x-field label="Cliente" name="client_id" class="md:col-span-3" x-show="!prospect">
                <select name="client_id" class="form-control" :disabled="prospect">
                    <option value="">Seleccione…</option>
                    @foreach ($clients as $c)<option value="{{ $c->id }}" @selected(old('client_id', $appointment->client_id) == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Caso relacionado" name="legal_case_id" class="md:col-span-3" x-show="!prospect">
                <select name="legal_case_id" class="form-control" :disabled="prospect">
                    <option value="">— Ninguno —</option>
                    @foreach ($cases as $c)<option value="{{ $c->id }}" @selected(old('legal_case_id', $appointment->legal_case_id) == $c->id)>{{ $c->case_number }} ({{ $c->client?->name }})</option>@endforeach
                </select>
            </x-field>
            <x-field label="Nombre de la persona" name="contact_name" class="md:col-span-3" x-show="prospect" x-cloak>
                <input name="contact_name" value="{{ old('contact_name', $appointment->contact_name) }}" class="form-control" :disabled="!prospect">
            </x-field>
            <x-field label="Teléfono de contacto" name="contact_phone" class="md:col-span-3" x-show="prospect" x-cloak>
                <input name="contact_phone" value="{{ old('contact_phone', $appointment->contact_phone) }}" class="form-control" :disabled="!prospect">
            </x-field>
            <x-field label="Asunto" name="title" required class="md:col-span-6">
                <input name="title" value="{{ old('title', $appointment->title) }}" required class="form-control" placeholder="Ej. Consulta inicial — proceso laboral">
            </x-field>
            <x-field label="Fecha y hora" name="starts_at" required class="md:col-span-3">
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $appointment->starts_at?->format('Y-m-d\TH:i')) }}" required class="form-control">
            </x-field>
            <x-field label="Duración" name="duration_minutes" required class="md:col-span-3">
                <select name="duration_minutes" class="form-control">
                    @foreach ([15, 30, 45, 60, 90, 120] as $m)<option value="{{ $m }}" @selected(old('duration_minutes', $appointment->duration_minutes) == $m)>{{ $m }} minutos</option>@endforeach
                </select>
            </x-field>
            <x-field label="Modalidad" name="mode" required class="md:col-span-3">
                <select name="mode" x-model="mode" class="form-control">
                    @foreach (\App\Enums\AppointmentMode::options() as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                </select>
            </x-field>
            <x-field name="location" class="md:col-span-3">
                <label class="form-label" x-text="mode === 'videollamada' ? 'Enlace de la videollamada' : (mode === 'telefonica' ? 'Número a llamar' : 'Lugar')"></label>
                <input name="location" value="{{ old('location', $appointment->location) }}" class="form-control">
            </x-field>
            <x-field label="Notas" name="notes" class="md:col-span-6">
                <textarea name="notes" rows="3" class="form-control">{{ old('notes', $appointment->notes) }}</textarea>
            </x-field>
        </div>
    </x-card>

    <div class="space-y-6">
        <x-card title="Abogado y tarifa" icon="banknotes">
            <div class="space-y-4">
                <x-field label="Abogado" name="user_id" required>
                    <select name="user_id" class="form-control">
                        @foreach ($lawyers as $l)<option value="{{ $l->id }}" @selected(old('user_id', $appointment->user_id) == $l->id)>{{ $l->name }}</option>@endforeach
                    </select>
                </x-field>
                <x-field label="Estado" name="status" required>
                    <select name="status" class="form-control">
                        @foreach (\App\Enums\AppointmentStatus::options() as $v => $l)<option value="{{ $v }}" @selected(old('status', $appointment->status?->value) === $v)>{{ $l }}</option>@endforeach
                    </select>
                </x-field>
                <x-field label="Valor de la consulta" name="fee" hint="Deje 0 si la cita no tiene costo.">
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">{{ setting('currency_symbol', '$') }}</span>
                        <input type="number" min="0" step="0.01" name="fee" value="{{ old('fee', (float) $appointment->fee) }}" class="form-control pl-8">
                    </div>
                </x-field>
            </div>
        </x-card>
        @if ($appointment->exists && $appointment->fee > 0)
            <x-card title="Pagos de la consulta" icon="receipt">
                @forelse ($appointment->payments as $p)
                    <a href="{{ route('payments.show', $p) }}" class="flex justify-between py-1 text-sm hover:text-primary-600"><span>{{ $p->receipt_number }}</span><span class="font-semibold">{{ money($p->amount) }}</span></a>
                @empty
                    <p class="text-sm text-gray-500">Aún no se ha registrado el pago.</p>
                @endforelse
                @if ($appointment->client_id && $appointment->payments->sum('amount') < $appointment->fee)
                    <a href="{{ route('payments.create', ['appointment_id' => $appointment->id]) }}" class="btn btn-success btn-sm mt-3 w-full"><x-icon name="banknotes" class="h-4 w-4" /> Registrar pago</a>
                @elseif (! $appointment->client_id)
                    <p class="mt-2 text-xs text-gray-500">Registre a la persona como cliente para poder emitir el recibo.</p>
                @endif
            </x-card>
        @endif
    </div>
</div>

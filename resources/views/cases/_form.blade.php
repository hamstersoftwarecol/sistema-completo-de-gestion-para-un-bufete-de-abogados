<div class="space-y-6">
    <x-card title="Identificación del caso" icon="briefcase">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-6">
            <x-field name="case_number" required class="md:col-span-3"
                     x-data="caseNumberCheck('{{ route('cases.check-number') }}', {{ $case->id ?? 'null' }}, {{ \Illuminate\Support\Js::from((string) old('case_number', $case->case_number)) }})">
                <label for="case_number" class="form-label">Número de caso / radicado <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input id="case_number" name="case_number" x-model="value" @input="check()" required autocomplete="off"
                           placeholder="Ej. 11001-31-03-005-2026-00123-00" class="form-control pr-9"
                           :class="{ '!border-red-500 !ring-red-500': status === 'duplicate', '!border-emerald-500': status === 'ok' }">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg x-show="status === 'checking'" x-cloak class="h-4 w-4 animate-spin text-gray-400" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>
                        <x-icon name="check-circle" class="h-4 w-4 text-emerald-500" x-show="status === 'ok'" x-cloak />
                        <x-icon name="warning" class="h-4 w-4 text-red-500" x-show="status === 'duplicate'" x-cloak />
                    </span>
                </div>
                <p x-show="status === 'duplicate'" x-cloak class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">
                    <span x-text="message"></span>
                    <a x-show="link" :href="link" class="underline" target="_blank">Ver caso</a>
                </p>
                <p x-show="status === 'ok'" x-cloak class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">Número disponible.</p>
            </x-field>
            <x-field label="Fecha de radicación" name="filing_date" class="md:col-span-3">
                <input type="date" id="filing_date" name="filing_date" value="{{ old('filing_date', $case->filing_date?->format('Y-m-d')) }}" class="form-control">
            </x-field>
            <x-field label="Título / asunto" name="title" required class="md:col-span-6">
                <input id="title" name="title" value="{{ old('title', $case->title) }}" required class="form-control" placeholder="Ej. Proceso ejecutivo — cobro de pagaré">
            </x-field>
            <x-field label="Cliente" name="client_id" required class="md:col-span-3">
                <select id="client_id" name="client_id" required class="form-control">
                    <option value="">Seleccione…</option>
                    @foreach ($clients as $c)
                        <option value="{{ $c->id }}" @selected(old('client_id', $case->client_id) == $c->id)>{{ $c->name }}{{ $c->document_number ? ' · '.$c->document_number : '' }}</option>
                    @endforeach
                </select>
                <p class="form-hint">¿No está en la lista? <a href="{{ route('clients.create') }}" class="link" target="_blank">Registrar cliente</a></p>
            </x-field>
            <x-field label="Tipo de caso" name="case_type_id" class="md:col-span-3">
                <select id="case_type_id" name="case_type_id" class="form-control">
                    <option value="">—</option>
                    @foreach ($types as $t)<option value="{{ $t->id }}" @selected(old('case_type_id', $case->case_type_id) == $t->id)>{{ $t->name }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Estado" name="case_status_id" required class="md:col-span-2">
                <select id="case_status_id" name="case_status_id" required class="form-control">
                    @foreach ($statuses as $s)<option value="{{ $s->id }}" @selected(old('case_status_id', $case->case_status_id) == $s->id)>{{ $s->name }}{{ $s->is_closed ? ' (cierre)' : '' }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Prioridad" name="priority" required class="md:col-span-2">
                <select id="priority" name="priority" class="form-control">
                    @foreach (\App\Enums\Priority::options() as $v => $l)<option value="{{ $v }}" @selected(old('priority', $case->priority?->value) === $v)>{{ $l }}</option>@endforeach
                </select>
            </x-field>
        </div>
    </x-card>

    <x-card title="Despacho judicial" icon="scale">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-6">
            <x-field label="Juzgado / tribunal" name="court_id" class="md:col-span-3">
                <select id="court_id" name="court_id" class="form-control">
                    <option value="">—</option>
                    @foreach ($courts as $ct)<option value="{{ $ct->id }}" @selected(old('court_id', $case->court_id) == $ct->id)>{{ $ct->full_name }}</option>@endforeach
                </select>
                @can('admin')<p class="form-hint">Administre la lista en <a href="{{ route('admin.master-data.index') }}#juzgados" class="link">Datos maestros</a>.</p>@endcan
            </x-field>
            <x-field label="Juez / magistrado ponente" name="judge" class="md:col-span-3">
                <input id="judge" name="judge" value="{{ old('judge', $case->judge) }}" class="form-control">
            </x-field>
        </div>
    </x-card>

    <x-card title="Equipo y honorarios" icon="users">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-6">
            <x-field label="Abogado responsable" name="lawyer_id" class="md:col-span-3">
                <select id="lawyer_id" name="lawyer_id" class="form-control">
                    <option value="">—</option>
                    @foreach ($lawyers as $l)<option value="{{ $l->id }}" @selected(old('lawyer_id', $case->lawyer_id) == $l->id)>{{ $l->name }} · {{ $l->role->label() }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Abogado asistente" name="assistant_id" class="md:col-span-3">
                <select id="assistant_id" name="assistant_id" class="form-control">
                    <option value="">—</option>
                    @foreach ($lawyers as $l)<option value="{{ $l->id }}" @selected(old('assistant_id', $case->assistant_id) == $l->id)>{{ $l->name }} · {{ $l->role->label() }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Honorarios pactados" name="fee_amount" class="md:col-span-2">
                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">{{ setting('currency_symbol', '$') }}</span>
                    <input type="number" step="0.01" min="0" id="fee_amount" name="fee_amount" value="{{ old('fee_amount', $case->fee_amount ? (float) $case->fee_amount : '') }}" class="form-control pl-8">
                </div>
            </x-field>
            <x-field label="Forma de pago de honorarios" name="fee_notes" class="md:col-span-4">
                <input id="fee_notes" name="fee_notes" value="{{ old('fee_notes', $case->fee_notes) }}" class="form-control" placeholder="Ej. 30% anticipo, saldo al fallo">
            </x-field>
            <x-field label="Descripción / estrategia" name="description" class="md:col-span-6">
                <textarea id="description" name="description" rows="4" class="form-control">{{ old('description', $case->description) }}</textarea>
            </x-field>
        </div>
    </x-card>

    @unless ($case->exists)
        <x-card title="Documentos iniciales (opcional)" icon="upload">
            <x-field name="documents" hint="PDF, imágenes, audio, video u ofimática. Hasta 10 archivos de {{ round(config('bufete.upload_max_kb') / 1024) }} MB cada uno.">
                <input type="file" name="documents[]" multiple accept=".{{ str_replace(',', ',.', config('bufete.document_mimes')) }}"
                       class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100 dark:text-gray-300 dark:file:bg-primary-500/10 dark:file:text-primary-300">
            </x-field>
            @error('documents.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </x-card>
    @endunless
</div>

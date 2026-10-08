<x-app-layout :title="$client->name">
    @php $tab = request('tab', 'casos'); @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('clients.index') }}" class="btn btn-ghost btn-sm -ml-2" title="Volver"><x-icon name="arrow-left" class="h-5 w-5" /></a>
                <x-avatar :name="$client->name" size="h-14 w-14 text-lg" />
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $client->name }}</h1>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                        <x-badge :color="$client->type->color()">{{ $client->type->label() }}</x-badge>
                        @if ($client->document_label)<span>{{ $client->document_label }}</span>@endif
                        <span>· Cliente desde {{ $client->created_at->translatedFormat('M Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($client->phone)
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $client->phone) }}" class="btn btn-secondary" title="Llamar"><x-icon name="phone" class="h-4 w-4" /> Llamar</a>
                    <a href="{{ $client->whatsapp_url }}" target="_blank" rel="noopener" class="btn btn-secondary text-emerald-600" title="WhatsApp"><x-icon name="whatsapp" class="h-4 w-4" /> WhatsApp</a>
                @endif
                @if ($client->email)
                    <button type="button" class="btn btn-secondary" x-data @click="$dispatch('open-modal', 'email-client')"><x-icon name="envelope" class="h-4 w-4" /> Correo</button>
                @endif
                <button type="button" class="btn btn-secondary" x-data @click="$dispatch('open-modal', 'log-communication')"><x-icon name="clipboard" class="h-4 w-4" /> Registrar contacto</button>
                <a href="{{ route('cases.create', ['client_id' => $client->id]) }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Nuevo caso</a>
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger"><button class="btn btn-secondary px-2.5"><x-icon name="ellipsis" class="h-5 w-5" /></button></x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('appointments.create', ['client_id' => $client->id])">Agendar cita</x-dropdown-link>
                        <x-dropdown-link :href="route('payments.create', ['client_id' => $client->id])">Registrar pago</x-dropdown-link>
                        @can('update', $client)<x-dropdown-link :href="route('clients.edit', $client)">Editar cliente</x-dropdown-link>@endcan
                        @can('delete', $client)
                            <form method="POST" action="{{ route('clients.destroy', $client) }}" data-confirm="¿Eliminar el cliente {{ $client->name }}?">
                                @csrf @method('DELETE')
                                <button class="block w-full px-4 py-2 text-start text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Eliminar cliente</button>
                            </form>
                        @endcan
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </x-slot>

    {{-- Resumen 360 --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
        <x-stat label="Casos activos" :value="$totals['open_cases'].' / '.$cases->count()" icon="briefcase" />
        <x-stat label="Honorarios pactados" :value="money($totals['fees'])" icon="scale" color="violet" />
        <x-stat label="Total pagado" :value="money($totals['paid'])" icon="banknotes" color="emerald" />
        <x-stat label="Saldo pendiente" :value="money($totals['balance'])" icon="trending-up" :color="$totals['balance'] > 0 ? 'amber' : 'emerald'" />
        <x-stat label="Próxima audiencia" :value="$totals['next_hearing']?->scheduled_at->format('d/m/Y') ?? '—'" icon="calendar" color="rose" :hint="$totals['next_hearing']?->title" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-4">
        {{-- Ficha --}}
        <div class="space-y-6">
            <x-card title="Ficha del cliente" icon="identification">
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        ['envelope', 'Correo', $client->email ? '<a class="link" href="mailto:'.e($client->email).'">'.e($client->email).'</a>' : null],
                        ['phone', 'Teléfono', $client->phone ? '<a class="link" href="tel:'.e($client->phone).'">'.e($client->phone).'</a>' : null],
                        ['phone', 'Teléfono alterno', e($client->alt_phone)],
                        ['map-pin', 'Dirección', e(trim($client->address.($client->city ? ', '.$client->city : ''), ', '))],
                        ['briefcase', $client->type->value === 'empresa' ? 'Actividad' : 'Ocupación', e($client->occupation)],
                        ['user', 'Contacto / representante', e($client->contact_person)],
                        ['shield', 'Abogado a cargo', e($client->lawyer?->name)],
                    ] as [$icon, $label, $value])
                        @if (filled($value))
                            <div class="flex gap-3">
                                <x-icon :name="$icon" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
                                <div class="min-w-0">
                                    <dt class="text-xs text-gray-500">{{ $label }}</dt>
                                    <dd class="break-words font-medium text-gray-800 dark:text-gray-200">{!! $value !!}</dd>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </dl>
                @if ($client->notes)
                    <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                        <p class="mb-1 text-xs font-semibold uppercase">Notas internas</p>
                        {!! nl2br(e($client->notes)) !!}
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Pestañas --}}
        <div class="xl:col-span-3" x-data="{ tab: window.location.hash ? window.location.hash.substring(1) : '{{ $tab }}' }" x-init="$watch('tab', t => history.replaceState(null, '', '#' + t))">
            <div class="card">
                <div class="flex gap-6 overflow-x-auto border-b border-gray-200 px-5 dark:border-gray-700">
                    @foreach ([
                        'casos' => ['briefcase', 'Casos', $cases->count()],
                        'pagos' => ['banknotes', 'Pagos', $payments->count()],
                        'audiencias' => ['scale', 'Audiencias', $hearings->count()],
                        'documentos' => ['document', 'Documentos', $documents->count()],
                        'citas' => ['clock', 'Citas', $appointments->count()],
                        'comunicaciones' => ['chat', 'Comunicaciones', $communications->count()],
                    ] as $key => [$icon, $label, $count])
                        <button type="button" @click="tab = '{{ $key }}'" class="tab" :class="tab === '{{ $key }}' && 'tab-active'">
                            <x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}
                            <span class="rounded-full bg-gray-100 px-1.5 text-xs dark:bg-gray-700">{{ $count }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Casos --}}
                <div x-show="tab === 'casos'" class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Caso</th><th>Tipo</th><th>Estado</th><th>Responsable</th><th class="text-right">Honorarios</th><th class="text-right">Saldo</th></tr></thead>
                        <tbody>
                        @forelse ($cases as $c)
                            <tr>
                                <td><a href="{{ route('cases.show', $c) }}" class="link">{{ $c->case_number }}</a><p class="max-w-xs truncate text-xs text-gray-500">{{ $c->title }}</p></td>
                                <td>{{ $c->type?->name ?? '—' }}</td>
                                <td><x-badge :color="$c->status?->color">{{ $c->status?->name }}</x-badge></td>
                                <td class="whitespace-nowrap">{{ $c->lawyer?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap text-right">{{ money($c->fee_amount) }}</td>
                                <td class="whitespace-nowrap text-right font-semibold">{{ money(max(0, $c->fee_amount - $c->payments_sum_amount)) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty icon="briefcase" title="Sin casos registrados"><a href="{{ route('cases.create', ['client_id' => $client->id]) }}" class="btn btn-primary btn-sm">Crear caso</a></x-empty></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagos --}}
                <div x-show="tab === 'pagos'" x-cloak class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Recibo</th><th>Fecha</th><th>Concepto</th><th>Caso</th><th>Medio</th><th class="text-right">Valor</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($payments as $p)
                            <tr>
                                <td class="whitespace-nowrap font-medium">{{ $p->receipt_number }}</td>
                                <td class="whitespace-nowrap">{{ fdate($p->paid_at) }}</td>
                                <td>{{ $p->concept }}</td>
                                <td class="whitespace-nowrap text-xs">{{ $p->legalCase?->case_number ?? '—' }}</td>
                                <td><x-badge :color="$p->method->color()">{{ $p->method->label() }}</x-badge></td>
                                <td class="whitespace-nowrap text-right font-semibold">{{ money($p->amount) }}</td>
                                <td class="text-right"><a href="{{ route('payments.show', $p) }}" class="btn btn-ghost btn-sm" title="Recibo"><x-icon name="printer" class="h-4 w-4" /></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-empty icon="banknotes" title="Sin pagos registrados" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Audiencias --}}
                <div x-show="tab === 'audiencias'" x-cloak class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Fecha</th><th>Audiencia</th><th>Caso</th><th>Lugar</th><th>Estado</th></tr></thead>
                        <tbody>
                        @forelse ($hearings as $h)
                            <tr>
                                <td class="whitespace-nowrap">{{ fdatetime($h->scheduled_at) }}</td>
                                <td><a href="{{ route('hearings.edit', $h) }}" class="link">{{ $h->title }}</a></td>
                                <td class="whitespace-nowrap text-xs">{{ $h->legalCase?->case_number }}</td>
                                <td class="text-xs">{{ $h->location ?: '—' }}</td>
                                <td><x-badge :color="$h->status->color()">{{ $h->status->label() }}</x-badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty icon="scale" title="Sin audiencias" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Documentos --}}
                <div x-show="tab === 'documentos'" x-cloak class="p-5">
                    @include('cases.partials.documents-grid', ['documents' => $documents, 'showCase' => true])
                </div>

                {{-- Citas --}}
                <div x-show="tab === 'citas'" x-cloak class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Fecha</th><th>Asunto</th><th>Abogado</th><th>Modalidad</th><th>Estado</th></tr></thead>
                        <tbody>
                        @forelse ($appointments as $a)
                            <tr>
                                <td class="whitespace-nowrap">{{ fdatetime($a->starts_at) }}</td>
                                <td><a href="{{ route('appointments.edit', $a) }}" class="link">{{ $a->title }}</a></td>
                                <td>{{ $a->user?->name }}</td>
                                <td>{{ $a->mode->label() }}</td>
                                <td><x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty icon="clock" title="Sin citas"><a href="{{ route('appointments.create', ['client_id' => $client->id]) }}" class="btn btn-primary btn-sm">Agendar cita</a></x-empty></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Comunicaciones --}}
                <div x-show="tab === 'comunicaciones'" x-cloak class="p-5">
                    <ol class="relative space-y-5 border-l border-gray-200 pl-6 dark:border-gray-700">
                        @forelse ($communications as $com)
                            <li class="relative">
                                <span class="absolute -left-[34px] flex h-7 w-7 items-center justify-center rounded-full ring-4 ring-white dark:ring-gray-800 {{ \App\Support\Badge::classes($com->type->color()) }}">
                                    <x-icon :name="match($com->type->value) { 'llamada' => 'phone', 'email' => 'envelope', 'whatsapp' => 'whatsapp', 'reunion' => 'users', default => 'chat' }" class="h-3.5 w-3.5" />
                                </span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold">{{ $com->type->label() }}@if($com->subject): {{ $com->subject }}@endif</p>
                                        <p class="text-xs text-gray-500">{{ $com->user?->name }} · {{ $com->created_at->format('d/m/Y h:i a') }} @if($com->legalCase)· Caso {{ $com->legalCase->case_number }}@endif</p>
                                        @if ($com->body)<p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $com->body }}</p>@endif
                                    </div>
                                    @if ($com->user_id === auth()->id() || auth()->user()->isSuperadmin())
                                        <x-delete-button :action="route('communications.destroy', $com)" confirm="¿Eliminar este registro?" />
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li><x-empty icon="chat" title="Sin comunicaciones registradas" message="Registre llamadas, correos o reuniones con el cliente." /></li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: enviar correo --}}
    <x-dialog name="email-client" title="Enviar correo al cliente" max-width="2xl" :show="$errors->has('subject') && old('_form') === 'email'">
        <form method="POST" action="{{ route('clients.email', $client) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="email">
            <p class="text-sm text-gray-500">Para: <strong>{{ $client->name }}</strong> &lt;{{ $client->email }}&gt;</p>
            <x-field label="Caso relacionado" name="legal_case_id">
                <select name="legal_case_id" class="form-control">
                    <option value="">— Ninguno —</option>
                    @foreach ($cases as $c)<option value="{{ $c->id }}">{{ $c->case_number }} — {{ $c->title }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Asunto" name="subject" required>
                <input name="subject" value="{{ old('subject') }}" required class="form-control">
            </x-field>
            <x-field label="Mensaje" name="body" required>
                <textarea name="body" rows="7" required class="form-control">{{ old('body', "Estimado(a) {$client->name}:\n\n\n\nCordialmente,\n".auth()->user()->name."\n".setting('firm_name')) }}</textarea>
            </x-field>
            <p class="form-hint">El correo se envía con la configuración SMTP del archivo .env y queda registrado en el historial de comunicaciones. También puede <a class="link" href="mailto:{{ $client->email }}">abrir su programa de correo</a>.</p>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" x-on:click="$dispatch('close')">Cancelar</button>
                <button class="btn btn-primary"><x-icon name="send" class="h-4 w-4" /> Enviar</button>
            </div>
        </form>
    </x-dialog>

    {{-- Modal: registrar comunicación --}}
    <x-dialog name="log-communication" title="Registrar contacto con el cliente">
        <form method="POST" action="{{ route('clients.communications.store', $client) }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <x-field label="Tipo" name="type" required>
                    <select name="type" class="form-control">
                        @foreach (\App\Enums\CommunicationType::options() as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                    </select>
                </x-field>
                <x-field label="Caso" name="legal_case_id">
                    <select name="legal_case_id" class="form-control">
                        <option value="">—</option>
                        @foreach ($cases as $c)<option value="{{ $c->id }}">{{ $c->case_number }}</option>@endforeach
                    </select>
                </x-field>
            </div>
            <x-field label="Asunto" name="subject"><input name="subject" class="form-control" placeholder="Ej. Llamada de seguimiento"></x-field>
            <x-field label="Detalle" name="body"><textarea name="body" rows="4" class="form-control" placeholder="¿Qué se habló? ¿Compromisos?"></textarea></x-field>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" x-on:click="$dispatch('close')">Cancelar</button>
                <button class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </x-dialog>
</x-app-layout>

<x-app-layout :title="'Caso '.$case->case_number">
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <a href="{{ route('cases.index') }}" class="btn btn-ghost btn-sm -ml-2 mt-1" title="Volver"><x-icon name="arrow-left" class="h-5 w-5" /></a>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">{{ $case->case_number }}</h1>
                        <x-badge :color="$case->status?->color">{{ $case->status?->name ?? 'Sin estado' }}</x-badge>
                        <x-badge :color="$case->priority?->color()">Prioridad {{ mb_strtolower($case->priority?->label() ?? '') }}</x-badge>
                    </div>
                    <p class="mt-1 text-gray-600 dark:text-gray-300">{{ $case->title }}</p>
                    <p class="mt-0.5 text-sm text-gray-500">
                        Cliente: <a href="{{ route('clients.show', $case->client) }}" class="link">{{ $case->client->name }}</a>
                        @if ($case->type) · {{ $case->type->name }} @endif
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('cases.print', $case) }}" target="_blank" class="btn btn-secondary"><x-icon name="printer" class="h-4 w-4" /> Informe</a>
                @can('update', $case)
                    <a href="{{ route('cases.edit', $case) }}" class="btn btn-secondary"><x-icon name="pencil" class="h-4 w-4" /> Editar</a>
                @endcan
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger"><button class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Agregar <x-icon name="chevron-down" class="h-4 w-4" /></button></x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('hearings.create', ['case_id' => $case->id, 'redirect' => 'case'])">Programar audiencia</x-dropdown-link>
                        <x-dropdown-link :href="route('appointments.create', ['case_id' => $case->id, 'client_id' => $case->client_id])">Agendar cita</x-dropdown-link>
                        <x-dropdown-link :href="route('payments.create', ['case_id' => $case->id])">Registrar pago</x-dropdown-link>
                        <x-dropdown-link :href="route('expenses.create', ['case_id' => $case->id])">Registrar gasto</x-dropdown-link>
                        <button type="button" class="block w-full px-4 py-2 text-start text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700" x-data @click="$dispatch('open-modal', 'add-party')">Agregar parte</button>
                    </x-slot>
                </x-dropdown>
                @can('delete', $case)
                    <form method="POST" action="{{ route('cases.destroy', $case) }}" data-confirm="¿Eliminar definitivamente el caso {{ $case->case_number }}? Sólo es posible si no tiene audiencias, pagos, gastos, documentos ni citas.">
                        @csrf @method('DELETE')
                        <button class="btn btn-secondary text-red-600" title="Eliminar caso"><x-icon name="trash" class="h-4 w-4" /></button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <div x-data="{ tab: (window.location.hash || '#resumen').substring(1) }" x-init="$watch('tab', t => history.replaceState(null, '', '#' + t))">
        <div class="card mb-6">
            <div class="flex gap-6 overflow-x-auto px-5">
                @foreach ([
                    'resumen' => ['folder', 'Resumen', null],
                    'partes' => ['users', 'Partes', $case->parties->count()],
                    'notas' => ['clipboard', 'Notas', $case->notes->count()],
                    'documentos' => ['document', 'Documentos', $case->documents->count()],
                    'audiencias' => ['scale', 'Audiencias', $case->hearings->count()],
                    'finanzas' => ['banknotes', 'Pagos y gastos', $case->payments->count() + $case->expenses->count()],
                    'citas' => ['clock', 'Citas', $case->appointments->count()],
                ] as $key => [$icon, $label, $count])
                    <button type="button" @click="tab = '{{ $key }}'" class="tab" :class="tab === '{{ $key }}' && 'tab-active'">
                        <x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}
                        @if (! is_null($count))<span class="rounded-full bg-gray-100 px-1.5 text-xs dark:bg-gray-700">{{ $count }}</span>@endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ============================ RESUMEN ============================ --}}
        <div x-show="tab === 'resumen'" class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <x-card title="Datos del expediente" icon="folder">
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                        @foreach ([
                            'Juzgado / tribunal' => $case->court?->full_name,
                            'Juez' => $case->judge,
                            'Abogado responsable' => $case->lawyer?->name,
                            'Abogado asistente' => $case->assistant?->name,
                            'Fecha de radicación' => fdate($case->filing_date),
                            'Fecha de cierre' => $case->closed_at ? fdate($case->closed_at) : null,
                            'Tipo de caso' => $case->type?->name,
                            'Creado' => $case->created_at->format('d/m/Y'),
                        ] as $label => $value)
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</dt>
                                <dd class="mt-0.5 font-medium text-gray-900 dark:text-gray-100">{{ filled($value) ? $value : '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    @if ($case->description)
                        <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-700">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Descripción / estrategia</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $case->description }}</p>
                        </div>
                    @endif
                </x-card>

                @php $pinned = $case->notes->where('is_pinned', true); @endphp
                @if ($pinned->isNotEmpty())
                    <x-card title="Notas fijadas" icon="bookmark">
                        <div class="space-y-3">
                            @foreach ($pinned as $note)
                                <div class="rounded-lg bg-amber-50 p-3 text-sm dark:bg-amber-500/10">
                                    <p class="whitespace-pre-line text-gray-800 dark:text-gray-200">{{ $note->body }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $note->user?->name }} · {{ $note->created_at->format('d/m/Y') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif
            </div>

            <div class="space-y-6">
                <x-card title="Cliente" icon="user">
                    <div class="flex items-center gap-3">
                        <x-avatar :name="$case->client->name" />
                        <div class="min-w-0">
                            <a href="{{ route('clients.show', $case->client) }}" class="block truncate font-semibold link">{{ $case->client->name }}</a>
                            <p class="text-xs text-gray-500">{{ $case->client->document_label }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($case->client->phone)
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $case->client->phone) }}" class="btn btn-secondary btn-sm"><x-icon name="phone" class="h-4 w-4" /> Llamar</a>
                            <a href="{{ $case->client->whatsapp_url }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm text-emerald-600"><x-icon name="whatsapp" class="h-4 w-4" /> WhatsApp</a>
                        @endif
                        @if ($case->client->email)
                            <a href="mailto:{{ $case->client->email }}?subject={{ rawurlencode('Caso '.$case->case_number.' — '.$case->title) }}" class="btn btn-secondary btn-sm"><x-icon name="envelope" class="h-4 w-4" /> Correo</a>
                        @endif
                    </div>
                </x-card>

                <x-card title="Honorarios" icon="banknotes">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Pactados</span><span class="font-semibold">{{ money($finance['fee']) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Pagado</span><span class="font-semibold text-emerald-600">{{ money($finance['paid']) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Saldo</span><span class="font-semibold text-amber-600">{{ money($finance['balance']) }}</span></div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $finance['progress'] }}%"></div></div>
                        <p class="text-xs text-gray-500">{{ $finance['progress'] }}% cobrado @if($case->fee_notes)· {{ $case->fee_notes }}@endif</p>
                        <div class="flex justify-between border-t border-gray-100 pt-2 dark:border-gray-700"><span class="text-gray-500">Gastos aprobados</span><span class="font-semibold">{{ money($finance['expenses']) }}</span></div>
                        @if ($finance['expenses_pending'] > 0)
                            <div class="flex justify-between"><span class="text-gray-500">Gastos pendientes</span><span class="font-semibold text-amber-600">{{ money($finance['expenses_pending']) }}</span></div>
                        @endif
                    </div>
                    <a href="{{ route('payments.create', ['case_id' => $case->id]) }}" class="btn btn-primary mt-4 w-full"><x-icon name="banknotes" class="h-4 w-4" /> Registrar pago</a>
                </x-card>

                @php $nextHearing = $case->hearings->where('scheduled_at', '>=', now())->where('status.value', 'programada')->sortBy('scheduled_at')->first(); @endphp
                <x-card title="Próxima audiencia" icon="scale">
                    @if ($nextHearing)
                        <p class="font-semibold">{{ $nextHearing->title }}</p>
                        <p class="text-sm text-gray-500">{{ ucfirst($nextHearing->scheduled_at->translatedFormat('l d \d\e F, h:i a')) }}</p>
                        <p class="text-sm text-gray-500">{{ $nextHearing->location }}</p>
                        <a href="{{ route('hearings.edit', $nextHearing) }}" class="mt-3 inline-block text-sm link">Ver detalle</a>
                    @else
                        <p class="text-sm text-gray-500">No hay audiencias programadas.</p>
                        <a href="{{ route('hearings.create', ['case_id' => $case->id, 'redirect' => 'case']) }}" class="btn btn-secondary btn-sm mt-3">Programar audiencia</a>
                    @endif
                </x-card>
            </div>
        </div>

        {{-- ============================ PARTES ============================ --}}
        <div x-show="tab === 'partes'" x-cloak x-data="{ editing: null }">
            <x-card title="Partes del proceso" icon="users" :padding="false">
                <x-slot name="actions">
                    @can('update', $case)
                        <button type="button" class="btn btn-primary btn-sm" @click="$dispatch('open-modal', 'add-party')"><x-icon name="plus" class="h-4 w-4" /> Agregar parte</button>
                    @endcan
                </x-slot>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Nombre</th><th>Calidad</th><th>Documento</th><th>Contacto</th><th>Apoderado</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($case->parties as $party)
                            <tr>
                                <td class="font-medium">{{ $party->name }}@if($party->notes)<p class="text-xs font-normal text-gray-500">{{ $party->notes }}</p>@endif</td>
                                <td><x-badge :color="in_array($party->role, ['demandante', 'denunciante', 'victima']) ? 'blue' : (in_array($party->role, ['demandado', 'denunciado']) ? 'rose' : 'gray')">{{ $party->role_label }}</x-badge></td>
                                <td>{{ $party->document_number ?: '—' }}</td>
                                <td class="text-xs">{{ $party->phone }}@if($party->email)<br>{{ $party->email }}@endif</td>
                                <td class="text-xs">{{ $party->lawyer_name ?: '—' }}</td>
                                <td class="whitespace-nowrap text-right">
                                    @can('update', $case)
                                        <button type="button" class="btn btn-ghost btn-sm" @click="editing = @js($party->only(['id', 'name', 'role', 'document_number', 'phone', 'email', 'address', 'lawyer_name', 'notes'])); $dispatch('open-modal', 'edit-party')"><x-icon name="pencil" class="h-4 w-4" /></button>
                                        <x-delete-button :action="route('parties.destroy', $party)" confirm="¿Quitar a {{ $party->name }} del caso?" />
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty icon="users" title="Sin partes registradas" message="Agregue demandantes, demandados, testigos, peritos y apoderados de la contraparte." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-dialog name="edit-party" title="Editar parte" max-width="2xl">
                <template x-if="editing">
                    <form method="POST" :action="'{{ url('parties') }}/' + editing.id" class="space-y-4">
                        @csrf @method('PUT')
                        @include('cases.partials.party-fields', ['model' => 'editing'])
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn btn-secondary" @click="$dispatch('close')">Cancelar</button>
                            <button class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </template>
            </x-dialog>
        </div>

        {{-- ============================ NOTAS ============================ --}}
        <div x-show="tab === 'notas'" x-cloak class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <x-card title="Nueva nota" icon="pencil">
                <form method="POST" action="{{ route('cases.notes.store', $case) }}" class="space-y-3">
                    @csrf
                    <textarea name="body" rows="6" required class="form-control" placeholder="Actuaciones, acuerdos, pendientes, términos…">{{ old('body') }}</textarea>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_pinned" value="1" class="form-check"> Fijar en el resumen</label>
                    <button class="btn btn-primary w-full">Agregar nota</button>
                </form>
            </x-card>
            <div class="space-y-3 xl:col-span-2">
                @forelse ($case->notes as $note)
                    <div class="card p-4" x-data="{ edit: false }">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <x-avatar :name="$note->user?->name ?? '?'" size="h-8 w-8 text-xs" />
                                <div>
                                    <p class="text-sm font-semibold">{{ $note->user?->name ?? 'Usuario eliminado' }}</p>
                                    <p class="text-xs text-gray-500">{{ $note->created_at->format('d/m/Y h:i a') }} @if($note->updated_at->gt($note->created_at->addMinute()))· editada @endif</p>
                                </div>
                                @if ($note->is_pinned)<x-badge color="amber"><x-icon name="bookmark" class="h-3 w-3" /> Fijada</x-badge>@endif
                            </div>
                            <div class="flex shrink-0">
                                <form method="POST" action="{{ route('notes.pin', $note) }}">@csrf @method('PATCH')
                                    <button class="btn btn-ghost btn-sm" title="{{ $note->is_pinned ? 'Desfijar' : 'Fijar' }}"><x-icon name="bookmark" class="h-4 w-4" /></button>
                                </form>
                                @can('update', $note)
                                    <button type="button" class="btn btn-ghost btn-sm" @click="edit = !edit" title="Editar"><x-icon name="pencil" class="h-4 w-4" /></button>
                                @endcan
                                @can('delete', $note)
                                    <x-delete-button :action="route('notes.destroy', $note)" confirm="¿Eliminar esta nota?" />
                                @endcan
                            </div>
                        </div>
                        <p x-show="!edit" class="mt-3 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $note->body }}</p>
                        @can('update', $note)
                            <form x-show="edit" x-cloak method="POST" action="{{ route('notes.update', $note) }}" class="mt-3 space-y-2">
                                @csrf @method('PUT')
                                <textarea name="body" rows="4" class="form-control">{{ $note->body }}</textarea>
                                <div class="flex justify-end gap-2"><button type="button" class="btn btn-secondary btn-sm" @click="edit = false">Cancelar</button><button class="btn btn-primary btn-sm">Guardar</button></div>
                            </form>
                        @endcan
                    </div>
                @empty
                    <div class="card"><x-empty icon="clipboard" title="Sin notas" message="Registre actuaciones, acuerdos y pendientes del caso." /></div>
                @endforelse
            </div>
        </div>

        {{-- ============================ DOCUMENTOS ============================ --}}
        <div x-show="tab === 'documentos'" x-cloak class="space-y-6">
            @can('update', $case)
                <x-card title="Cargar documentos" icon="upload">
                    <form method="POST" action="{{ route('cases.documents.store', $case) }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end"
                          x-data="{ files: 0 }">
                        @csrf
                        <x-field label="Archivos (PDF, imagen, video, audio…)" name="documents" class="md:col-span-5">
                            <input type="file" name="documents[]" multiple required @change="files = $event.target.files.length"
                                   accept=".{{ str_replace(',', ',.', config('bufete.document_mimes')) }}"
                                   class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-700 dark:text-gray-300 dark:file:bg-primary-500/10 dark:file:text-primary-300">
                        </x-field>
                        <x-field label="Título" name="title" class="md:col-span-3" hint="Si sube varios archivos se usa el nombre de cada uno.">
                            <input name="title" class="form-control" :disabled="files > 1">
                        </x-field>
                        <x-field label="Categoría" name="category" class="md:col-span-2">
                            <select name="category" class="form-control">
                                <option value="">Automática</option>
                                @foreach (config('bufete.document_categories') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                            </select>
                        </x-field>
                        <button class="btn btn-primary md:col-span-2 md:mb-5"><x-icon name="upload" class="h-4 w-4" /> Cargar</button>
                    </form>
                    @error('documents.*')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </x-card>
            @endcan
            <x-card title="Documentos del caso" icon="folder">
                @include('cases.partials.documents-grid', ['documents' => $case->documents])
            </x-card>
        </div>

        {{-- ============================ AUDIENCIAS ============================ --}}
        <div x-show="tab === 'audiencias'" x-cloak>
            <x-card title="Audiencias" icon="scale" :padding="false">
                <x-slot name="actions"><a href="{{ route('hearings.create', ['case_id' => $case->id, 'redirect' => 'case']) }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Programar</a></x-slot>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Fecha y hora</th><th>Audiencia</th><th>Lugar</th><th>Asignada a</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($case->hearings as $h)
                            <tr>
                                <td class="whitespace-nowrap">{{ fdatetime($h->scheduled_at) }}</td>
                                <td><p class="font-medium">{{ $h->title }}</p>@if($h->outcome)<p class="max-w-md text-xs text-gray-500">Resultado: {{ \Illuminate\Support\Str::limit($h->outcome, 120) }}</p>@endif</td>
                                <td class="text-xs">{{ $h->location ?: ($h->court?->name ?? '—') }}</td>
                                <td class="whitespace-nowrap">{{ $h->user?->name ?? '—' }}</td>
                                <td><x-badge :color="$h->status->color()">{{ $h->status->label() }}</x-badge></td>
                                <td class="text-right"><a href="{{ route('hearings.edit', $h) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4" /></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty icon="scale" title="Sin audiencias" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        {{-- ============================ FINANZAS ============================ --}}
        <div x-show="tab === 'finanzas'" x-cloak class="space-y-6">
            <x-card title="Pagos recibidos" icon="banknotes" :padding="false">
                <x-slot name="actions"><a href="{{ route('payments.create', ['case_id' => $case->id]) }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Registrar pago</a></x-slot>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Recibo</th><th>Fecha</th><th>Concepto</th><th>Medio</th><th>Recibió</th><th class="text-right">Valor</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($case->payments as $p)
                            <tr>
                                <td class="whitespace-nowrap font-medium">{{ $p->receipt_number }}</td>
                                <td class="whitespace-nowrap">{{ fdate($p->paid_at) }}</td>
                                <td>{{ $p->concept }}</td>
                                <td><x-badge :color="$p->method->color()">{{ $p->method->label() }}</x-badge></td>
                                <td class="whitespace-nowrap">{{ $p->user?->name }}</td>
                                <td class="whitespace-nowrap text-right font-semibold">{{ money($p->amount) }}</td>
                                <td class="text-right"><a href="{{ route('payments.show', $p) }}" class="btn btn-ghost btn-sm" title="Recibo"><x-icon name="printer" class="h-4 w-4" /></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-empty icon="banknotes" title="Sin pagos" /></td></tr>
                        @endforelse
                        </tbody>
                        @if ($case->payments->isNotEmpty())
                            <tfoot><tr class="bg-gray-50 dark:bg-gray-800/60"><td colspan="5" class="px-4 py-2 text-right text-sm font-semibold">Total pagado</td><td class="px-4 py-2 text-right font-bold">{{ money($finance['paid']) }}</td><td></td></tr></tfoot>
                        @endif
                    </table>
                </div>
            </x-card>

            <x-card title="Gastos del caso" icon="receipt" :padding="false">
                <x-slot name="actions"><a href="{{ route('expenses.create', ['case_id' => $case->id]) }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Registrar gasto</a></x-slot>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Fecha</th><th>Descripción</th><th>Categoría</th><th>Registró</th><th>Estado</th><th class="text-right">Valor</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($case->expenses as $e)
                            <tr>
                                <td class="whitespace-nowrap">{{ fdate($e->expense_date) }}</td>
                                <td>{{ $e->description }} @if(! $e->billable)<x-badge color="gray">No facturable</x-badge>@endif</td>
                                <td class="text-xs">{{ $e->category_label }}</td>
                                <td class="whitespace-nowrap">{{ $e->user?->name }}</td>
                                <td><x-badge :color="$e->status->color()">{{ $e->status->label() }}</x-badge></td>
                                <td class="whitespace-nowrap text-right font-semibold">{{ money($e->amount) }}</td>
                                <td class="text-right"><a href="{{ route('expenses.show', $e) }}" class="btn btn-ghost btn-sm"><x-icon name="eye" class="h-4 w-4" /></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-empty icon="receipt" title="Sin gastos" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        {{-- ============================ CITAS ============================ --}}
        <div x-show="tab === 'citas'" x-cloak>
            <x-card title="Citas relacionadas" icon="clock" :padding="false">
                <x-slot name="actions"><a href="{{ route('appointments.create', ['case_id' => $case->id, 'client_id' => $case->client_id]) }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Agendar</a></x-slot>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Fecha</th><th>Asunto</th><th>Abogado</th><th>Modalidad</th><th>Estado</th></tr></thead>
                        <tbody>
                        @forelse ($case->appointments as $a)
                            <tr>
                                <td class="whitespace-nowrap">{{ fdatetime($a->starts_at) }}</td>
                                <td><a href="{{ route('appointments.edit', $a) }}" class="link">{{ $a->title }}</a></td>
                                <td>{{ $a->user?->name }}</td>
                                <td>{{ $a->mode->label() }}</td>
                                <td><x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty icon="clock" title="Sin citas" /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>

    {{-- Modal: agregar parte --}}
    @can('update', $case)
        <x-dialog name="add-party" title="Agregar parte al proceso" max-width="2xl" :show="$errors->has('role')">
            <form method="POST" action="{{ route('cases.parties.store', $case) }}" class="space-y-4">
                @csrf
                @include('cases.partials.party-fields', ['model' => null])
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="$dispatch('close')">Cancelar</button>
                    <button class="btn btn-primary">Agregar</button>
                </div>
            </form>
        </x-dialog>
    @endcan
</x-app-layout>

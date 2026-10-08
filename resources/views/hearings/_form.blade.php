<input type="hidden" name="redirect" value="{{ request('redirect', old('redirect')) }}">
<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <x-card title="Datos de la audiencia" icon="scale" class="xl:col-span-2">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-6">
            <x-field label="Caso" name="legal_case_id" required class="md:col-span-6">
                <select name="legal_case_id" required class="form-control">
                    <option value="">Seleccione el caso…</option>
                    @foreach ($cases as $c)
                        <option value="{{ $c->id }}" @selected(old('legal_case_id', $hearing->legal_case_id) == $c->id)>{{ $c->case_number }} — {{ \Illuminate\Support\Str::limit($c->title, 60) }} ({{ $c->client?->name }})</option>
                    @endforeach
                </select>
            </x-field>
            <x-field label="Tipo de audiencia" name="hearing_type" class="md:col-span-3">
                <select name="hearing_type" class="form-control" x-data @change="if (!$el.form.title.value) $el.form.title.value = $el.value">
                    <option value="">—</option>
                    @foreach (config('bufete.hearing_types') as $t)<option @selected(old('hearing_type', $hearing->hearing_type) === $t)>{{ $t }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Título" name="title" required class="md:col-span-3">
                <input name="title" value="{{ old('title', $hearing->title) }}" required class="form-control">
            </x-field>
            <x-field label="Fecha y hora" name="scheduled_at" required class="md:col-span-3">
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $hearing->scheduled_at?->format('Y-m-d\TH:i')) }}" required class="form-control">
            </x-field>
            <x-field label="Duración (minutos)" name="duration_minutes" required class="md:col-span-3">
                <input type="number" min="5" step="5" name="duration_minutes" value="{{ old('duration_minutes', $hearing->duration_minutes ?? 60) }}" required class="form-control">
            </x-field>
            <x-field label="Juzgado" name="court_id" class="md:col-span-3">
                <select name="court_id" class="form-control">
                    <option value="">—</option>
                    @foreach ($courts as $ct)<option value="{{ $ct->id }}" @selected(old('court_id', $hearing->court_id) == $ct->id)>{{ $ct->full_name }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Juez" name="judge" class="md:col-span-3">
                <input name="judge" value="{{ old('judge', $hearing->judge) }}" class="form-control">
            </x-field>
            <x-field label="Sala / lugar / enlace virtual" name="location" class="md:col-span-6">
                <input name="location" value="{{ old('location', $hearing->location) }}" class="form-control" placeholder="Ej. Sala 4 o enlace de Teams">
            </x-field>
            <x-field label="Preparación / notas" name="notes" class="md:col-span-6">
                <textarea name="notes" rows="3" class="form-control">{{ old('notes', $hearing->notes) }}</textarea>
            </x-field>
        </div>
    </x-card>

    <div class="space-y-6">
        <x-card title="Asignación y estado" icon="user">
            <div class="space-y-4">
                <x-field label="Abogado que asiste" name="user_id">
                    <select name="user_id" class="form-control">
                        <option value="">—</option>
                        @foreach ($lawyers as $l)<option value="{{ $l->id }}" @selected(old('user_id', $hearing->user_id) == $l->id)>{{ $l->name }}</option>@endforeach
                    </select>
                </x-field>
                <x-field label="Estado" name="status" required>
                    <select name="status" class="form-control">
                        @foreach (\App\Enums\HearingStatus::options() as $v => $l)<option value="{{ $v }}" @selected(old('status', $hearing->status?->value) === $v)>{{ $l }}</option>@endforeach
                    </select>
                </x-field>
                <x-field label="Resultado / decisión" name="outcome" hint="Complete después de la diligencia.">
                    <textarea name="outcome" rows="4" class="form-control">{{ old('outcome', $hearing->outcome) }}</textarea>
                </x-field>
            </div>
        </x-card>
        <div class="rounded-lg bg-primary-50 p-4 text-sm text-primary-800 dark:bg-primary-500/10 dark:text-primary-200">
            <x-icon name="bell" class="mb-1 h-5 w-5" />
            Los abogados del caso reciben una notificación al programar o reprogramar la audiencia, y un recordatorio 24 horas antes.
        </div>
    </div>
</div>

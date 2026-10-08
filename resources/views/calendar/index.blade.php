<x-app-layout title="Calendario judicial">
    <x-slot name="header">
        <x-page-header title="Calendario judicial" subtitle="Audiencias y citas. Haga clic en un día para programar.">
            <a href="{{ route('hearings.create', ['redirect' => 'calendar']) }}" class="btn btn-secondary"><x-icon name="scale" class="h-4 w-4" /> Audiencia</a>
            <a href="{{ route('appointments.create', ['redirect' => 'calendar']) }}" class="btn btn-primary"><x-icon name="clock" class="h-4 w-4" /> Cita</a>
        </x-page-header>
    </x-slot>

    <div x-data="{
            show: 'all',
            lawyer: '',
            picked: null,
            calendar: null,
            init() {
                // calendar.js se carga como módulo al final de la página: se espera a que esté listo.
                if (!window.initLegalCalendar) { setTimeout(() => this.init(), 50); return; }
                this.calendar = window.initLegalCalendar(this.$refs.cal, {
                    eventsUrl: '{{ route('calendar.events') }}',
                    filters: () => ({ show: this.show, lawyer: this.lawyer }),
                    onDateClick: (date, allDay) => { this.picked = allDay ? date + 'T09:00' : date.substring(0, 16); this.$dispatch('open-modal', 'pick-action'); },
                });
                this.$watch('show', () => this.calendar.refetchEvents());
                this.$watch('lawyer', () => this.calendar.refetchEvents());
            },
        }" class="card p-4">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <div class="inline-flex rounded-lg bg-gray-100 p-1 text-sm dark:bg-gray-700/60">
                @foreach (['all' => 'Todo', 'hearings' => 'Audiencias', 'appointments' => 'Citas'] as $k => $l)
                    <button type="button" @click="show = '{{ $k }}'" class="rounded-md px-3 py-1 font-medium" :class="show === '{{ $k }}' ? 'bg-white shadow dark:bg-gray-800' : 'text-gray-500'">{{ $l }}</button>
                @endforeach
            </div>
            @can('admin')
                <select x-model="lawyer" class="form-control w-auto py-1.5">
                    <option value="">Todos los abogados</option>
                    @foreach ($lawyers as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                </select>
            @endcan
            <div class="ml-auto flex flex-wrap gap-3 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-sm bg-rose-600"></span> Audiencia programada</span>
                <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-sm bg-green-600"></span> Realizada</span>
                <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-sm bg-amber-600"></span> Aplazada</span>
                <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-sm bg-indigo-600"></span> Cita</span>
                <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-sm bg-gray-400"></span> Cancelada</span>
            </div>
        </div>
        <div x-ref="cal"></div>

        <x-dialog name="pick-action" title="¿Qué desea programar?" max-width="md">
            <p class="mb-4 text-sm text-gray-500">Fecha seleccionada: <strong x-text="picked ? new Date(picked).toLocaleString('es', { dateStyle: 'full', timeStyle: 'short' }) : ''"></strong></p>
            <div class="grid grid-cols-2 gap-3">
                <a :href="'{{ route('hearings.create') }}?redirect=calendar&date=' + encodeURIComponent(picked)" class="flex flex-col items-center gap-2 rounded-lg border border-gray-200 p-4 hover:border-rose-400 hover:bg-rose-50 dark:border-gray-700 dark:hover:bg-rose-500/10">
                    <x-icon name="scale" class="h-8 w-8 text-rose-500" /><span class="font-semibold">Audiencia</span>
                </a>
                <a :href="'{{ route('appointments.create') }}?redirect=calendar&date=' + encodeURIComponent(picked)" class="flex flex-col items-center gap-2 rounded-lg border border-gray-200 p-4 hover:border-indigo-400 hover:bg-indigo-50 dark:border-gray-700 dark:hover:bg-indigo-500/10">
                    <x-icon name="clock" class="h-8 w-8 text-indigo-500" /><span class="font-semibold">Cita</span>
                </a>
            </div>
        </x-dialog>
    </div>

    @push('scripts')
        @vite('resources/js/calendar.js')
    @endpush
</x-app-layout>

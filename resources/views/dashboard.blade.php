<x-app-layout title="Panel de control">
    @php
        $me = auth()->user();
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
    @endphp

    <x-slot name="header">
        <x-page-header :title="$greeting.', '.\Illuminate\Support\Str::before($me->name, ' ')" :subtitle="ucfirst(now()->translatedFormat('l, d \d\e F \d\e Y')).' · '.($me->isSuperadmin() ? 'Vista de todo el bufete' : 'Sus asuntos asignados')">
            <a href="{{ route('clients.create') }}" class="btn btn-secondary"><x-icon name="user-plus" class="h-4 w-4" /> Cliente</a>
            <a href="{{ route('cases.create') }}" class="btn btn-secondary"><x-icon name="briefcase" class="h-4 w-4" /> Caso</a>
            <a href="{{ route('appointments.create') }}" class="btn btn-secondary"><x-icon name="clock" class="h-4 w-4" /> Cita</a>
            <a href="{{ route('payments.create') }}" class="btn btn-primary"><x-icon name="banknotes" class="h-4 w-4" /> Registrar pago</a>
        </x-page-header>
    </x-slot>

    {{-- Indicadores --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Clientes" :value="number_format($stats['clients'])" icon="users" color="sky" :href="route('clients.index')" />
        <x-stat label="Casos activos" :value="number_format($stats['open_cases'])" icon="briefcase" color="primary" :href="route('cases.index')" :hint="$stats['closed_cases'].' cerrados'" />
        <x-stat label="Audiencias (7 días)" :value="$stats['hearings_week']" icon="scale" color="rose" :href="route('hearings.index')" />
        <x-stat label="Citas hoy" :value="$stats['appointments_today']" icon="clock" color="violet" :href="route('appointments.index', ['range' => 'today'])" />
        <x-stat label="Ingresos del mes" :value="money($stats['income_month'])" icon="banknotes" color="emerald" :href="route('payments.index')" :hint="'Año: '.money($stats['income_year'])" />
        <x-stat label="Por cobrar" :value="money($stats['receivable'])" icon="trending-up" color="amber" hint="Saldo de honorarios en casos activos" />
        <x-stat label="Gastos del mes" :value="money($stats['expenses_month'])" icon="receipt" color="gray" :href="route('expenses.index')" hint="Aprobados y reembolsados" />
        <x-stat label="Gastos pendientes" :value="$stats['pending_expenses']" icon="warning" color="amber" :href="route('expenses.index', ['status' => 'pendiente'])" hint="Esperando aprobación" />
    </div>

    {{-- Gráficos --}}
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-card title="Ingresos vs. gastos (12 meses)" icon="chart" class="xl:col-span-2">
            <div class="h-72"><canvas id="financeChart"></canvas></div>
        </x-card>
        <x-card title="Casos por estado" icon="briefcase">
            @if (count($charts['status']))
                <div class="h-72"><canvas id="statusChart"></canvas></div>
            @else
                <x-empty icon="briefcase" title="Aún no hay casos" />
            @endif
        </x-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Próximas audiencias --}}
        <x-card title="Próximas audiencias" icon="scale" :padding="false" class="xl:col-span-2">
            <x-slot name="actions"><a href="{{ route('calendar.index') }}" class="text-sm link">Ver calendario</a></x-slot>
            @forelse ($upcomingHearings as $h)
                <a href="{{ route('hearings.edit', $h) }}" class="flex items-center gap-4 border-b border-gray-100 px-5 py-3 last:border-0 hover:bg-gray-50 dark:border-gray-700/60 dark:hover:bg-gray-700/30">
                    <div class="flex w-14 shrink-0 flex-col items-center rounded-lg bg-rose-50 py-1.5 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
                        <span class="text-[10px] font-semibold uppercase">{{ $h->scheduled_at->translatedFormat('M') }}</span>
                        <span class="text-lg font-bold leading-none">{{ $h->scheduled_at->format('d') }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $h->title }}</p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $h->legalCase?->case_number }} · {{ $h->legalCase?->client?->name }}</p>
                    </div>
                    <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                        <p class="font-semibold text-gray-700 dark:text-gray-200">{{ $h->scheduled_at->format('h:i a') }}</p>
                        <p>{{ $h->scheduled_at->diffForHumans() }}</p>
                    </div>
                </a>
            @empty
                <x-empty icon="scale" title="Sin audiencias próximas" message="Programe audiencias desde el calendario o desde el expediente del caso." />
            @endforelse
        </x-card>

        {{-- Citas de hoy --}}
        <x-card title="Citas de hoy" icon="clock" :padding="false">
            <x-slot name="actions"><a href="{{ route('appointments.create') }}" class="btn btn-ghost btn-sm"><x-icon name="plus" class="h-4 w-4" /></a></x-slot>
            @forelse ($todayAppointments as $a)
                <a href="{{ route('appointments.edit', $a) }}" class="flex items-center gap-3 border-b border-gray-100 px-5 py-3 last:border-0 hover:bg-gray-50 dark:border-gray-700/60 dark:hover:bg-gray-700/30">
                    <span class="w-16 shrink-0 text-sm font-semibold text-primary-600 dark:text-primary-400">{{ $a->starts_at->format('h:i a') }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ $a->title }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $a->who }} · {{ $a->user?->name }}</p>
                    </div>
                    <x-badge :color="$a->status->color()">{{ $a->status->label() }}</x-badge>
                </a>
            @empty
                <x-empty icon="clock" title="No hay citas para hoy" />
            @endforelse
        </x-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Casos recientes --}}
        <x-card title="Casos recientes" icon="briefcase" :padding="false" class="xl:col-span-2">
            <x-slot name="actions"><a href="{{ route('cases.index') }}" class="text-sm link">Ver todos</a></x-slot>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Caso</th><th>Cliente</th><th class="hidden 2xl:table-cell">Responsable</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($recentCases as $c)
                        <tr>
                            <td>
                                <a href="{{ route('cases.show', $c) }}" class="link">{{ $c->case_number }}</a>
                                <p class="max-w-[16rem] truncate text-xs text-gray-500">{{ $c->title }}</p>
                            </td>
                            <td>{{ $c->client?->name }}</td>
                            <td class="hidden whitespace-nowrap 2xl:table-cell">{{ $c->lawyer?->name ?? '—' }}</td>
                            <td><x-badge :color="$c->status?->color">{{ $c->status?->name ?? '—' }}</x-badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty icon="briefcase" title="Aún no hay casos" /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="space-y-6">
            {{-- Aprobaciones pendientes --}}
            @if ($approvals->isNotEmpty())
                <x-card title="Gastos por aprobar" icon="receipt" :padding="false">
                    <x-slot name="actions"><a href="{{ route('expenses.index', ['view' => 'approvals']) }}" class="text-sm link">Ver</a></x-slot>
                    @foreach ($approvals as $e)
                        <a href="{{ route('expenses.show', $e) }}" class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 last:border-0 hover:bg-gray-50 dark:border-gray-700/60 dark:hover:bg-gray-700/30">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $e->description }}</p>
                                <p class="truncate text-xs text-gray-500">{{ $e->user?->name }} · {{ $e->legalCase?->case_number }}</p>
                            </div>
                            <span class="whitespace-nowrap text-sm font-semibold">{{ money($e->amount) }}</span>
                        </a>
                    @endforeach
                </x-card>
            @endif

            <x-card title="Casos por tipo" icon="chart">
                @if (count($charts['types']))
                    <div class="h-56"><canvas id="typeChart"></canvas></div>
                @else
                    <x-empty icon="chart" title="Sin datos" />
                @endif
            </x-card>

            <a href="{{ route('ai.index') }}" class="card flex items-center gap-4 bg-gradient-to-br from-primary-600 to-primary-800 p-5 text-white ring-0 transition hover:from-primary-700">
                <x-icon name="sparkles" class="h-10 w-10 shrink-0" />
                <div>
                    <p class="font-semibold">Asistente legal con IA</p>
                    <p class="text-sm text-primary-100">Pregunte en lenguaje natural: «¿Qué audiencias tengo esta semana?»</p>
                </div>
            </a>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/charts.js')
        <script type="module">
            const charts = @json($charts);
            const money = (v) => '{{ setting('currency_symbol', '$') }} ' + Number(v).toLocaleString('{{ setting('number_format', 'es') === 'en' ? 'en-US' : 'es-CO' }}');

            new Chart(document.getElementById('financeChart'), {
                type: 'bar',
                data: {
                    labels: charts.months,
                    datasets: [
                        { label: 'Ingresos', data: charts.income, backgroundColor: primaryColor(500, 0.85), borderRadius: 6, maxBarThickness: 28 },
                        { label: 'Gastos', data: charts.expenses, type: 'line', borderColor: '#f43f5e', backgroundColor: 'rgba(244,63,94,0.15)', tension: 0.35, fill: true, pointRadius: 3 },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + money(ctx.parsed.y) } } },
                    scales: { y: { beginAtZero: true, ticks: { callback: (v) => money(v) } }, x: { grid: { display: false } } },
                },
            });

            if (document.getElementById('statusChart')) {
                new Chart(document.getElementById('statusChart'), {
                    type: 'doughnut',
                    data: {
                        labels: charts.status.map(s => s.label),
                        datasets: [{ data: charts.status.map(s => s.value), backgroundColor: charts.status.map(s => s.color), borderWidth: 0 }],
                    },
                    options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } },
                });
            }

            if (document.getElementById('typeChart')) {
                new Chart(document.getElementById('typeChart'), {
                    type: 'bar',
                    data: {
                        labels: charts.types.map(t => t.label),
                        datasets: [{ label: 'Casos', data: charts.types.map(t => t.value), backgroundColor: primaryColor(400, 0.8), borderRadius: 4 }],
                    },
                    options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } } },
                });
            }
        </script>
    @endpush
</x-app-layout>

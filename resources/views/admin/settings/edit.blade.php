<x-app-layout title="Configuración">
    <x-slot name="header">
        <x-page-header title="Configuración" subtitle="Datos del bufete, apariencia, inteligencia artificial y copias de seguridad" />
    </x-slot>

    <div x-data="{ tab: (window.location.hash || '#general').substring(1) }" x-init="$watch('tab', t => history.replaceState(null, '', '#' + t))" class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <nav class="card h-fit p-2">
            @foreach ([
                'general' => ['building', 'Datos del bufete'],
                'apariencia' => ['swatch', 'Tema y logotipo'],
                'ia' => ['sparkles', 'Inteligencia artificial'],
                'respaldos' => ['cloud-download', 'Copias de seguridad'],
                'sistema' => ['info', 'Sistema'],
            ] as $key => [$icon, $label])
                <button type="button" @click="tab = '{{ $key }}'" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium"
                        :class="tab === '{{ $key }}' ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/40'">
                    <x-icon :name="$icon" class="h-5 w-5" /> {{ $label }}
                </button>
            @endforeach
        </nav>

        <div class="lg:col-span-3">
            {{-- General --}}
            <form x-show="tab === 'general'" method="POST" action="{{ route('admin.settings.general') }}">
                @csrf @method('PUT')
                <x-card title="Datos del bufete" icon="building">
                    <p class="mb-4 text-sm text-gray-500">Aparecen en el menú, en los recibos y en los informes imprimibles.</p>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <x-field label="Nombre del bufete" name="firm_name" required><input name="firm_name" value="{{ old('firm_name', setting('firm_name')) }}" required class="form-control"></x-field>
                        <x-field label="Eslogan" name="firm_slogan"><input name="firm_slogan" value="{{ old('firm_slogan', setting('firm_slogan')) }}" class="form-control"></x-field>
                        <x-field label="NIT / identificación fiscal" name="firm_tax_id"><input name="firm_tax_id" value="{{ old('firm_tax_id', setting('firm_tax_id')) }}" class="form-control"></x-field>
                        <x-field label="Teléfono" name="firm_phone"><input name="firm_phone" value="{{ old('firm_phone', setting('firm_phone')) }}" class="form-control"></x-field>
                        <x-field label="Dirección" name="firm_address"><input name="firm_address" value="{{ old('firm_address', setting('firm_address')) }}" class="form-control"></x-field>
                        <x-field label="Ciudad / país" name="firm_city"><input name="firm_city" value="{{ old('firm_city', setting('firm_city')) }}" class="form-control"></x-field>
                        <x-field label="Correo" name="firm_email"><input type="email" name="firm_email" value="{{ old('firm_email', setting('firm_email')) }}" class="form-control"></x-field>
                        <x-field label="Sitio web" name="firm_website"><input name="firm_website" value="{{ old('firm_website', setting('firm_website')) }}" class="form-control"></x-field>
                    </div>
                    <h4 class="mb-3 mt-8 text-sm font-semibold uppercase tracking-wide text-gray-500">Moneda y recibos</h4>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
                        <x-field label="Símbolo de moneda" name="currency_symbol" required><input name="currency_symbol" value="{{ old('currency_symbol', setting('currency_symbol', '$')) }}" required class="form-control"></x-field>
                        <x-field label="Decimales" name="currency_decimals">
                            <select name="currency_decimals" class="form-control">
                                <option value="0" @selected(setting('currency_decimals', '0') == '0')>Sin decimales</option>
                                <option value="2" @selected(setting('currency_decimals') == '2')>2 decimales</option>
                            </select>
                        </x-field>
                        <x-field label="Formato numérico" name="number_format">
                            <select name="number_format" class="form-control">
                                <option value="es" @selected(setting('number_format', 'es') === 'es')>1.234.567,89</option>
                                <option value="en" @selected(setting('number_format') === 'en')>1,234,567.89</option>
                            </select>
                        </x-field>
                        <x-field label="Prefijo de recibos" name="receipt_prefix" required><input name="receipt_prefix" value="{{ old('receipt_prefix', setting('receipt_prefix', 'REC')) }}" required class="form-control"></x-field>
                        <x-field label="Moneda en letras" name="currency_words" required class="md:col-span-2" hint="Ej. PESOS, DÓLARES, SOLES"><input name="currency_words" value="{{ old('currency_words', setting('currency_words', 'PESOS')) }}" required class="form-control"></x-field>
                        <x-field label="Sufijo" name="currency_suffix" class="md:col-span-2" hint="Ej. M/CTE"><input name="currency_suffix" value="{{ old('currency_suffix', setting('currency_suffix', 'M/CTE')) }}" class="form-control"></x-field>
                        <x-field label="Pie de página del recibo" name="receipt_footer" class="md:col-span-4"><textarea name="receipt_footer" rows="2" class="form-control">{{ old('receipt_footer', setting('receipt_footer')) }}</textarea></x-field>
                    </div>
                    <div class="mt-6 flex justify-end"><button class="btn btn-primary">Guardar</button></div>
                </x-card>
            </form>

            {{-- Apariencia --}}
            <form x-show="tab === 'apariencia'" x-cloak method="POST" action="{{ route('admin.settings.appearance') }}" enctype="multipart/form-data" x-data="{ theme: '{{ setting('theme_color', 'indigo') }}', preview: null }">
                @csrf
                <x-card title="Tema de la interfaz y logotipo" icon="swatch">
                    <h4 class="mb-3 text-sm font-semibold">Color principal</h4>
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                        @foreach ($themes as $key => $t)
                            <label class="cursor-pointer rounded-xl border-2 p-3 text-center text-xs font-medium" :class="theme === '{{ $key }}' ? 'border-gray-900 dark:border-white' : 'border-transparent bg-gray-50 dark:bg-gray-700/40'">
                                <input type="radio" name="theme_color" value="{{ $key }}" x-model="theme" class="sr-only">
                                <span class="mx-auto mb-2 flex h-10 w-10 overflow-hidden rounded-full">
                                    <span class="w-1/2" style="background: {{ $t['shades'][500] }}"></span><span class="w-1/2" style="background: {{ $t['shades'][800] }}"></span>
                                </span>
                                {{ $t['label'] }}
                            </label>
                        @endforeach
                    </div>
                    <p class="form-hint mt-2">El modo claro / oscuro lo elige cada usuario con el botón <x-icon name="moon" class="inline h-3.5 w-3.5" /> de la barra superior.</p>

                    <h4 class="mb-3 mt-8 text-sm font-semibold">Logotipo</h4>
                    <div class="flex flex-wrap items-center gap-6">
                        <div class="flex h-24 w-24 items-center justify-center rounded-xl bg-gray-100 p-2 dark:bg-gray-700">
                            <img :src="preview || '{{ route('branding.logo') }}?v={{ md5((string) setting('logo_path')) }}'" class="max-h-full max-w-full object-contain" alt="Logo">
                        </div>
                        <div class="space-y-2">
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                                   class="block text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-700 dark:text-gray-300">
                            <p class="form-hint">PNG, JPG o WEBP, máximo 2 MB. Se recomienda cuadrado con fondo transparente.</p>
                            @if (setting('logo_path'))<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_logo" value="1" class="form-check"> Quitar logotipo y usar el predeterminado</label>@endif
                            @error('logo')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end"><button class="btn btn-primary">Guardar apariencia</button></div>
                </x-card>
            </form>

            {{-- IA --}}
            <div x-show="tab === 'ia'" x-cloak class="space-y-6">
                <form method="POST" action="{{ route('admin.settings.ai') }}" x-data="{ provider: '{{ setting('ai_provider', 'gemini') }}' }">
                    @csrf @method('PUT')
                    <x-card title="Asistente de inteligencia artificial" icon="sparkles">
                        <x-field label="Proveedor" name="ai_provider">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                @foreach (['gemini' => ['Google Gemini', $hasGeminiKey], 'openai' => ['OpenAI ChatGPT', $hasOpenaiKey], 'none' => ['Sin conexión (local)', true]] as $k => [$l, $ok])
                                    <label class="cursor-pointer rounded-lg border-2 p-3 text-sm" :class="provider === '{{ $k }}' ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10' : 'border-gray-200 dark:border-gray-700'">
                                        <input type="radio" name="ai_provider" value="{{ $k }}" x-model="provider" class="sr-only">
                                        <span class="block font-semibold">{{ $l }}</span>
                                        <span class="text-xs {{ $ok ? 'text-emerald-600' : 'text-amber-600' }}">{{ $k === 'none' ? 'Responde consultas básicas con los datos del sistema' : ($ok ? 'Clave configurada ✓' : 'Falta la clave de API') }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </x-field>

                        <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <p class="font-semibold">Google Gemini</p>
                                <x-field label="Clave de API" name="gemini_api_key" hint="Obtenga una gratis en aistudio.google.com/apikey. Se guarda cifrada.">
                                    <input type="password" name="gemini_api_key" autocomplete="off" class="form-control" placeholder="{{ $hasGeminiKey ? '•••••••••••• (configurada — escriba para reemplazar)' : 'AIza…' }}">
                                </x-field>
                                @if ($hasGeminiKey)<label class="flex items-center gap-2 text-xs"><input type="checkbox" name="clear_gemini_api_key" value="1" class="form-check"> Borrar clave guardada</label>@endif
                                <x-field label="Modelo" name="gemini_model"><input name="gemini_model" value="{{ setting('gemini_model', config('bufete.ai.default_gemini_model')) }}" class="form-control"></x-field>
                            </div>
                            <div class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <p class="font-semibold">OpenAI ChatGPT</p>
                                <x-field label="Clave de API" name="openai_api_key" hint="platform.openai.com/api-keys. Se guarda cifrada.">
                                    <input type="password" name="openai_api_key" autocomplete="off" class="form-control" placeholder="{{ $hasOpenaiKey ? '•••••••••••• (configurada — escriba para reemplazar)' : 'sk-…' }}">
                                </x-field>
                                @if ($hasOpenaiKey)<label class="flex items-center gap-2 text-xs"><input type="checkbox" name="clear_openai_api_key" value="1" class="form-check"> Borrar clave guardada</label>@endif
                                <x-field label="Modelo" name="openai_model"><input name="openai_model" value="{{ setting('openai_model', config('bufete.ai.default_openai_model')) }}" class="form-control"></x-field>
                                <x-field label="URL base (API compatible)" name="openai_base_url"><input name="openai_base_url" value="{{ setting('openai_base_url', config('bufete.ai.default_openai_base_url')) }}" class="form-control"></x-field>
                            </div>
                        </div>

                        <x-field label="Instrucciones del sistema (para todo el bufete)" name="ai_system_prompt" class="mt-6" hint="Cada usuario puede añadir además sus propias instrucciones personalizadas desde el asistente.">
                            <textarea name="ai_system_prompt" rows="6" class="form-control">{{ setting('ai_system_prompt', config('bufete.ai.default_system_prompt')) }}</textarea>
                        </x-field>
                        <label class="mt-4 flex items-start gap-2 text-sm">
                            <input type="checkbox" name="ai_include_context" value="1" class="form-check mt-0.5" @checked(setting('ai_include_context', '1') === '1')>
                            <span>Enviar a la IA un resumen de los datos visibles para el usuario (casos, audiencias, citas, pagos y gastos) para que pueda responder preguntas sobre el bufete.
                                <span class="block text-xs text-gray-500">Desactívelo si no desea compartir datos de clientes con el proveedor de IA.</span></span>
                        </label>
                        <div class="mt-6 flex justify-end"><button class="btn btn-primary">Guardar configuración de IA</button></div>
                    </x-card>
                </form>
                <form method="POST" action="{{ route('admin.settings.ai.test') }}" class="flex justify-end">
                    @csrf
                    <button class="btn btn-secondary"><x-icon name="refresh" class="h-4 w-4" /> Probar conexión con el proveedor</button>
                </form>
            </div>

            {{-- Respaldos --}}
            <div x-show="tab === 'respaldos'" x-cloak>
                <x-card title="Copias de seguridad" icon="cloud-download">
                    <x-slot name="actions">
                        <form method="POST" action="{{ route('admin.backups.store') }}">@csrf<button class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Crear copia ahora</button></form>
                    </x-slot>
                    <p class="mb-4 text-sm text-gray-500">Cada copia es un archivo ZIP con la base de datos SQLite y todos los documentos subidos. Descárguela y guárdela fuera del servidor.</p>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead><tr><th>Archivo</th><th>Fecha</th><th class="text-right">Tamaño</th><th></th></tr></thead>
                            <tbody>
                            @forelse ($backups as $b)
                                <tr>
                                    <td class="font-medium">{{ $b['name'] }}</td>
                                    <td>{{ $b['date']->format('d/m/Y h:i a') }}</td>
                                    <td class="text-right">{{ format_bytes($b['size']) }}</td>
                                    <td class="whitespace-nowrap text-right">
                                        <a href="{{ route('admin.backups.download', $b['name']) }}" class="btn btn-ghost btn-sm"><x-icon name="download" class="h-4 w-4" /></a>
                                        <x-delete-button :action="route('admin.backups.destroy', $b['name'])" confirm="¿Eliminar esta copia de seguridad?" />
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty icon="archive" title="Aún no hay copias de seguridad" /></td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- Sistema --}}
            <div x-show="tab === 'sistema'" x-cloak class="space-y-6">
                <x-card title="Información del sistema" icon="info">
                    @php $db = \Illuminate\Support\Facades\DB::connection()->getDatabaseName(); @endphp
                    <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                        <div><dt class="text-gray-500">Aplicación</dt><dd class="font-medium">{{ config('app.name') }}</dd></div>
                        <div><dt class="text-gray-500">Laravel / PHP</dt><dd class="font-medium">{{ app()->version() }} / {{ PHP_VERSION }}</dd></div>
                        <div><dt class="text-gray-500">Base de datos</dt><dd class="font-medium">SQLite · {{ is_file($db) ? format_bytes(filesize($db)) : '—' }}</dd></div>
                        <div><dt class="text-gray-500">Zona horaria</dt><dd class="font-medium">{{ config('app.timezone') }}</dd></div>
                        <div><dt class="text-gray-500">Correo saliente</dt><dd class="font-medium">{{ config('mail.default') }} {{ config('mail.default') === 'log' ? '(se escriben en storage/logs)' : '' }}</dd></div>
                        <div><dt class="text-gray-500">Tamaño máx. de subida (PHP)</dt><dd class="font-medium">{{ ini_get('upload_max_filesize') }} / post {{ ini_get('post_max_size') }}</dd></div>
                    </dl>
                </x-card>
                <x-card title="Restablecer datos de demostración" icon="refresh">
                    <p class="text-sm text-gray-600 dark:text-gray-300">Para borrar todo y cargar de nuevo los datos de ejemplo use una de estas opciones:</p>
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-300">
                        <li>Navegador (sólo desde el mismo equipo): <a href="{{ asset('setup.php') }}" class="link" target="_blank">{{ asset('setup.php') }}</a></li>
                        <li>Consola: <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-gray-700">php setup.php --reset</code> o <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-gray-700">php artisan bufete:demo</code></li>
                    </ul>
                    <p class="mt-3 text-xs text-amber-600">⚠ Esta acción elimina definitivamente todos los datos. Cree antes una copia de seguridad.</p>
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>

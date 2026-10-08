@php $isCompany = old('type', $client->type?->value ?? 'persona') === 'empresa'; @endphp
<div x-data="{ type: '{{ old('type', $client->type?->value ?? 'persona') }}' }" class="space-y-6">
    <x-card title="Datos del cliente" icon="identification">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-6">
            <x-field label="Tipo de cliente" name="type" required class="md:col-span-2">
                <div class="grid grid-cols-2 gap-2">
                    @foreach (\App\Enums\ClientType::cases() as $t)
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm"
                               :class="type === '{{ $t->value }}' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'border-gray-300 dark:border-gray-600'">
                            <input type="radio" name="type" value="{{ $t->value }}" x-model="type" class="sr-only">
                            <x-icon :name="$t->value === 'empresa' ? 'building' : 'user'" class="h-4 w-4" /> {{ $t->label() }}
                        </label>
                    @endforeach
                </div>
            </x-field>
            <x-field name="name" required class="md:col-span-4">
                <label class="form-label" for="name"><span x-text="type === 'empresa' ? 'Razón social' : 'Nombre completo'"></span> <span class="text-red-500">*</span></label>
                <input id="name" name="name" value="{{ old('name', $client->name) }}" required class="form-control">
            </x-field>
            <x-field label="Tipo de documento" name="document_type" class="md:col-span-2">
                <select id="document_type" name="document_type" class="form-control">
                    <option value="">—</option>
                    @foreach (config('bufete.document_types') as $code => $label)
                        <option value="{{ $code }}" @selected(old('document_type', $client->document_type) === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-field>
            <x-field label="Número de documento" name="document_number" class="md:col-span-2">
                <input id="document_number" name="document_number" value="{{ old('document_number', $client->document_number) }}" class="form-control">
            </x-field>
            <x-field name="occupation" class="md:col-span-2">
                <label class="form-label" for="occupation" x-text="type === 'empresa' ? 'Actividad económica' : 'Ocupación'"></label>
                <input id="occupation" name="occupation" value="{{ old('occupation', $client->occupation) }}" class="form-control">
            </x-field>
            <x-field label="Persona de contacto / representante legal" name="contact_person" class="md:col-span-6" x-show="type === 'empresa'" x-cloak>
                <input id="contact_person" name="contact_person" value="{{ old('contact_person', $client->contact_person) }}" class="form-control">
            </x-field>
        </div>
    </x-card>

    <x-card title="Contacto" icon="phone">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-6">
            <x-field label="Correo electrónico" name="email" class="md:col-span-2">
                <input id="email" type="email" name="email" value="{{ old('email', $client->email) }}" class="form-control">
            </x-field>
            <x-field label="Teléfono / celular" name="phone" class="md:col-span-2" hint="Incluya el indicativo para usar WhatsApp (ej. +57 300…)">
                <input id="phone" name="phone" value="{{ old('phone', $client->phone) }}" class="form-control">
            </x-field>
            <x-field label="Teléfono alterno" name="alt_phone" class="md:col-span-2">
                <input id="alt_phone" name="alt_phone" value="{{ old('alt_phone', $client->alt_phone) }}" class="form-control">
            </x-field>
            <x-field label="Dirección" name="address" class="md:col-span-4">
                <input id="address" name="address" value="{{ old('address', $client->address) }}" class="form-control">
            </x-field>
            <x-field label="Ciudad" name="city" class="md:col-span-2">
                <input id="city" name="city" value="{{ old('city', $client->city) }}" class="form-control">
            </x-field>
        </div>
    </x-card>

    <x-card title="Gestión interna" icon="briefcase">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-6">
            @can('admin')
                <x-field label="Abogado a cargo" name="user_id" class="md:col-span-2">
                    <select id="user_id" name="user_id" class="form-control">
                        <option value="">—</option>
                        @foreach ($lawyers as $l)
                            <option value="{{ $l->id }}" @selected(old('user_id', $client->user_id) == $l->id)>{{ $l->name }} · {{ $l->role->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
            @endcan
            <x-field label="Notas internas" name="notes" class="md:col-span-6">
                <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes', $client->notes) }}</textarea>
            </x-field>
        </div>
    </x-card>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <x-card title="Datos del usuario" icon="user" class="xl:col-span-2">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <x-field label="Nombre completo" name="name" required><input name="name" value="{{ old('name', $user->name) }}" required class="form-control"></x-field>
            <x-field label="Correo electrónico (usuario)" name="email" required><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-control"></x-field>
            <x-field label="Teléfono" name="phone"><input name="phone" value="{{ old('phone', $user->phone) }}" class="form-control"></x-field>
            <x-field label="Tarjeta / matrícula profesional" name="professional_id"><input name="professional_id" value="{{ old('professional_id', $user->professional_id) }}" class="form-control" placeholder="T.P. 123.456"></x-field>
            <x-field label="Especialidad" name="specialty" class="md:col-span-2"><input name="specialty" value="{{ old('specialty', $user->specialty) }}" class="form-control"></x-field>
            <x-field :label="$user->exists ? 'Nueva contraseña (opcional)' : 'Contraseña'" name="password" :required="! $user->exists">
                <input type="password" name="password" autocomplete="new-password" class="form-control" @required(! $user->exists)>
            </x-field>
            <x-field label="Confirmar contraseña" name="password_confirmation">
                <input type="password" name="password_confirmation" autocomplete="new-password" class="form-control">
            </x-field>
        </div>
    </x-card>
    <x-card title="Rol y acceso" icon="shield">
        <div class="space-y-3">
            @foreach (\App\Enums\Role::cases() as $role)
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 dark:border-gray-700 dark:has-[:checked]:bg-primary-500/10">
                    <input type="radio" name="role" value="{{ $role->value }}" class="mt-1 text-primary-600 focus:ring-primary-500" @checked(old('role', $user->role?->value) === $role->value)>
                    <span><span class="block text-sm font-semibold">{{ $role->label() }}</span>
                        <span class="block text-xs text-gray-500">{{ match ($role) { \App\Enums\Role::Superadmin => 'Administra todo el bufete.', \App\Enums\Role::Senior => 'Gestiona sus casos y aprueba gastos de su equipo.', \App\Enums\Role::Junior => 'Trabaja en los casos asignados.' } }}</span></span>
                </label>
            @endforeach
            @error('role')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            <label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active', $user->is_active ?? true))> Usuario activo (puede iniciar sesión)</label>
        </div>
    </x-card>
</div>
